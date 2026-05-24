import cv2
import numpy as np
import mediapipe as mp
from mediapipe.tasks import python
from mediapipe.tasks.python import vision
from ultralytics import YOLO
import warnings
import os
import urllib.request

os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

EMOTION_MODEL = "best_v2.pt"

LABELS = ["POSITIF", "NEGATIF"]
COLORS = {
    0: (50, 205, 50),
    1: (0, 0, 255),
}

class SimpleTracker:
    def __init__(self, max_lost=30, jarak_max=120):
        self.tracks    = {}
        self.counter   = 1
        self.next_id   = 0
        self.max_lost  = max_lost
        self.jarak_max = jarak_max

    def hitung_jarak(self, box1, box2):
        cx1 = (box1[0] + box1[2]) / 2
        cy1 = (box1[1] + box1[3]) / 2
        cx2 = (box2[0] + box2[2]) / 2
        cy2 = (box2[1] + box2[3]) / 2
        return np.sqrt((cx1-cx2)**2 + (cy1-cy2)**2)

    def update(self, detections):
        if not detections:
            for tid in list(self.tracks.keys()):
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    del self.tracks[tid]
            return {}

        matched   = {}
        unmatched = list(range(len(detections)))

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
                matched[best_idx]        = tid
                unmatched.remove(best_idx)
                self.tracks[tid]["box"]  = detections[best_idx]
                self.tracks[tid]["lost"] = 0

        for idx in unmatched:
            tid = self.next_id
            self.next_id += 1
            self.tracks[tid] = {
                "box":   detections[idx],
                "lost":  0,
                "nomor": self.counter
            }
            matched[idx]  = tid
            self.counter += 1

        for tid in list(self.tracks.keys()):
            if tid not in matched.values():
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    del self.tracks[tid]

        return {
            idx: self.tracks[tid]["nomor"]
            for idx, tid in matched.items()
            if tid in self.tracks
        }

def download_model():
    model_path = "blaze_face_short_range.tflite"
    if not os.path.exists(model_path):
        print("Downloading MediaPipe face model...")
        url = "https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite"
        urllib.request.urlretrieve(url, model_path)
        print("✅ Model downloaded!")
    return model_path

def run():
    # Download model MediaPipe
    model_path = download_model()

    # Load MediaPipe face detector (API baru)
    base_options = python.BaseOptions(model_asset_path=model_path)
    options      = vision.FaceDetectorOptions(
        base_options=base_options,
        min_detection_confidence=0.4
    )
    detector = vision.FaceDetector.create_from_options(options)

    emotion_model = YOLO(EMOTION_MODEL)
    tracker       = SimpleTracker()

    cap = cv2.VideoCapture(1, cv2.CAP_DSHOW)

    if not cap.isOpened():
        print("❌ Gagal buka kamera!")
        return

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)

    print("✅ Kamera terhubung! Tekan q untuk keluar")

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        h, w = frame.shape[:2]

        # Stage 1 — MediaPipe deteksi semua wajah
        frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        mp_image  = mp.Image(
            image_format=mp.ImageFormat.SRGB,
            data=frame_rgb
        )
        hasil = detector.detect(mp_image)

        boxes = []
        for detection in hasil.detections:
            bbox = detection.bounding_box
            x1   = max(0, bbox.origin_x)
            y1   = max(0, bbox.origin_y)
            x2   = min(w, bbox.origin_x + bbox.width)
            y2   = min(h, bbox.origin_y + bbox.height)
            boxes.append([x1, y1, x2, y2])

        # Update tracker
        hasil_tracking = tracker.update(boxes)

        # Stage 2 — Klasifikasi emosi tiap wajah
        for idx, box in enumerate(boxes):
            x1, y1, x2, y2 = box
            nomor = hasil_tracking.get(idx, "?")

            wajah_crop = frame[y1:y2, x1:x2]
            if wajah_crop.size == 0:
                continue

            results = emotion_model.predict(
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

            color = COLORS[cls_id]
            label = f"Mahasiswa {nomor} | {LABELS[cls_id]} {conf_v:.0%}"

            cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
            (tw, th), _ = cv2.getTextSize(
                label, cv2.FONT_HERSHEY_SIMPLEX, 0.55, 2
            )
            cv2.rectangle(
                frame,
                (x1, y1 - th - 10),
                (x1 + tw + 6, y1),
                color, -1
            )
            cv2.putText(
                frame, label,
                (x1 + 3, y1 - 5),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.55, (255, 255, 255), 2
            )

        cv2.imshow("Emotion Monitoring", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    run()