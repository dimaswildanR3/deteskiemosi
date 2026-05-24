import cv2

for i in range(5):
    cap = cv2.VideoCapture(i)
    if cap.isOpened():
        print(f"Kamera index {i} tersedia")
        cap.release()
    else:
        print(f"Index {i} tidak ada")