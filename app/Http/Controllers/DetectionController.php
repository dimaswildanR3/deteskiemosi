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
            | VALIDASI IMAGE
            |--------------------------------------------------------------------------
            */
    
            if (!$request->hasFile('image')) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'Image tidak ditemukan'
                ], 400);
            }
    
            /*
            |--------------------------------------------------------------------------
            | PYTHON ENV SERVER
            |--------------------------------------------------------------------------
            */
    
            $pythonEnv =
                '/home/deteksie/virtualenv/repositories/deteskiemosi/python/3.11/bin/python';
    
            /*
            |--------------------------------------------------------------------------
            | PYTHON SCRIPT
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
            | VALIDASI PYTHON ENV
            |--------------------------------------------------------------------------
            */
    
            if (!file_exists($pythonEnv)) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'Python environment tidak ditemukan',
                    'path' => $pythonEnv
                ], 500);
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
                ], 500);
            }
    
            /*
            |--------------------------------------------------------------------------
            | SIMPAN IMAGE TEMPORARY
            |--------------------------------------------------------------------------
            */
    
            $file = $request->file('image');
    
            $tempPath = $file->store(
                'temp_frames',
                'local'
            );
    
            $fullPathImage = storage_path(
                'app/' . $tempPath
            );
    
            Log::info('IMAGE TEMP', [
                'path' => $fullPathImage
            ]);
    
            /*
            |--------------------------------------------------------------------------
            | COMMAND PYTHON
            |--------------------------------------------------------------------------
            */
    
            $command =
                'cd ' .
                escapeshellarg($dirPython) .
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
            | HAPUS IMAGE TEMP
            |--------------------------------------------------------------------------
            */
    
            if (file_exists($fullPathImage)) {
    
                unlink($fullPathImage);
            }
    
            /*
            |--------------------------------------------------------------------------
            | VALIDASI OUTPUT PYTHON
            |--------------------------------------------------------------------------
            */
    
            if (!$output) {
    
                return response()->json([
                    'status' => 'error',
                    'message' => 'Python tidak mengembalikan output'
                ], 500);
            }
    
            /*
            |--------------------------------------------------------------------------
            | RESPONSE SUCCESS
            |--------------------------------------------------------------------------
            */
    
            return response()->json([
    
                'status' => 'success',
    
                'output_python' => trim($output)
    
            ]);
    
        } catch (\Throwable $e) {
    
            Log::error('ERROR DETEKSI', [
    
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