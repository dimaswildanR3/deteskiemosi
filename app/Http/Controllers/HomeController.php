<?php

namespace App\Http\Controllers;

use App\User;
use App\Yolo;
use App\Session;
use App\Detection;
use App\FaceImage;
use App\Summary;
use Carbon\Carbon;
use App\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Dashboard
     */
    public function index()
{
    $user = Auth::user();
    $today = Carbon::today();
    if (!session()->has('dashboard_loaded')) {

        session(['dashboard_loaded' => true]);

        $classes = ($user->role == 'Dosen')
            ? ClassModel::where('dosen_id', $user->id)->get()
            : ClassModel::all();

        return view('home', [
            'widget' => [
                'users' => ($user->role == 'Admin') ? User::count() : 0,
                'classes' => 0,
                'monitoring' => 0,
                'positive_rate' => 0,
                'negative_rate' => 0,
                'avg_sentiment' => 0,
                'timeline_labels' => [],
                'timeline_values' => [],
                'latest_faces' => [],
            ],
            'classes' => $classes
        ]);
    }
    $classes = ($user->role == 'Dosen')
        ? ClassModel::where('dosen_id', $user->id)->get()
        : ClassModel::all();

    $sessionIds = Session::where('dosen', $user->id)
        ->whereDate('created_at', $today)
        ->pluck('id');

    $sessionQuery = Session::whereIn('id', $sessionIds);
    $detectionQuery = Detection::whereIn('session_id', $sessionIds);
    $summaryQuery = Summary::whereIn('session_id', $sessionIds);
    $faceQuery = FaceImage::whereIn('session_id', $sessionIds);

    $session = (clone $sessionQuery)->latest()->first();

    $monitoring = (clone $detectionQuery)->count();

    $summary = (clone $summaryQuery)->latest()->first();

    $positive = $summary->total_positif ?? 0;
    $negative = $summary->total_negatif ?? 0;

    $total = $positive + $negative;

    $positive_rate = $total ? round(($positive / $total) * 100, 2) : 0;
    $negative_rate = $total ? round(($negative / $total) * 100, 2) : 0;

    $timeline = (clone $detectionQuery)
        ->orderBy('timestamp', 'asc')
        ->limit(20)
        ->get();

    $timeline_labels = $timeline->map(function ($item) {
        return $item->timestamp
            ? Carbon::parse($item->timestamp)->format('H:i:s')
            : ($item->created_at ? $item->created_at->format('H:i:s') : '00:00:00');
    });

    $timeline_values = $timeline->map(function ($item) {
        return $item->label === 'POSITIF' ? 1 : -1;
    });

    $latest_faces = (clone $faceQuery)
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();

    $widget = [
        'users' => ($user->role == 'Admin') ? User::count() : 0,
        'classes' => $classes->count(),
        'monitoring' => $monitoring,
        'positive_rate' => $positive_rate,
        'negative_rate' => $negative_rate,
        'avg_sentiment' => ($positive_rate - $negative_rate),
        'timeline_labels' => $timeline_labels,
        'timeline_values' => $timeline_values,
        'latest_faces' => $latest_faces,
    ];

    return view('home', compact('widget', 'classes'));
}

    public function view($id)
    {
        // =========================
        // AMBIL DATA YOLO BY ID
        // =========================
        $session = Session::findOrFail($id);
    
        // ambil class (kalau relasi ada)
        $classes = ClassModel::where('id', $session->nama_kelas)->get();
    
        // =========================
        // BASE QUERY (HANYA 1 SESSION)
        // =========================
        $query = Session::where('id', $id);
        $summaryQuery = Summary::where('session_id', $session->id);
        $summary = (clone $summaryQuery)->latest()->first();
    
        $positive = $summary->total_positif ?? 0;
        $negative = $summary->total_negatif ?? 0;
    
        $total = $positive + $negative;
    
        $positive_rate = $total ? round(($positive / $total) * 100, 2) : 0;
        $negative_rate = $total ? round(($negative / $total) * 100, 2) : 0;
        // =========================
        // WIDGET
        // =========================
        $timeline = Detection::where('session_id', $session->id)
        ->orderBy('timestamp', 'asc')
        ->limit(30)
        ->get();

        $widget = [
            'classes' => $classes->count(),
    
            'monitoring' => $timeline->count(),
    
            'positive_rate' =>$positive_rate,
    
            'negative_rate' => $negative_rate,
    
            'avg_sentiment' =>($positive_rate - $negative_rate),
        ];
    
        // =========================
        // TIMELINE (FIX REAL)
        // =========================
        // kalau hanya 1 session → kita pecah jadi fake timeline dari perubahan kecil
        // $timeline = Session::where('nama_kelas', $session->nama_kelas)
        //     ->orderBy('created_at', 'asc')
        //     ->limit(30)
        //     ->get();
  
    
        $timeline = Detection::where('session_id', $session->id)
        ->orderBy('timestamp', 'asc')
        ->limit(30)
        ->get();
    
    $widget['timeline_labels'] = $timeline->map(function ($item) {
        return Carbon::parse($item->timestamp)->format('H:i:s');
    })->values();
    
    $widget['timeline_values'] = $timeline->map(function ($item) {
        return $item->label === 'POSITIF' ? 1 : -1;
    })->values();
    
        return view('monitoring/view', compact('widget', 'classes', 'session'));
    }
}