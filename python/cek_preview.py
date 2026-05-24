import cv2

# Coba semua index dengan DSHOW
for INDEX in [0, 1, 2, 3]:
    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)
    ret, frame = cap.read()
    if ret and frame is not None:
        print(f"✅ Index {INDEX} berhasil baca frame!")
        cv2.imshow(f"Preview index {INDEX}", frame)
        cv2.waitKey(3000)  # tampil 3 detik
        cv2.destroyAllWindows()
    else:
        print(f"❌ Index {INDEX} gagal")
    cap.release()