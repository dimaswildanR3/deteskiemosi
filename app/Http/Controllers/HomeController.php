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
        /*
        =========================
        DOSEN
        =========================
        */
        if (Auth::user()->role == 'Dosen') {

            $classes = ClassModel::where(
                'dosen_id',
                Auth::id()
            )->count();

            $monitoring = Yolo::whereHas(
                'class',
                function ($q) {

                    $q->where(
                        'dosen_id',
                        Auth::id()
                    );

                }
            )->count();

            $widget = [

                'users' => 0,

                'classes' => $classes,

                'monitoring' => $monitoring,

                'monitoring_today' => Yolo::whereDate(
                    'created_at',
                    now()->toDateString()
                )
                ->whereHas('class', function ($q) {

                    $q->where(
                        'dosen_id',
                        Auth::id()
                    );

                })->count()

            ];

        } else {

            /*
            =========================
            ADMIN
            =========================
            */

            $widget = [

                'users' => User::count(),

                'admin' => User::where(
                    'role',
                    'Admin'
                )->count(),

                'dosen' => User::where(
                    'role',
                    'Dosen'
                )->count(),

                'mahasiswa' => User::where(
                    'role',
                    'User'
                )->count(),

                'classes' => ClassModel::count(),

                'monitoring' => Yolo::count(),

                'monitoring_today' => Yolo::whereDate(
                    'created_at',
                    now()->toDateString()
                )->count()

            ];
        }

        return view(
            'home',
            compact('widget')
        );
    }

    /**
     * Detail Monitoring YOLO
     */
    // public function view($id)
    // {
    //     $query = Yolo::with('class', 'user');

    //     /*
    //     =========================
    //     DOSEN
    //     hanya bisa lihat miliknya
    //     =========================
    //     */
    //     if (Auth::user()->role == 'Dosen') {

    //         $query->whereHas('class', function ($q) {

    //             $q->where(
    //                 'dosen_id',
    //                 Auth::id()
    //             );

    //         });

    //     }

    //     $session = $query->findOrFail($id);

    //     return view(
    //         'monitoring.view',
    //         compact('session')
    //     );
    // }
}