import cv2
import numpy as np
import insightface
from insightface.app import FaceAnalysis
from ultralytics import YOLO
import threading
import time
import requests
import base64
import datetime
from collections import deque
import warnings
import os

# --- SILENT WARNINGS ---
os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

# ============================================================
# CONFIG — SESUAIKAN BAGIAN INI
# ============================================================
EMOTION_MODEL = "best_v2.pt"
LABELS        = ["POSITIF", "NEGATIF"]
COLORS        = {
    0: (50, 205, 50),  # POSITIF → Hijau
    1: (0,   0, 255),  # NEGATIF → Merah
}
SKIP_FRAMES = 2

# --- CONFIG SERVER ---
SERVER_URL         = "https://server.osimpu.org/api/terima_data.php"
API_KEY            = "susksesTA"   # samakan dengan di file PHP
NAMA_KELAS         = "Pemrograman Web"      # ganti sesuai kelas
NAMA_DOSEN         = "Bu Ani"               # ganti sesuai dosen
SNAPSHOT_INTERVAL  = 10                     # kirim snapshot tiap N detik per wajah
KIRIM_KE_SERVER    = True                   # set False jika mau jalankan offline


# ============================================================
# CLASS: VideoStream — Thread pembaca kamera
# ============================================================
class VideoStream:
    """
    Membaca frame kamera di thread terpisah agar loop utama
    tidak terhambat menunggu setiap frame selesai dibaca.
    """
    def __init__(self, src=0):
        # Buka kamera (src=0 = webcam default / iVCam)
        self.cap = cv2.VideoCapture(src, cv2.CAP_DSHOW)
        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
        self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # buffer 1 agar selalu frame terbaru
        self.ret, self.frame = self.cap.read()
        self.stopped = False

    def start(self):
        # Jalankan thread background
        threading.Thread(target=self.update, args=(), daemon=True).start()
        return self

    def update(self):
        # Loop terus-menerus membaca frame baru setiap 10ms
        while not self.stopped:
            ret, frame = self.cap.read()
            if ret:
                self.frame = frame
            time.sleep(0.01)

    def read(self):
        # Kembalikan frame terbaru
        return self.frame

    def stop(self):
        # Hentikan thread dan lepas resource kamera
        self.stopped = True
        self.cap.release()


# ============================================================
# CLASS: SimpleTracker — Pelacak ID wajah antar frame
# ============================================================
class SimpleTracker:
    """
    Melacak setiap wajah antar frame menggunakan jarak centroid.
    Memberikan nomor urut mahasiswa yang persisten selama sesi.
    """
    def __init__(self, max_lost=30, jarak_max=150):
        # max_lost: berapa frame wajah boleh hilang sebelum ID dihapus
        # jarak_max: jarak centroid maks (px) agar dianggap wajah yang sama
        self.tracks    = {}
        self.counter   = 1
        self.next_id   = 0
        self.max_lost  = max_lost
        self.jarak_max = jarak_max
        self.history   = {}  # {tid: deque berisi 5 hasil klasifikasi terakhir}

    def update(self, detections):
        """
        Terima list bounding box frame saat ini.
        Cocokkan dengan track yang ada via jarak centroid.
        Wajah baru yang tidak cocok → dapat ID baru.
        """
        if not detections:
            # Tambah hitungan 'lost' untuk semua track yang aktif
            for tid in list(self.tracks.keys()):
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    self.tracks.pop(tid, None)
                    self.history.pop(tid, None)
            return {}

        matched   = {}
        unmatched = list(range(len(detections)))

        for tid, track in self.tracks.items():
            if not unmatched:
                break
            best_idx  = None
            best_dist = float("inf")

            for idx in unmatched:
                box = detections[idx]
                cx1 = (track["box"][0] + track["box"][2]) / 2
                cy1 = (track["box"][1] + track["box"][3]) / 2
                cx2 = (box[0] + box[2]) / 2
                cy2 = (box[1] + box[3]) / 2
                dist = np.sqrt((cx1 - cx2)**2 + (cy1 - cy2)**2)

                if dist < best_dist:
                    best_dist = dist
                    best_idx  = idx

            if best_idx is not None and best_dist < self.jarak_max:
                matched[best_idx] = tid
                unmatched.remove(best_idx)
                self.tracks[tid]["box"]  = detections[best_idx]
                self.tracks[tid]["lost"] = 0

        # Wajah baru → buat ID baru
        for idx in unmatched:
            tid = self.next_id
            self.next_id += 1
            self.tracks[tid]  = {"box": detections[idx], "lost": 0, "nomor": self.counter}
            self.history[tid] = deque(maxlen=5)
            matched[idx]      = tid
            self.counter      += 1

        return {idx: (self.tracks[tid]["nomor"], tid) for idx, tid in matched.items()}


# ============================================================
# FUNGSI SERVER — Komunikasi ke cPanel via PHP API
# ============================================================

