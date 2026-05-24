<?php

namespace App\Http\Controllers;

use App\User;
use App\Yolo;
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
    
        if ($user->role == 'Dosen') {
    
            $classes = ClassModel::where('dosen_id', $user->id)->get();
    
            $query = Yolo::whereHas('class', function ($q) use ($user) {
                $q->where('dosen_id', $user->id);
            });
    
        } else {
    
            $classes = ClassModel::all();
            $query = Yolo::query();
        }
    
        // =========================
        // SESSION TERAKHIR (optional widget)
        // =========================
        $session = (clone $query)->latest()->first();
    
        // =========================
        // TIMELINE FIX (INI KUNCI UTAMA)
        // =========================
        $timeline = (clone $query)
            ->orderBy('created_at')
            ->limit(20)
            ->get();
    
        $timeline_labels = $timeline->map(function ($item) {
            return $item->created_at->format('H:i:s');
        });
    
        /*
        🔥 FIX PENTING:
        jangan pakai avg_sentiment (biasanya stabil / summary)
        tapi pakai SELISIH POSITIVE - NEGATIVE biar naik turun
        */
        $timeline_values = $timeline->map(function ($item) {
            return (float) ($item->positive_rate - $item->negative_rate);
        });
    
        // =========================
        // WIDGET
        // =========================
        $widget = [
            'users' => ($user->role == 'Admin') ? User::count() : 0,
            'classes' => $classes->count(),
    
            'monitoring' => (clone $query)->sum('total_captures'),
    
            'positive_rate' => round((clone $query)->avg('positive_rate') ?? 0, 2),
    
            'negative_rate' => round((clone $query)->avg('negative_rate') ?? 0, 2),
    
            'avg_sentiment' => round((clone $query)->avg('avg_sentiment') ?? 0, 2),
    
            // TIMELINE
            'timeline_labels' => $timeline_labels,
            'timeline_values' => $timeline_values,
        ];
    
        return view('home', compact('widget', 'classes'));
    }

    /**
     * Detail Monitoring YOLO
     */
    public function view($id)
    {
        // =========================
        // AMBIL DATA YOLO BY ID
        // =========================
        $session = Yolo::findOrFail($id);
    
        // ambil class (kalau relasi ada)
        $classes = ClassModel::where('id', $session->class_id)->get();
    
        // =========================
        // BASE QUERY (HANYA 1 SESSION)
        // =========================
        $query = Yolo::where('id', $id);
    
        // =========================
        // WIDGET
        // =========================
        $widget = [
            'classes' => $classes->count(),
    
            'monitoring' => $session->total_captures,
    
            'positive_rate' => round($session->positive_rate ?? 0, 2),
    
            'negative_rate' => round($session->negative_rate ?? 0, 2),
    
            'avg_sentiment' => round($session->avg_sentiment ?? 0, 2),
        ];
    
        // =========================
        // TIMELINE (FIX REAL)
        // =========================
        // kalau hanya 1 session → kita pecah jadi fake timeline dari perubahan kecil
        $timeline = Yolo::where('class_id', $session->class_id)
            ->orderBy('created_at', 'asc')
            ->limit(30)
            ->get();
    
        $widget['timeline_labels'] = $timeline->map(function ($item) {
            return $item->created_at->format('H:i:s');
        })->values();
    
        $widget['timeline_values'] = $timeline->map(function ($item) {
            return (float) $item->avg_sentiment;
        })->values();
    
        return view('monitoring/view', compact('widget', 'classes', 'session'));
    }
}