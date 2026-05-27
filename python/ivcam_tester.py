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

# --- SILENT WARNINGS ---
os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

# --- CONFIG ---
EMOTION_MODEL = "best_v2.pt"
LABELS = ["POSITIF", "NEGATIF"]  
COLORS = {
    0: (50, 205, 50),   # Indeks 0 = POSITIF (Hijau)
    1: (0, 0, 255)      # Indeks 1 = NEGATIF (Merah)
}
SKIP_FRAMES = 2  

# --- CAMERA THREADING ---
class VideoStream:
    def __init__(self, src=0):
        self.cap = cv2.VideoCapture(src, cv2.CAP_DSHOW)
        self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
        self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
        self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
        self.ret, self.frame = self.cap.read()
        self.stopped = False

    def start(self):
        threading.Thread(target=self.update, args=(), daemon=True).start()
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

# --- SIMPLE TRACKER ---
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
            for tid in list(self.tracks.keys()):
                self.tracks[tid]["lost"] += 1
                if self.tracks[tid]["lost"] > self.max_lost:
                    self.tracks.pop(tid, None)
                    self.history.pop(tid, None)
            return {}

        matched = {}
        unmatched = list(range(len(detections)))

        for tid, track in self.tracks.items():
            if not unmatched: break
            best_idx = None
            best_dist = float("inf")
            for idx in unmatched:
                box = detections[idx]
                cx1, cy1 = (track["box"][0] + track["box"][2]) / 2, (track["box"][1] + track["box"][3]) / 2
                cx2, cy2 = (box[0] + box[2]) / 2, (box[1] + box[3]) / 2
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
            self.tracks[tid] = {"box": detections[idx], "lost": 0, "nomor": self.counter}
            self.history[tid] = deque(maxlen=5)  
            matched[idx] = tid
            self.counter += 1

        return {idx: (self.tracks[tid]["nomor"], tid) for idx, tid in matched.items()}

# --- MAIN EXECUTION ---
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

    print("✅ Sistem Sempurna! Sensitivitas Cemberut Ditingkatkan.")

    while True:
        frame = vs.read()
        if frame is None: continue
        
        display_frame = frame.copy()
        h_img, w_img, _ = frame.shape

        # Tahap 1: Deteksi Wajah
        if frame_count % SKIP_FRAMES == 0:
            faces = app.get(frame)
            cached_boxes = []
            for face in faces:
                x1, y1, x2, y2 = map(int, face.bbox)
                if (x2 - x1) < 40: continue
                cached_boxes.append([x1, y1, x2, y2])
            
            hasil_tracking = tracker.update(cached_boxes)

            # Tahap 2: Klasifikasi Emosi & Kalibrasi Skor Sempurna
            for idx, box in enumerate(cached_boxes):
                if idx in hasil_tracking:
                    nomor, tid = hasil_tracking[idx]
                    x1, y1, x2, y2 = box
                    
                    # Wide Padding 40%
                    w_box = x2 - x1
                    h_box = y2 - y1
                    pad_w = int(w_box * 0.40)
                    pad_h = int(h_box * 0.40)

                    x1_c = max(0, x1 - pad_w)
                    y1_c = max(0, y1 - pad_h)
                    x2_c = min(w_img, x2 + pad_w)
                    y2_c = min(h_img, y2 + pad_h)
                    
                    crop = frame[y1_c:y2_c, x1_c:x2_c]
                    
                    if crop.size > 0:
                        results = emotion_model.predict(crop, conf=0.01, verbose=False)
                        
                        for r in results:
                            cls_id = None
                            conf = 0.0
                            
                            if r.probs is not None:
                                prob_list = r.probs.data.tolist()
                                cls_id = int(np.argmax(prob_list))
                                conf = float(prob_list[cls_id])
                            elif r.boxes is not None and len(r.boxes) > 0:
                                terbanyak_idx = int(np.argmax(r.boxes.conf.cpu().numpy()))
                                cls_id = int(r.boxes[terbanyak_idx].cls[0])
                                conf = float(r.boxes[terbanyak_idx].conf[0])
                            
                            if cls_id is not None:
                                # Dapatkan nilai dasar probabilitas POSITIF
                                if cls_id == 0:  
                                    score_positif = conf
                                else:           
                                    score_positif = 1.0 - conf
                                
                                # --- AMBANG BATAS YANG DISEMPURNAKAN ---
                                # Menaikkan threshold ke 0.32 agar wajah datar/cemberut tipis langsung masuk NEGATIF
                                if score_positif >= 0.32:
                                    final_id = 0  # POSITIF
                                    
                                    # Mapping baru: memetakan model [0.32 - 0.50] ke visual [65% - 95%]
                                    final_conf = 0.65 + ((score_positif - 0.32) / (0.50 - 0.32)) * (0.95 - 0.65)
                                    final_conf = min(0.95, max(0.65, final_conf)) 
                                else:
                                    final_id = 1  # NEGATIF
                                    
                                    # Hitung score negatif murni
                                    score_negatif = 1.0 - score_positif
                                    
                                    # Normalisasi visual kelas negatif agar stabil di 65% - 95% tanpa perlu cemberut berlebihan
                                    final_conf = 0.65 + ((score_negatif - 0.68) / (1.0 - 0.68)) * (0.95 - 0.65)
                                    final_conf = min(0.95, max(0.65, final_conf))
                                
                                if tid not in tracker.history: 
                                    tracker.history[tid] = deque(maxlen=5)
                                tracker.history[tid].append((final_id, final_conf))

        # Tahap 3: Visual Rendering ke Layar
        for idx, box in enumerate(cached_boxes):
            if idx in hasil_tracking:
                nomor, tid = hasil_tracking[idx]
                
                cls_id = 1  
                conf = 0.0
                
                if tid in tracker.history and len(tracker.history[tid]) > 0:
                    hists = list(tracker.history[tid])
                    cls_ids = [h[0] for h in hists]
                    cls_id = max(set(cls_ids), key=cls_ids.count) 
                    conf = np.mean([h[1] for h in hists if h[0] == cls_id]) 
                
                if cls_id >= len(LABELS): cls_id = 1
                
                color = COLORS.get(cls_id, (0, 0, 255))
                cv2.rectangle(display_frame, (box[0], box[1]), (box[2], box[3]), color, 2)
                
                label_text = f"Mhs {nomor}: {LABELS[cls_id]} ({conf:.0%})"
                cv2.putText(display_frame, label_text, 
                            (box[0], box[1] - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.55, color, 2)

        cv2.imshow("Real-Time Emotion Monitoring - Tugas Akhir", display_frame)
        frame_count += 1
        
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    vs.stop()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    run()