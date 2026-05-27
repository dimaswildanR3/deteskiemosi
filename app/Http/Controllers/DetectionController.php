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
            | PATH PYTHON SERVER CPANEL
            |--------------------------------------------------------------------------
            */
    
            $pythonEnv =
                '/home/deteksie/virtualenv/repositories/deteskiemosi/python/3.11/bin/python';
    
            /*
            |--------------------------------------------------------------------------
            | FILE PYTHON
            |--------------------------------------------------------------------------
            */
    
            $scriptPython =
                '/home/deteksie/repositories/deteskiemosi/python/ivcam_tester.py';
    
            /*
            |--------------------------------------------------------------------------
            | DIRECTORY PYTHON
            |--------------------------------------------------------------------------
            */
    
            $dirPython =
                '/home/deteksie/repositories/deteskiemosi/python';
    
            /*
            |--------------------------------------------------------------------------
            | VALIDASI PYTHON
            |--------------------------------------------------------------------------
            */
    
            if (!file_exists($pythonEnv)) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'Python environment tidak ditemukan',
                    'path' => $pythonEnv
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | VALIDASI SCRIPT
            |--------------------------------------------------------------------------
            */
    
            if (!file_exists($scriptPython)) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'ivcam_tester.py tidak ditemukan',
                    'path' => $scriptPython
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | VALIDASI FILE IMAGE
            |--------------------------------------------------------------------------
            */
    
            if (!$request->hasFile('image')) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'File image tidak ditemukan'
                ]);
            }
    
            /*
            |--------------------------------------------------------------------------
            | SIMPAN TEMP IMAGE
            |--------------------------------------------------------------------------
            */
    
            $file = $request->file('image');
    
            $tempPath = $file->store('temp_frames', 'local');
    
            $fullPathImage = storage_path('app/' . $tempPath);
    
            /*
            |--------------------------------------------------------------------------
            | COMMAND PYTHON
            |--------------------------------------------------------------------------
            */
    
            $command =
                'cd ' . escapeshellarg($dirPython) .
                ' && ' .
                escapeshellarg($pythonEnv) .
                ' ' .
                escapeshellarg($scriptPython) .
                ' ' .
                escapeshellarg($fullPathImage) .
                ' 2>&1';
    
            Log::info('COMMAND PYTHON', [
                'command' => $command
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | RUN PYTHON
            |--------------------------------------------------------------------------
            */
    
            $output = shell_exec($command);
    
            Log::info('OUTPUT PYTHON', [
                'output' => $output
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | HAPUS TEMP IMAGE
            |--------------------------------------------------------------------------
            */
    
            if (file_exists($fullPathImage)) {
    
                unlink($fullPathImage);
            }
    
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
    
            Log::error('ERROR PYTHON', [
    
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
    
            ]);
    
            return response()->json([
    
                'status' => 'error',
    
                'message' => $e->getMessage()
    
            ], 500);
        }
    }
}