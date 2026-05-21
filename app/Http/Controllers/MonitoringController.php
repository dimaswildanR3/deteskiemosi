<?php

namespace App\Http\Controllers;

use App\Yolo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    public function index()
    {
        $query = Yolo::with('class', 'user');

        // kalau dosen hanya lihat miliknya
        if (Auth::user()->role == 'Dosen') {

            $query->whereHas('class', function ($q) {

                $q->where('dosen_id', Auth::id());

            });

        }

        // ambil terbaru
        $sessions = $query
            ->latest()
            ->get()
            ->groupBy(function ($item) {

                return \Carbon\Carbon::parse(
                    $item->created_at
                )->format('Y-m-d');

            });

        return view('monitoring.index', compact('sessions'));
    }

    public function view($id)
    {
        $query = Yolo::with('class', 'user');

        // Proteksi dosen
        if (Auth::user()->role == 'Dosen') {

            $query->whereHas('class', function ($q) {

                $q->where('dosen_id', Auth::id());

            });

        }

        $session = $query->findOrFail($id);

        return view('monitoring.view', compact('session'));
    }
    /*
    API CREATE MONITORING
    dipakai realtime AI / python
    */
    public function storeApi(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'session_name' => 'required',
            'total_captures' => 'required',
            'positive_rate' => 'required',
            'avg_sentiment' => 'required'
        ]);

        $session = Yolo::create([

            'user_id' => Auth::id(),

            'class_id' => $request->class_id,

            'session_name' => $request->session_name,

            'total_captures' => $request->total_captures,

            'positive_rate' => $request->positive_rate,

            'avg_sentiment' => $request->avg_sentiment,

            'started_at' => now(),

            'ended_at' => now()

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Monitoring saved',
            'data' => $session
        ]);
    }
}