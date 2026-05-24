from ultralytics import YOLO
import cv2
import warnings
import os
import numpy as np

os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

MODEL_PATH = "best_v2.pt"

LABELS = ["POSITIF", "NEGATIF"]
COLORS = {
    0: (50, 205, 50),
    1: (0, 0, 255),
}

def hitung_jarak(box1, box2):
    cx1 = (box1[0] + box1[2]) / 2
    cy1 = (box1[1] + box1[3]) / 2
    cx2 = (box2[0] + box2[2]) / 2
    cy2 = (box2[1] + box2[3]) / 2
    return np.sqrt((cx1-cx2)**2 + (cy1-cy2)**2)

def run():
    model = YOLO(MODEL_PATH)
    cap   = cv2.VideoCapture(0, cv2.CAP_DSHOW)

    if not cap.isOpened():
        print("Gagal buka kamera!")
        return

    print("Kamera terhubung! Tekan q untuk keluar")

    id_mapping       = {}
    posisi_terakhir  = {}
    counter          = 1
    JARAK_MAX        = 150

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        # Perbesar frame untuk deteksi lebih akurat
        frame_resized = cv2.resize(frame, (1280, 720))

        results = model.track(
            source=frame_resized,
            conf=0.1,
            persist=True,
            verbose=False,
        )

        for r in results:
            if r.boxes.id is None:
                continue

            for box, track_id in zip(r.boxes, r.boxes.id.tolist()):
                track_id = int(track_id)
                cls_id   = int(box.cls[0])
                conf_v   = float(box.conf[0])
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                color    = COLORS[cls_id]
                box_pos  = [x1, y1, x2, y2]

                if track_id not in id_mapping:
                    nomor_cocok = None
                    jarak_min   = float("inf")
                    for nomor, pos in posisi_terakhir.items():
                        jarak = hitung_jarak(box_pos, pos)
                        if jarak < jarak_min:
                            jarak_min   = jarak
                            nomor_cocok = nomor
                    if nomor_cocok and jarak_min < JARAK_MAX:
                        id_mapping[track_id] = nomor_cocok
                    else:
                        id_mapping[track_id] = counter
                        counter += 1

                nomor = id_mapping[track_id]
                posisi_terakhir[nomor] = box_pos

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