<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetectionController extends Controller
{
    public function start(Request $request)
    {
        $pythonPath = "/home/deteksie/virtualenv/repositories/deteskiemosi/python/3.11/bin/python";

        $scriptPath = "/home/deteksie/repositories/deteskiemosi/python/ivcam_tester.py";

        $workingDir = "/home/deteksie/repositories/deteskiemosi/python";

        $command = "cd $workingDir && $pythonPath $scriptPath 2>&1";

        $output = shell_exec($command);

        return response()->json([
            'status' => 'ok',
            'output' => $output
        ]);
    }


    public function index()
    {
        // Menampilkan halaman utama tempat kamera berada
        return view('deteksi_kamera'); // Buat file deteksi_kamera.blade.php di resources/views
    }

    public function prosesDeteksi(Request $request)
    {
        // 1. Validasi apakah ada file gambar yang dikirim
        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'Tidak ada gambar yang diterima'], 400);
        }

        // 2. Ambil file gambar dari browser
        $file = $request->file('image');
        
        // Simpan sementara di folder storage Laravel agar bisa dibaca oleh Python
        $tempPath = $file->store('temp_frames', 'local');
        $fullPathGambar = storage_path('app/' . $tempPath);

        // 3. Tentukan lokasi PATH Python Virtual Environment cPanel Anda dan script Python-nya
        // Sesuaikan dengan letak folder repositori Anda yang di screenshot tadi
        $pythonEnv = '/home/deteksie/virtualenv/repositories/deteskiemosi/pyth/3.11/bin/python'; 
        $scriptPython = '/home/deteksie/repositories/deteskiemosi/pyth/cek_kamera.php_atau_apapun.py';

        // 4. Jalankan script Python lewat terminal Server dengan mengirimkan path gambarnya sebagai argumen
        // Perintah di terminal akan seperti: /path/python /path/script.py /path/gambar.jpg
        $command = escapeshellcmd("$pythonEnv $scriptPython " . escapeshellarg($fullPathGambar));
        $output = shell_exec($command);

        // 5. Hapus file gambar temporary tadi demi menghemat penyimpanan server
        if (file_exists($fullPathGambar)) {
            unlink($fullPathGambar);
        }

        // 6. Kembalikan hasil output dari Python (yang sudah berupa teks/JSON) ke browser
        // Asumsi script Python Anda melakukan print() teks emosi
        return response()->json([
            'status' => 'success',
            'emosi' => trim($output) // trim untuk menghapus spasi/baris baru bawaan terminal
        ]);
    }
}