def mulai_sesi(nama_kelas, dosen):
    """
    Kirim permintaan ke server untuk membuat record sesi baru di database.
    Mengembalikan session_id yang akan dipakai selama sesi berlangsung.
    """
    if not KIRIM_KE_SERVER:
        return None
    try:
        res = requests.post(SERVER_URL, json={
            "api_key"    : API_KEY,
            "aksi"       : "mulai_sesi",
            "nama_kelas" : nama_kelas,
            "dosen"      : dosen,
        }, timeout=5)
        data = res.json()
        if data.get("status") == "ok":
            print(f"✅ Sesi dimulai | session_id: {data['session_id']}")
            return data["session_id"]
        else:
            print(f"⚠ Server gagal buat sesi: {data}")
    except Exception as e:
        print(f"⚠ Tidak bisa konek ke server: {e}")
    return None


def kirim_deteksi(session_id, nomor_mhs, label, confidence):
    """
    Kirim satu hasil deteksi emosi ke server secara non-blocking.
    Data masuk ke tabel 'detections' di MySQL.
    """
    if not KIRIM_KE_SERVER or not session_id:
        return
    try:
        requests.post(SERVER_URL, json={
            "api_key"        : API_KEY,
            "aksi"           : "kirim_deteksi",
            "session_id"     : session_id,
            "nomor_mahasiswa": nomor_mhs,
            "label"          : label,
            "confidence"     : round(float(confidence), 4),
        }, timeout=3)
    except:
        pass  # diam-diam skip jika jaringan lambat/putus


def kirim_snapshot(session_id, nomor_mhs, label, crop_img):
    """
    Encode gambar crop wajah ke base64 dan kirim ke server.
    Server menyimpan file .jpg dan mencatat path-nya di tabel 'snapshots'.
    """
    if not KIRIM_KE_SERVER or not session_id:
        return
    try:
        _, buf    = cv2.imencode(".jpg", crop_img, [cv2.IMWRITE_JPEG_QUALITY, 70])
        img_b64   = base64.b64encode(buf).decode("utf-8")
        requests.post(SERVER_URL, json={
            "api_key"        : API_KEY,
            "aksi"           : "kirim_snapshot",
            "session_id"     : session_id,
            "nomor_mahasiswa": nomor_mhs,
            "label"          : label,
            "gambar_base64"  : img_b64,
        }, timeout=5)
    except:
        pass


def kirim_di_thread(fn, *args):
    """
    Jalankan fungsi pengiriman di thread terpisah agar tidak
    memblokir loop deteksi utama saat menunggu respons server.
    """
    threading.Thread(target=fn, args=args, daemon=True).start()


