<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Deteksi Emosi Real-time</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-2xl border border-slate-200">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Sistem Deteksi Emosi</h1>
            <p class="text-sm text-slate-500 mt-1">Menggunakan integrasi Laravel + OpenCV Python</p>
        </div>

        <div class="relative overflow-hidden rounded-xl bg-slate-900 aspect-video flex items-center justify-center border-2 border-slate-300 shadow-inner">
            <video id="webcam" autoplay playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
            
            <canvas id="canvas" width="640" height="480" class="hidden"></canvas>
            
            <div id="loading-overlay" class="absolute inset-0 bg-slate-900 flex flex-col items-center justify-center text-white p-4 text-center">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent mb-3"></div>
                <p class="text-sm font-medium">Meminta izin akses kamera...</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Kamera & Server</span>
                <p id="status-text" class="text-sm font-medium text-amber-600 mt-1">Menginisialisasi...</p>
            </div>

            <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 flex flex-col justify-between">
                <span class="text-xs font-semibold text-blue-400 uppercase tracking-wider">Hasil Deteksi Emosi</span>
                <p id="hasil-deteksi" class="text-xl font-bold text-blue-700 mt-1">-</p>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const statusText = document.getElementById('status-text');
        const hasilDeteksi = document.getElementById('hasil-deteksi');
        const loadingOverlay = document.getElementById('loading-overlay');

        // Mengambil Route POST dari Laravel secara dinamis
        const SERVER_URL = "{{ route('deteksi.proses') }}"; 

        // 1. Membuka Webcam Pengunjung
        async function aktifkanKamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 }, 
                        height: { ideal: 480 },
                        facingMode: "user" // Mengutamakan kamera depan jika buka lewat HP
                    } 
                });
                
                video.srcObject = stream;
                
                // Hilangkan overlay loading setelah video berhasil dialirkan
                video.onloadedmetadata = () => {
                    loadingOverlay.classList.add('hidden');
                    statusText.innerText = "Kamera Aktif & Mengirim Frame...";
                    statusText.className = "text-sm font-medium text-green-600 mt-1";
                    
                    // Mulai otomatis mengambil & mengirim gambar setiap 1000ms (1 detik)
                    setInterval(captureDanKirim, 1000);
                };

            } catch (err) {
                console.error("Gagal meminjam kamera:", err);
                loadingOverlay.innerHTML = `
                    <p class="text-red-400 font-semibold">Gagal Mengakses Kamera</p>
                    <p class="text-xs text-slate-400 mt-2 max-w-xs">Pastikan Anda mengizinkan akses kamera dan website berjalan di localhost atau protokol HTTPS.</p>
                `;
                statusText.innerText = "Akses kamera ditolak/gagal.";
                statusText.className = "text-sm font-medium text-red-600 mt-1";
            }
        }

        // 2. Mengambil Frame Gambar & Mengirimkannya ke Laravel via AJAX Fetch
        function captureDanKirim() {
            // Gambar posisi frame video saat ini ke dalam canvas tersembunyi
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Konversi canvas menjadi file Blob format JPEG
            canvas.toBlob((blob) => {
                if (!blob) return;

                // Bungkus blob ke dalam object FormData agar terbaca sebagai request upload file
                const formData = new FormData();
                formData.append('image', blob, 'frame.jpg');
                
                // WAJIB menyertakan CSRF Token bawaan Laravel agar terhindar dari Error 419 Page Expired
                formData.append('_token', '{{ csrf_token() }}'); 

                // Kirim data secara async (background) ke server
                fetch(SERVER_URL, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Terjadi kesalahan pada respon server.');
                    }
                    return response.json();
                })
                .then(data => {
                    // Update tampilan hasil emosi dengan output yang diprint oleh Python
                    if (data.status === 'success' && data.emosi) {
                        hasilDeteksi.innerText = data.emosi;
                    } else {
                        hasilDeteksi.innerText = "Gagal mendeteksi";
                    }
                })
                .catch(error => {
                    console.error('Error saat mengirim ke Laravel:', error);
                    statusText.innerText = "Koneksi ke server bermasalah...";
                    statusText.className = "text-sm font-medium text-red-600 mt-1";
                });

            }, 'image/jpeg', 0.6); // Kualitas kompresi diset ke 0.6 (60%) agar ukuran file kecil dan transfer cepat
        }

        // Jalankan fungsi aktifkanKamera sesaat setelah struktur halaman selesai dimuat sepenuhnya
        window.addEventListener("DOMContentLoaded", aktifkanKamera);
    </script>

</body>
</html>