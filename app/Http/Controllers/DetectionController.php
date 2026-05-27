<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        return view('deteksi_kamera');
    }

    public function prosesDeteksi(Request $request)
    {
        set_time_limit(0);
    
        try {
    
            /*
            |--------------------------------------------------------------------------
            | PATH PYTHON
            |--------------------------------------------------------------------------
            */
    
            $pythonEnv =
                '/Users/dimaswildanalfurqaan/Documents/untitled folder 2/Laravel---SB-Admin-2---Fortify/python/venv/bin/python';
    
            /*
            |--------------------------------------------------------------------------
            | FILE ivcam_tester.py
            |--------------------------------------------------------------------------
            */
    
            $scriptPython =
                '/Users/dimaswildanalfurqaan/Documents/untitled folder 2/Laravel---SB-Admin-2---Fortify/python/ivcam_tester.py';
    
            /*
            |--------------------------------------------------------------------------
            | DIRECTORY PYTHON
            |--------------------------------------------------------------------------
            */
    
            $dirPython =
                '/Users/dimaswildanalfurqaan/Documents/untitled folder 2/Laravel---SB-Admin-2---Fortify/python';
    
            /*
            |--------------------------------------------------------------------------
            | VALIDASI
            |--------------------------------------------------------------------------
            */
    
            if (!file_exists($pythonEnv)) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'Python tidak ditemukan'
                ]);
            }
    
            if (!file_exists($scriptPython)) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'ivcam_tester.py tidak ditemukan'
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | COMMAND
            |--------------------------------------------------------------------------
            */
    
            $command =
                'cd ' . escapeshellarg($dirPython) .
                ' && ' .
                escapeshellarg($pythonEnv) .
                ' ' .
                escapeshellarg($scriptPython) .
                ' 2>&1';
    
            Log::info("COMMAND PYTHON", [
                'command' => $command
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | RUN PYTHON
            |--------------------------------------------------------------------------
            */
    
            $output = shell_exec($command);
    
            Log::info("OUTPUT PYTHON", [
                'output' => $output
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */
    
            return response()->json([
    
                'status' => 'success',
    
                'output_python' => trim($output)
    
            ]);
    
        } catch (\Throwable $e) {
    
            Log::error("ERROR PYTHON", [
    
                'message' => $e->getMessage()
    
            ]);
    
            return response()->json([
    
                'status' => 'error',
    
                'message' => $e->getMessage()
    
            ], 500);
        }
    }
}