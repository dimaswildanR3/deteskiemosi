<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index()
    {
        // dummy data tabel
        $data = [
            [
                'id' => 1,
                'nama' => 'Kelas A',
                'tanggal' => '2026-04-01',
                'positive' => '20%',
                'negative' => '80%',
                'status' => 'Selesai'
            ],
            [
                'id' => 2,
                'nama' => 'Kelas B',
                'tanggal' => '2026-04-02',
                'positive' => '50%',
                'negative' => '50%',
                'status' => 'Selesai'
            ],
            [
                'id' => 3,
                'nama' =>'Kelas C',
                'tanggal' => '2026-04-03',
                'positive' => '60%',
                'negative' => '40%',
                'status' => 'Proses'
            ],
        ];

        return view('monitoring.index', compact('data'));
    }

    public function view($id)
    {
        // sementara redirect ke dashboard
        return redirect()->route('home');
    }
}