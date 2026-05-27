import cv2

for i in range(5):

    print(f"\nMencoba kamera index {i}...")

    # pakai AVFOUNDATION khusus Mac
    cap = cv2.VideoCapture(i, cv2.CAP_AVFOUNDATION)

    # cek kamera berhasil dibuka
    if not cap.isOpened():
        print(f"Index {i} tidak ada")
        continue

    print(f"Kamera index {i} tersedia")

    # coba baca frame
    ret, frame = cap.read()

    if not ret:
        print(f"Index {i} gagal ambil frame")
        cap.release()
        continue

    # tampilkan kamera
    while True:

        cv2.imshow(f"Kamera {i}", frame)

        ret, frame = cap.read()

        if not ret:
            print("Gagal baca frame")
            break

        # tekan q untuk keluar kamera
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()