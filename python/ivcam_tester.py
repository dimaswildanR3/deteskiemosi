import cv2
import numpy as np
from insightface.app import FaceAnalysis
from ultralytics import YOLO
import warnings
import os
import sys

# ==============================
# SILENT WARNING
# ==============================

os.environ["YOLO_VERBOSE"] = "False"
warnings.filterwarnings("ignore")

# ==============================
# CONFIG
# ==============================

EMOTION_MODEL = "best_v2.pt"

LABELS = [
    "POSITIF",
    "NEGATIF"
]

# ==============================
# VALIDASI ARGUMENT
# ==============================

if len(sys.argv) < 2:

    print("Path gambar tidak diberikan")

    exit()

image_path = sys.argv[1]

# ==============================
# LOAD IMAGE
# ==============================

frame = cv2.imread(image_path)

if frame is None:

    print("Gagal membaca gambar")

    exit()

# ==============================
# LOAD FACE DETECTION
# ==============================

app = FaceAnalysis(
    name="buffalo_sc",
    allowed_modules=["detection"]
)

# CPU MODE SERVER
app.prepare(
    ctx_id=-1,
    det_size=(640, 640)
)

# ==============================
# LOAD MODEL EMOSI
# ==============================

emotion_model = YOLO(EMOTION_MODEL)

# ==============================
# DETEKSI WAJAH
# ==============================

faces = app.get(frame)

if len(faces) == 0:

    print("Tidak ada wajah")

    exit()

# ==============================
# AMBIL WAJAH PERTAMA
# ==============================

face = faces[0]

x1, y1, x2, y2 = map(
    int,
    face.bbox
)

h_img, w_img, _ = frame.shape

# ==============================
# PADDING
# ==============================

w_box = x2 - x1

h_box = y2 - y1

pad_w = int(w_box * 0.40)

pad_h = int(h_box * 0.40)

x1_c = max(0, x1 - pad_w)

y1_c = max(0, y1 - pad_h)

x2_c = min(w_img, x2 + pad_w)

y2_c = min(h_img, y2 + pad_h)

crop = frame[
    y1_c:y2_c,
    x1_c:x2_c
]

if crop.size == 0:

    print("Crop wajah gagal")

    exit()

# ==============================
# PREDICT EMOSI
# ==============================

results = emotion_model.predict(
    crop,
    conf=0.01,
    verbose=False
)

final_label = "UNKNOWN"

final_conf = 0.0

for r in results:

    cls_id = None

    conf = 0.0

    if r.probs is not None:

        prob_list = r.probs.data.tolist()

        cls_id = int(np.argmax(prob_list))

        conf = float(prob_list[cls_id])

    elif (
        r.boxes is not None and
        len(r.boxes) > 0
    ):

        idx_max = int(
            np.argmax(
                r.boxes.conf.cpu().numpy()
            )
        )

        cls_id = int(
            r.boxes[idx_max].cls[0]
        )

        conf = float(
            r.boxes[idx_max].conf[0]
        )

    if cls_id is None:
        continue

    # ==============================
    # NORMALISASI
    # ==============================

    if cls_id == 0:

        score_positif = conf

    else:

        score_positif = 1.0 - conf

    if score_positif >= 0.32:

        final_id = 0

        final_conf = (
            0.65 +
            (
                (score_positif - 0.32)
                /
                (0.50 - 0.32)
            )
            *
            (0.95 - 0.65)
        )

    else:

        final_id = 1

        score_negatif = 1.0 - score_positif

        final_conf = (
            0.65 +
            (
                (score_negatif - 0.68)
                /
                (1.0 - 0.68)
            )
            *
            (0.95 - 0.65)
        )

    final_conf = min(
        0.95,
        max(0.65, final_conf)
    )

    final_label = LABELS[final_id]

# ==============================
# OUTPUT KE LARAVEL
# ==============================

print(
    f"{final_label} ({final_conf:.0%})"
)