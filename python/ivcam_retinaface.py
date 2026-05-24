from ultralytics import YOLO
from retinaface import RetinaFace
import cv2
import numpy as np
import warnings
import os

os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

MODEL_PATH = "best_v2.pt"

LABELS = ["POSITIF", "NEGATIF"]
COLORS = {
    0: (50, 205, 50),
    1: (0, 0, 255),
}

# =====================
# ByteTrack sederhana
# =====================
class ByteTracker:
    def __init__(self, max_lost=30, jarak_max=100):
        self.tracks      = {}   # {track_id: {"box": [], "lost": 0, "nomor": int}}
        self.id_mapping  = {}   # {track_id: nomor_urut}
        self.counter     = 1
        self.next_id     = 0
        self.max_lost    = max_lost
        self.jarak_max   = jarak_max

    def hitung_jarak(self, box1, box2):
        cx1 = (box1[0] + box1[2]) / 2
        cy1 = (box1[1] + box1[3]) / 2
        cx2 = (box2[0] + box2[2]) / 2
        cy2 = (box2[1] + box2[3]) / 2
        return np.sqrt((cx1-cx2)**2 + (cy1-cy2)**2)

    def update(self, detections):
        # detections: list of [x1, y1, x2, y2]
        if not detections:
            # Tambah lost counter semua track
            for tid in list(self.tracks.keys()):
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    del self.tracks[tid]
            return {}

        # Match deteksi ke track yang ada
        matched    = {}  # {detection_idx: track_id}
        unmatched  = list(range(len(detections)))

        for tid, track in self.tracks.items():
            if not unmatched:
                break
            best_idx  = None
            best_dist = float("inf")

            for idx in unmatched:
                dist = self.hitung_jarak(detections[idx], track["box"])
                if dist < best_dist:
                    best_dist = dist
                    best_idx  = idx

            if best_idx is not None and best_dist < self.jarak_max:
                matched[best_idx] = tid
                unmatched.remove(best_idx)
                self.tracks[tid]["box"]  = detections[best_idx]
                self.tracks[tid]["lost"] = 0

        # Buat track baru untuk deteksi yang tidak matched
        for idx in unmatched:
            tid = self.next_id
            self.next_id += 1
            self.tracks[tid] = {
                "box":   detections[idx],
                "lost":  0,
                "nomor": self.counter
            }
            self.id_mapping[tid] = self.counter
            matched[idx]         = tid
            self.counter        += 1

        # Hapus track yang sudah lama hilang
        for tid in list(self.tracks.keys()):
            if tid not in matched.values():
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    del self.tracks[tid]

        # Return hasil: {detection_idx: nomor_mahasiswa}
        return {
            idx: self.tracks[tid]["nomor"]
            for idx, tid in matched.items()
            if tid in self.tracks
        }


def run():
    model   = YOLO(MODEL_PATH)
    tracker = ByteTracker(max_lost=30, jarak_max=120)
    cap     = cv2.VideoCapture(1, cv2.CAP_DSHOW)

    if not cap.isOpened():
        print("❌ Gagal buka kamera!")
        return

    print("✅ Kamera terhubung! Tekan q untuk keluar")

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        frame_resized = cv2.resize(frame, (1280, 720))

        # Stage 1 — RetinaFace deteksi semua wajah
        try:
            wajah = RetinaFace.detect_faces(frame_resized)
        except:
            wajah = {}

        if not isinstance(wajah, dict) or len(wajah) == 0:
            cv2.imshow("Emotion Monitoring", frame_resized)
            if cv2.waitKey(1) & 0xFF == ord("q"):
                break
            continue

        # Kumpulkan semua bounding box wajah
        boxes = []
        for key, val in wajah.items():
            x1, y1, x2, y2 = val["facial_area"]
            x1 = max(0, x1)
            y1 = max(0, y1)
            x2 = min(frame_resized.shape[1], x2)
            y2 = min(frame_resized.shape[0], y2)
            boxes.append([x1, y1, x2, y2])

        # Update ByteTracker
        hasil_tracking = tracker.update(boxes)

        # Stage 2 — YOLO klasifikasi + gambar hasil
        for idx, box in enumerate(boxes):
            x1, y1, x2, y2 = box
            nomor = hasil_tracking.get(idx, "?")

            # Crop wajah untuk klasifikasi
            wajah_crop = frame_resized[y1:y2, x1:x2]
            if wajah_crop.size == 0:
                continue

            # YOLO klasifikasi emosi
            results = model.predict(
                source=wajah_crop,
                conf=0.1,
                verbose=False,
            )

            cls_id = 1
            conf_v = 0.0
            for r in results:
                if len(r.boxes) > 0:
                    cls_id = int(r.boxes[0].cls[0])
                    conf_v = float(r.boxes[0].conf[0])

            # Gambar hasil
            color = COLORS[cls_id]
            label = f"Mahasiswa {nomor} | {LABELS[cls_id]} {conf_v:.0%}"

            cv2.rectangle(frame_resized, (x1, y1), (x2, y2), color, 2)
            (tw, th), _ = cv2.getTextSize(
                label, cv2.FONT_HERSHEY_SIMPLEX, 0.55, 2
            )
            cv2.rectangle(
                frame_resized,
                (x1, y1 - th - 10),
                (x1 + tw + 6, y1),
                color, -1
            )
            cv2.putText(
                frame_resized, label,
                (x1 + 3, y1 - 5),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.55, (255, 255, 255), 2
            )

        cv2.imshow("Emotion Monitoring", frame_resized)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    run()