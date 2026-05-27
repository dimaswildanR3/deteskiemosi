from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import cv2
from ultralytics import YOLO

app = Flask(__name__)
CORS(app)

# load model
model = YOLO("best_v2.pt")


@app.route("/api/detect", methods=["POST"])
def detect():

    if "image" not in request.files:
        return jsonify({
            "error": "No image provided"
        }), 400

    file = request.files["image"]

    # decode image
    npimg = np.frombuffer(file.read(), np.uint8)
    frame = cv2.imdecode(npimg, cv2.IMREAD_COLOR)

    if frame is None:
        return jsonify({
            "error": "Invalid image"
        }), 400

    results = model.predict(frame, conf=0.25, verbose=False)

    label = "NEGATIF"
    confidence = 0.0

    for r in results:

        # =========================
        # CASE 1: CLASSIFICATION MODEL
        # =========================
        if r.probs is not None:
            probs = r.probs.data.tolist()
            idx = int(np.argmax(probs))
            confidence = float(probs[idx])

            label = "POSITIF" if idx == 0 else "NEGATIF"

        # =========================
        # CASE 2: DETECTION MODEL (lebih umum YOLO)
        # =========================
        elif r.boxes is not None and len(r.boxes) > 0:

            confs = r.boxes.conf.cpu().numpy()
            clss = r.boxes.cls.cpu().numpy()

            best_idx = int(np.argmax(confs))

            confidence = float(confs[best_idx])
            cls_id = int(clss[best_idx])

            label = "POSITIF" if cls_id == 0 else "NEGATIF"

    return jsonify({
        "label": label,
        "confidence": round(confidence, 3)
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=True)