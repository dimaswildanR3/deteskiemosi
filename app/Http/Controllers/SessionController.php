<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Session;
use App\Detection;
use App\Summary;
use App\FaceImage;
use App\Yolo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;

class SessionController extends Controller
{
    public function start(Request $request)
    {
        $session = Session::create([
            'nama_kelas' => $request->nama_kelas,
            'dosen' => $request->dosen,
            'waktu_mulai' => now(),

            'total_mahasiswa' => $request->total_mahasiswa
        ]);

        Summary::create([
            'session_id' => $session->id,
            'total_positif' => 0,
            'total_negatif' => 0,
            'persen_positif' => 0,
            'persen_negatif' => 0
        ]);
        return response()->json([
            'status' => 'ok',
            'session_id' => $session->id
        ]);
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
    
        if ($request->hasFile('image')) {

            $file = $request->file('image');
        
            $filename = uniqid().'.jpg';
        
            // 🔥 FIX CPANEL PATH
            $destination = $_SERVER['DOCUMENT_ROOT'].'/uploads/face_images';
        
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
        
            $file->move($destination, $filename);
        
            $path = 'uploads/face_images/'.$filename;
        
            FaceImage::create([
                'session_id' => $request->session_id,
                'nomor_mahasiswa' => $request->nomor_mahasiswa,
                'label' => $request->label,
                'file_path' => $path
            ]);
        }
    
        // 3. ambil summary
        $summary = Summary::firstOrCreate(
            ['session_id' => $request->session_id],
            [
                'total_positif' => 0,
                'total_negatif' => 0,
                'persen_positif' => 0,
                'persen_negatif' => 0
            ]
        );
    
        // 4. update counter
        if ($request->label == 'POSITIF') {
            $summary->total_positif++;
        } else {
            $summary->total_negatif++;
        }
    
        // 5. hitung persen
        $total = $summary->total_positif + $summary->total_negatif;
    
        if ($total > 0) {
            $summary->persen_positif =
                ($summary->total_positif / $total) * 100;
    
            $summary->persen_negatif =
                ($summary->total_negatif / $total) * 100;
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
    
            // Detection::truncate();
            // Summary::truncate();
            FaceImage::truncate();
            // Session::truncate();
    
        }
    
        // =========================
        // DOSEN = HANYA DATA SENDIRI
        // =========================
        elseif ($role == 'Dosen') {
    
            // ambil semua session milik dosen login
            $sessionIds = Session::where('user_id', Auth::id())
                ->pluck('id');
    
            // hapus child table dulu
            // Detection::whereIn('session_id', $sessionIds)->delete();
    
            // Summary::whereIn('session_id', $sessionIds)->delete();
    
            FaceImage::whereIn('session_id', $sessionIds)->delete();
    
            // hapus session
            // Session::whereIn('id', $sessionIds)->delete();
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

    public function report()
{
    $role = Auth::user()->role ?? '';

    // =========================
    // ADMIN = SEMUA DATA
    // =========================
    if ($role == 'Admin') {

        $sessions = Session::latest()->get();

        $detections = Detection::orderBy('timestamp', 'DESC')->get();

        $summaries = Summary::latest()->get();

        $faceImages = FaceImage::latest()->get();
    }

    // =========================
    // DOSEN = DATA SENDIRI
    // =========================
    elseif ($role == 'Dosen') {

        $sessionIds = Session::where('user_id', Auth::id())
            ->pluck('id');

        $sessions = Session::whereIn('id', $sessionIds)
            ->latest()
            ->get();

        $detections = Detection::whereIn('session_id', $sessionIds)
            ->latest()
            ->get();

        $summaries = Summary::whereIn('session_id', $sessionIds)
            ->latest()
            ->get();

        $faceImages = FaceImage::whereIn('session_id', $sessionIds)
            ->latest()
            ->get();
    }

    // =========================
    // ROLE LAIN DITOLAK
    // =========================
    else {

        abort(403, 'Akses ditolak');
    }

    return view(
        'monitoring.report',
        compact(
            'sessions',
            'detections',
            'summaries',
            'faceImages'
        )
    );
}
public function exportExcel()
{
    $role = Auth::user()->role ?? '';

    // =========================
    // ADMIN = SEMUA DATA
    // =========================
    if ($role == 'Admin') {

        $sessions = Session::all();

        $detections = Detection::orderBy(
            'timestamp',
            'DESC'
        )->get();

        $summaries = Summary::all();

        $faceImages = FaceImage::all();
    }

    // =========================
    // DOSEN = DATA SENDIRI
    // =========================
    elseif ($role == 'Dosen') {

        $sessionIds = Session::where(
            'user_id',
            Auth::id()
        )->pluck('id');

        $sessions = Session::whereIn(
            'id',
            $sessionIds
        )->get();

        $detections = Detection::whereIn(
            'session_id',
            $sessionIds
        )
        ->orderBy('timestamp', 'DESC')
        ->get();

        $summaries = Summary::whereIn(
            'session_id',
            $sessionIds
        )->get();

        $faceImages = FaceImage::whereIn(
            'session_id',
            $sessionIds
        )->get();
    }

    // =========================
    // ROLE LAIN DITOLAK
    // =========================
    else {

        abort(403, 'Akses ditolak');
    }

    $data = [];

    /*
    |--------------------------------------------------------------------------
    | DATA SESSION
    |--------------------------------------------------------------------------
    */

    $data[] = ['DATA SESSION'];

    $data[] = [
        'ID',
        'KELAS',
        'DOSEN',
        'WAKTU MULAI',
        'WAKTU SELESAI',
        'TOTAL MAHASISWA'
    ];

    foreach ($sessions as $s) {

        $data[] = [
            $s->id,
            $s->nama_kelas,
            $s->dosen,
            $s->waktu_mulai,
            $s->waktu_selesai,
            $s->total_mahasiswa
        ];
    }

    $data[] = [];
    $data[] = [];

    /*
    |--------------------------------------------------------------------------
    | DATA SUMMARY
    |--------------------------------------------------------------------------
    */

    $data[] = ['DATA SUMMARY'];

    $data[] = [
        'SESSION ID',
        'TOTAL POSITIF',
        'TOTAL NEGATIF',
        'PERSEN POSITIF',
        'PERSEN NEGATIF'
    ];

    foreach ($summaries as $s) {

        $data[] = [
            $s->session_id,
            $s->total_positif,
            $s->total_negatif,
            $s->persen_positif,
            $s->persen_negatif
        ];
    }

    $data[] = [];
    $data[] = [];

    /*
    |--------------------------------------------------------------------------
    | DATA DETECTION
    |--------------------------------------------------------------------------
    */

    $data[] = ['DATA DETECTION'];

    $data[] = [
        'ID',
        'SESSION ID',
        'NOMOR MAHASISWA',
        'LABEL',
        'CONFIDENCE',
        'TIMESTAMP'
    ];

    foreach ($detections as $d) {

        $data[] = [
            $d->id,
            $d->session_id,
            $d->nomor_mahasiswa,
            $d->label,
            $d->confidence,
            $d->timestamp
        ];
    }

    $data[] = [];
    $data[] = [];

    /*
    |--------------------------------------------------------------------------
    | DATA FACE IMAGE
    |--------------------------------------------------------------------------
    */

    $data[] = ['DATA FACE IMAGE'];

    $data[] = [
        'ID',
        'SESSION ID',
        'NOMOR MAHASISWA',
        'LABEL',
        'FILE PATH'
    ];

    foreach ($faceImages as $f) {

        $data[] = [
            $f->id,
            $f->session_id,
            $f->nomor_mahasiswa,
            $f->label,
            $f->file_path
        ];
    }

    return Excel::download(

        new class($data)
        implements \Maatwebsite\Excel\Concerns\FromArray {

            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function array(): array
            {
                return $this->data;
            }
        },

        'laporan_monitoring.xlsx'
    );
}

public function stop(Request $request)
{
    // 1. ambil session dari request (WAJIB lebih aman)
    $session = Session::find($request->session_id);

    if (!$session) {
        return response()->json([
            'status' => 'error',
            'message' => 'Session tidak ditemukan'
        ], 404);
    }
    $session->update([
        'waktu_selesai' => now()
    ]);
    // 2. ambil summary
    $summary = Summary::where('session_id', $session->id)->first();

    if (!$summary) {
        return response()->json([
            'status' => 'error',
            'message' => 'Summary tidak ditemukan'
        ], 404);
    }

    // 3. hitung total capture (dari detection)
    $totalCaptures = \App\Detection::where('session_id', $session->id)->count();

    // 4. insert ke tabel YOLO
    $yolo = \App\Yolo::create([
        'user_id'         => $session->dosen ?? null,
        'class_id'        => $session->nama_kelas ?? null,
        'session_name'    => $session->nama_kelas,
        'total_captures'  => $totalCaptures,
        'positive_rate'   => $summary->persen_positif ?? 0,
        'negative_rate'   => $summary->persen_negatif ?? 0,
        'avg_sentiment'   => $this->calculateAvgSentiment($session->id),
        'started_at'      => $session->waktu_mulai,
        'ended_at'        => now(),
    ]);

    return response()->json([
        'status' => 'ok',
        'message' => 'YOLO report saved',
        'data' => $yolo
    ]);
}
private function calculateAvgSentiment($sessionId)
{
    $detections = \App\Detection::where('session_id', $sessionId)->get();

    if ($detections->count() == 0) {
        return 0;
    }

    $score = 0;

    foreach ($detections as $d) {
        if ($d->label == 'POSITIF') {
            $score += 1;
        } else {
            $score -= 1;
        }
    }

    return round($score / $detections->count(), 2);
}
}