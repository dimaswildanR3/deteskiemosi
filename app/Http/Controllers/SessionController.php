<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Session;
use App\Detection;
use App\Summary;
use App\FaceImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function start(Request $request)
    {
        $session = Session::create([
            'nama_kelas' => $request->nama_kelas,
            'dosen' => $request->dosen,
            'waktu_mulai' => now(),
            'waktu_selesai' => now(),
            'total_mahasiswa' => $request->total_mahasiswa
        ]);

        Summary::create([
            'session_id' => $session->id,
            'total_positif' => 0,
            'total_negatif' => 0,
            'persen_positif' => 0,
            'persen_negatif' => 0
        ]);

        return response()->json($session);
    }

    /*
    =========================
    🔥 SINGLE STORE FUNCTION (INTI SISTEM)
    =========================
    */
    public function store(Request $request)
    {
        // 1. simpan detection
        Detection::create([
            'session_id' => $request->session_id,
            'nomor_mahasiswa' => $request->nomor_mahasiswa,
            'label' => $request->label,
            'confidence' => $request->confidence,
            'timestamp' => now()
        ]);

        // 2. ambil summary (AMAN)
        $summary = Summary::firstOrCreate(
            ['session_id' => $request->session_id],
            [
                'total_positif' => 0,
                'total_negatif' => 0,
                'persen_positif' => 0,
                'persen_negatif' => 0
            ]
        );

        // 3. update counter
        if ($request->label == 'POSITIF') {
            $summary->total_positif++;
        } else {
            $summary->total_negatif++;
        }

        // 4. hitung persen
        $total = $summary->total_positif + $summary->total_negatif;

        if ($total > 0) {
            $summary->persen_positif = ($summary->total_positif / $total) * 100;
            $summary->persen_negatif = ($summary->total_negatif / $total) * 100;
        }

        $summary->save();

        return response()->json([
            'status' => 'ok'
        ]);
    }

    public function clearMyData()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
        $role = Auth::user()->role ?? '';
    
        // =========================
        // ADMIN = HAPUS SEMUA DATA
        // =========================
        if ($role == 'Admin') {
    
            Detection::truncate();
            Summary::truncate();
            FaceImage::truncate();
            Session::truncate();
    
        }
    
        // =========================
        // DOSEN = HANYA DATA SENDIRI
        // =========================
        elseif ($role == 'Dosen') {
    
            // ambil semua session milik dosen login
            $sessionIds = Session::where('user_id', Auth::id())
                ->pluck('id');
    
            // hapus child table dulu
            Detection::whereIn('session_id', $sessionIds)->delete();
    
            Summary::whereIn('session_id', $sessionIds)->delete();
    
            FaceImage::whereIn('session_id', $sessionIds)->delete();
    
            // hapus session
            Session::whereIn('id', $sessionIds)->delete();
        }
    
        // =========================
        // ROLE LAIN DITOLAK
        // =========================
        else {
    
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
            abort(403, 'Akses ditolak');
        }
    
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
        return redirect()->back()->with(
            'success',
            'Data berhasil dihapus'
        );
    }
}