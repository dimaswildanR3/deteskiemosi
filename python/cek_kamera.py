import cv2

def find_ivcam():
    print("🔍 Mencari iVCam...")

    for i in range(10):
        cap = cv2.VideoCapture(i)
        ret, frame = cap.read()

        if ret:

            backend = cap.getBackendName()
            print(f"Camera {i} detected: {backend}")

            # heuristik: biasanya iVCam bukan index 0
            # jadi kita skip kemungkinan camera internal
            if i != 0:
                print(f"✅ iVCam kemungkinan di index: {i}")
                cap.release()
                return i

        cap.release()

    print("⚠️ Tidak ketemu iVCam, fallback ke 0")
    return 0