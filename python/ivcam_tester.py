import cv2
import numpy as np
import insightface
from insightface.app import FaceAnalysis
from ultralytics import YOLO
import threading
import time
from collections import deque
import warnings
import os
import requests
import io

# --- SILENT WARNINGS ---
os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

# --- CONFIG ---
EMOTION_MODEL = "best_v2.pt"
LABELS = ["POSITIF", "NEGATIF"]

COLORS = {
    0: (50, 205, 50),
    1: (0, 0, 255)
}

SKIP_FRAMES = 2

# =========================
# API CONFIG
# =========================
API_URL = "https://deteksiemosi.com/api/store"

# SESSION_ID = 2
NOMOR_MAHASISWA = 1

API_INTERVAL = 10  # anti spam


def send_to_api(frame, label="NEGATIF", confidence=0.95):
    try:
        start_time = time.time()

        # encode frame
        _, buffer = cv2.imencode('.jpg', frame, [int(cv2.IMWRITE_JPEG_QUALITY), 80])
        file_bytes = io.BytesIO(buffer.tobytes())

        files = {
            "image": ("frame.jpg", file_bytes, "image/jpeg")
        }

        data = {
            # "session_id": SESSION_ID,
            "nomor_mahasiswa": NOMOR_MAHASISWA,
            "label": label,
            "confidence": float(confidence)
        }

        headers = {
            "Accept": "application/json"
        }

        print("\n🚀 [API CALL] Mengirim frame ke server...")

        response = requests.post(
            API_URL,
            data=data,
            files=files,
            headers=headers,
            timeout=5
        )

        elapsed = time.time() - start_time

        print(f"📡 [API RESPONSE] Status: {response.status_code} | Time: {elapsed:.2f}s")

        if response.status_code == 200:
            try:
                return response.json()
            except:
                print("⚠️ Response bukan JSON:", response.text)
        else:
            print("⚠️ API ERROR:", response.text)

    except Exception as e:
        print("❌ API EXCEPTION:", e)

    return None


# --- CAMERA THREADING ---
class VideoStream:
    def __init__(self, src=0):
        self.cap = cv2.VideoCapture(src, cv2.CAP_AVFOUNDATION)

        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
        self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

        self.ret, self.frame = self.cap.read()
        self.stopped = False

    def start(self):
        threading.Thread(target=self.update, daemon=True).start()
        return self

    def update(self):
        while not self.stopped:
            ret, frame = self.cap.read()
            if ret:
                self.frame = frame
            time.sleep(0.01)

    def read(self):
        return self.frame

    def stop(self):
        self.stopped = True
        self.cap.release()


# --- TRACKER ---
class SimpleTracker:
    def __init__(self, max_lost=30, jarak_max=150):
        self.tracks = {}
        self.counter = 1
        self.next_id = 0
        self.max_lost = max_lost
        self.jarak_max = jarak_max
        self.history = {}

    def update(self, detections):
        if not detections:
            return {}

        matched = {}
        unmatched = list(range(len(detections)))

        for tid, track in self.tracks.items():
            if not unmatched:
                break

            best_idx = None
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
                    best_idx = idx

            if best_idx is not None and best_dist < self.jarak_max:
                matched[best_idx] = tid
                unmatched.remove(best_idx)
                self.tracks[tid]["box"] = detections[best_idx]
                self.tracks[tid]["lost"] = 0

        for idx in unmatched:
            tid = self.next_id
            self.next_id += 1

            self.tracks[tid] = {
                "box": detections[idx],
                "lost": 0,
                "nomor": self.counter
            }

            self.history[tid] = deque(maxlen=5)
            matched[idx] = tid
            self.counter += 1

        return {idx: (self.tracks[tid]["nomor"], tid) for idx, tid in matched.items()}


# --- MAIN ---
def run():
    print("Inisialisasi Face Detection SCRFD...")
    app = FaceAnalysis(name="buffalo_sc", allowed_modules=["detection"])
    app.prepare(ctx_id=0, det_size=(640, 640))

    print("Loading Model Emosi YOLO...")
    emotion_model = YOLO(EMOTION_MODEL)

    tracker = SimpleTracker()

    vs = VideoStream(src=0).start()

    frame_count = 0
    cached_boxes = []
    hasil_tracking = {}

    print("✅ Sistem siap")

    while True:
        frame = vs.read()
        if frame is None:
            continue

        display_frame = frame.copy()
        h_img, w_img, _ = frame.shape

        # =========================
        # API CALL (ANTI SPAM)
        # =========================
        if frame_count % API_INTERVAL == 0:

            label_api = "NEGATIF"
            conf_api = 0.95

            if tracker.history:
                for tid in tracker.history:
                    if tracker.history[tid]:
                        last = tracker.history[tid][-1]
                        label_api = LABELS[last[0]]
                        conf_api = float(last[1])
                        break

            result = send_to_api(frame, label_api, conf_api)

            if result:
                text = f"{result.get('label')} ({result.get('confidence', 0)*100:.0f}%)"
                cv2.putText(display_frame, text, (50, 50),
                            cv2.FONT_HERSHEY_SIMPLEX, 1, (0,255,0), 2)

        # =========================
        # FACE DETECTION
        # =========================
        if frame_count % SKIP_FRAMES == 0:
            faces = app.get(frame)

            cached_boxes = []
            for face in faces:
                x1, y1, x2, y2 = map(int, face.bbox)
                cached_boxes.append([x1, y1, x2, y2])

            hasil_tracking = tracker.update(cached_boxes)

        # =========================
        # DRAW
        # =========================
        for idx, box in enumerate(cached_boxes):
            if idx in hasil_tracking:
                nomor, tid = hasil_tracking[idx]

                color = (0, 255, 0)

                cv2.rectangle(display_frame, (box[0], box[1]), (box[2], box[3]), color, 2)

                cv2.putText(
                    display_frame,
                    f"Mhs {nomor}",
                    (box[0], box[1] - 10),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.6,
                    color,
                    2
                )

        cv2.imshow("Emotion Detection + API", display_frame)

        frame_count += 1

        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    vs.stop()
    cv2.destroyAllWindows()


if __name__ == "__main__":
    run()