<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Deteksi Emosi Laravel + Python</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-2xl">

        <h1 class="text-3xl font-bold text-slate-800 mb-2">
            Deteksi Emosi
        </h1>

        <p class="text-slate-500 mb-6">
            Laravel + Python + OpenCV + YOLO
        </p>

        <!-- VIDEO -->
        <div class="overflow-hidden rounded-2xl border border-slate-300 bg-black">

            <video
                id="video"
                autoplay
                playsinline
                class="w-full h-auto"
            ></video>

        </div>

        <!-- BUTTON -->
        <div class="mt-6 flex gap-3">

            <button
                id="btn-start"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold"
            >
                Aktifkan Kamera
            </button>

            <button
                id="btn-detect"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold"
            >
                Kirim ke Python
            </button>

        </div>

        <!-- STATUS -->
        <div class="mt-6">

            <div
                id="status"
                class="bg-slate-100 border border-slate-200 rounded-xl p-4 text-sm whitespace-pre-wrap"
            >
                Menunggu...
            </div>

        </div>

        <!-- CANVAS HIDDEN -->
        <canvas
            id="canvas"
            width="640"
            height="480"
            class="hidden"
        ></canvas>

    </div>

    <script>

        /*
        |--------------------------------------------------------------------------
        | ELEMENT
        |--------------------------------------------------------------------------
        */

        const video =
            document.getElementById('video');

        const canvas =
            document.getElementById('canvas');

        const statusBox =
            document.getElementById('status');

        const btnStart =
            document.getElementById('btn-start');

        const btnDetect =
            document.getElementById('btn-detect');

        const ctx =
            canvas.getContext('2d');

        /*
        |--------------------------------------------------------------------------
        | ROUTE
        |--------------------------------------------------------------------------
        */

        const SERVER_URL =
            "{{ route('deteksi.proses') }}";

        /*
        |--------------------------------------------------------------------------
        | START CAMERA
        |--------------------------------------------------------------------------
        */

        btnStart.addEventListener(
            'click',
            async () => {

                try {

                    const stream =
                        await navigator.mediaDevices.getUserMedia({

                            video: true

                        });

                    video.srcObject = stream;

                    statusBox.innerHTML =
                        '✅ Kamera berhasil aktif';

                } catch (err) {

                    console.error(err);

                    statusBox.innerHTML =
                        '❌ Gagal membuka kamera';
                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | DETEKSI
        |--------------------------------------------------------------------------
        */

        btnDetect.addEventListener(
            'click',
            async () => {

                try {

                    statusBox.innerHTML =
                        '⏳ Mengirim gambar ke Python...';

                    /*
                    |--------------------------------------------------------------------------
                    | CAPTURE FRAME
                    |--------------------------------------------------------------------------
                    */

                    ctx.drawImage(
                        video,
                        0,
                        0,
                        canvas.width,
                        canvas.height
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | CANVAS TO BLOB
                    |--------------------------------------------------------------------------
                    */

                    canvas.toBlob(
                        async (blob) => {

                            const formData =
                                new FormData();

                            formData.append(
                                'image',
                                blob,
                                'frame.jpg'
                            );

                            formData.append(
                                '_token',
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                ).content
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | FETCH
                            |--------------------------------------------------------------------------
                            */

                            const response =
                                await fetch(
                                    SERVER_URL,
                                    {
                                        method: 'POST',
                                        body: formData
                                    }
                                );

                            const data =
                                await response.json();

                            console.log(data);

                            /*
                            |--------------------------------------------------------------------------
                            | RESULT
                            |--------------------------------------------------------------------------
                            */

                            if (data.status === 'success') {

                                statusBox.innerHTML =
                                    '✅ HASIL DETEKSI\n\n' +
                                    data.output_python;

                            } else {

                                statusBox.innerHTML =
                                    '❌ ERROR\n\n' +
                                    data.message;
                            }

                        },
                        'image/jpeg',
                        0.8
                    );

                } catch (err) {

                    console.error(err);

                    statusBox.innerHTML =
                        '❌ ERROR FETCH\n\n' +
                        err.message;
                }

            }
        );

    </script>

</body>

</html>