# ============================================================
# FUNGSI UTAMA — run()
# ============================================================
def run():
    # ── Init model ──────────────────────────────────────────
    print("Inisialisasi Face Detection SCRFD...")
    app = FaceAnalysis(name="buffalo_sc", allowed_modules=["detection"])
    app.prepare(ctx_id=0, det_size=(640, 640))

    print("Loading Model Emosi YOLO...")
    emotion_model = YOLO(EMOTION_MODEL)

    tracker = SimpleTracker()

    # ── Mulai kamera ────────────────────────────────────────
    vs = VideoStream(src=0).start()

    # ── Mulai sesi di server ────────────────────────────────
    session_id = mulai_sesi(NAMA_KELAS, NAMA_DOSEN)
    if not session_id and KIRIM_KE_SERVER:
        print("⚠ Berjalan OFFLINE — data tidak dikirim ke server.")

    # ── Variabel loop ───────────────────────────────────────
    frame_count        = 0
    cached_boxes       = []
    hasil_tracking     = {}
    last_snapshot_time = {}  # {tid: unix timestamp kirim snapshot terakhir}

    print("✅ Sistem berjalan! Tekan Q untuk berhenti.")

    while True:
        frame = vs.read()
        if frame is None:
            continue

        display_frame      = frame.copy()
        h_img, w_img, _    = frame.shape

        # ── TAHAP 1: Deteksi Wajah & Tracking ───────────────
        if frame_count % SKIP_FRAMES == 0:
            faces        = app.get(frame)
            cached_boxes = []

            for face in faces:
                x1, y1, x2, y2 = map(int, face.bbox)
                if (x2 - x1) < 40:
                    continue  # abaikan wajah terlalu kecil
                cached_boxes.append([x1, y1, x2, y2])

            hasil_tracking = tracker.update(cached_boxes)

            # ── TAHAP 2: Crop, Klasifikasi Emosi & Kirim Data ─
            for idx, box in enumerate(cached_boxes):
                if idx not in hasil_tracking:
                    continue

                nomor, tid = hasil_tracking[idx]
                x1, y1, x2, y2 = box

                # Padding 40% agar ekspresi wajah lebih terlihat
                w_box = x2 - x1
                h_box = y2 - y1
                pad_w = int(w_box * 0.40)
                pad_h = int(h_box * 0.40)

                x1_c = max(0,     x1 - pad_w)
                y1_c = max(0,     y1 - pad_h)
                x2_c = min(w_img, x2 + pad_w)
                y2_c = min(h_img, y2 + pad_h)

                crop = frame[y1_c:y2_c, x1_c:x2_c]
                if crop.size == 0:
                    continue

                # Prediksi emosi dengan YOLO classification
                results = emotion_model.predict(crop, conf=0.01, verbose=False)

                for r in results:
                    cls_id = None
                    conf   = 0.0

                    if r.probs is not None:
                        prob_list = r.probs.data.tolist()
                        cls_id    = int(np.argmax(prob_list))
                        conf      = float(prob_list[cls_id])
                    elif r.boxes is not None and len(r.boxes) > 0:
                        best_idx  = int(np.argmax(r.boxes.conf.cpu().numpy()))
                        cls_id    = int(r.boxes[best_idx].cls[0])
                        conf      = float(r.boxes[best_idx].conf[0])

                    if cls_id is None:
                        continue

                    # Kalibrasi skor ke rentang POSITIF
                    score_positif = conf if cls_id == 0 else 1.0 - conf

                    # Threshold 0.32: di bawahnya langsung NEGATIF
                    if score_positif >= 0.32:
                        final_id   = 0  # POSITIF
                        final_conf = 0.65 + ((score_positif - 0.32) / (0.50 - 0.32)) * (0.95 - 0.65)
                        final_conf = min(0.95, max(0.65, final_conf))
                    else:
                        final_id    = 1  # NEGATIF
                        score_neg   = 1.0 - score_positif
                        final_conf  = 0.65 + ((score_neg - 0.68) / (1.0 - 0.68)) * (0.95 - 0.65)
                        final_conf  = min(0.95, max(0.65, final_conf))

                    # Simpan ke history tracker untuk smoothing
                    if tid not in tracker.history:
                        tracker.history[tid] = deque(maxlen=5)
                    tracker.history[tid].append((final_id, final_conf))

                    # ── Kirim deteksi ke server (non-blocking) ──
                    kirim_di_thread(
                        kirim_deteksi,
                        session_id, nomor, LABELS[final_id], final_conf
                    )

                    # ── Kirim snapshot tiap SNAPSHOT_INTERVAL detik ──
                    now = datetime.datetime.now().timestamp()
                    if (tid not in last_snapshot_time or
                            now - last_snapshot_time[tid] >= SNAPSHOT_INTERVAL):
                        kirim_di_thread(
                            kirim_snapshot,
                            session_id, nomor, LABELS[final_id], crop.copy()
                        )
                        last_snapshot_time[tid] = now

        # ── TAHAP 3: Render Visual ke Layar ─────────────────
        for idx, box in enumerate(cached_boxes):
            if idx not in hasil_tracking:
                continue

            nomor, tid = hasil_tracking[idx]
            cls_id     = 1   # default NEGATIF jika belum ada history
            conf       = 0.0

            if tid in tracker.history and len(tracker.history[tid]) > 0:
                hists    = list(tracker.history[tid])
                cls_ids  = [h[0] for h in hists]
                # Majority voting: ambil label terbanyak dari 5 frame terakhir
                cls_id   = max(set(cls_ids), key=cls_ids.count)
                # Rata-rata confidence dari label yang menang
                conf     = np.mean([h[1] for h in hists if h[0] == cls_id])

            if cls_id >= len(LABELS):
                cls_id = 1

            color      = COLORS.get(cls_id, (0, 0, 255))
            label_text = f"Mhs {nomor}: {LABELS[cls_id]} ({conf:.0%})"

            cv2.rectangle(display_frame,
                          (box[0], box[1]), (box[2], box[3]), color, 2)
            cv2.putText(display_frame, label_text,
                        (box[0], box[1] - 10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.55, color, 2)

        # Tampilkan status koneksi di pojok kiri atas
        status_txt   = f"Server: {'ON' if session_id else 'OFFLINE'}"
        status_color = (0, 200, 0) if session_id else (0, 100, 255)
        cv2.putText(display_frame, status_txt,
                    (10, 25), cv2.FONT_HERSHEY_SIMPLEX, 0.55, status_color, 2)

        cv2.imshow("Real-Time Emotion Monitoring - Tugas Akhir", display_frame)
        frame_count += 1

        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    vs.stop()
    cv2.destroyAllWindows()
    print("Program selesai.")
def cek_koneksi():
    try:
        res = requests.post(SERVER_URL, json={
            "api_key" : API_KEY,
            "aksi"    : "mulai_sesi",
            "nama_kelas": "TEST",
            "dosen"   : "TEST",
        }, timeout=5)
        print("✅ Server OK:", res.json())
    except Exception as e:
        print("❌ Gagal konek:", e)


def cek_koneksi():
    try:
        res = requests.post(SERVER_URL, json={
            "api_key"   : API_KEY,
            "aksi"      : "mulai_sesi",
            "nama_kelas": "TEST",
            "dosen"     : "TEST",
        }, timeout=5)
        print("✅ Server OK:", res.json())
    except Exception as e:
        print("❌ Gagal konek:", e)

if __name__ == "__main__":
    cek_koneksi()   # test dulu, kalau OK baru jalankan run()
    # run()