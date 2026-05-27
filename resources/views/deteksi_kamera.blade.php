<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Kamera Laravel + Python</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-xl">

        <h1 class="text-3xl font-bold text-slate-800 mb-2">
            Test Kamera Laravel + Python
        </h1>

        <p class="text-slate-500 mb-6">
            Menjalankan <b>cek_kamera.py</b> dari Laravel
        </p>

        <button
            id="btn-test"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl transition"
        >
            Jalankan Python Kamera
        </button>

        <div class="mt-6">

            <p class="font-semibold text-slate-700 mb-2">
                Status:
            </p>

            <div
                id="status"
                class="bg-slate-100 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 whitespace-pre-wrap"
            >
                Belum dijalankan...
            </div>

        </div>

    </div>

    <script>

        /*
        |--------------------------------------------------------------------------
        | ELEMENT
        |--------------------------------------------------------------------------
        */

        const btnTest = document.getElementById('btn-test');

        const statusBox = document.getElementById('status');

        /*
        |--------------------------------------------------------------------------
        | URL CONTROLLER
        |--------------------------------------------------------------------------
        */

        const SERVER_URL = "{{ route('deteksi.proses') }}";

        /*
        |--------------------------------------------------------------------------
        | BUTTON CLICK
        |--------------------------------------------------------------------------
        */

        btnTest.addEventListener('click', async () => {

            statusBox.innerHTML = 'Menjalankan Python...';

            btnTest.disabled = true;

            try {

                const formData = new FormData();

                formData.append(
                    '_token',
                    document.querySelector('meta[name="csrf-token"]').content
                );

                const response = await fetch(SERVER_URL, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                console.log(data);

                if (data.status === 'success') {

                    statusBox.innerHTML =
                        'PYTHON BERHASIL DIJALANKAN\n\n' +
                        data.output_python;

                } else {

                    statusBox.innerHTML =
                        'ERROR\n\n' +
                        data.message;
                }

            } catch (error) {

                console.error(error);

                statusBox.innerHTML =
                    'ERROR FETCH\n\n' +
                    error.message;

            } finally {

                btnTest.disabled = false;
            }

        });

    </script>

</body>

</html>