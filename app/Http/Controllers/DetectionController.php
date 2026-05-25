<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}