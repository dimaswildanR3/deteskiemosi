<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetectionController extends Controller
{
    public function start(Request $request)
    {
        $pythonPath = base_path('python/venv/bin/python');
    
        $scriptPath = base_path('python/ivcam_tester.py');
    
        $command = "cd " . base_path('python') .
                   " && $pythonPath $scriptPath > /dev/null 2>&1 &";
    
        exec($command);
    
        return response()->json([
            'status'  => 'ok',
            'message' => 'Detection started'
        ]);
    }


    
}