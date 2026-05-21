<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ClassModel;
use App\User;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function index()
{
    if (Auth::user()->role == 'Dosen') {

        $classes = ClassModel::with('dosen')
            ->where('dosen_id', Auth::id())
            ->get();
    
    } else {
    
        $classes = ClassModel::with('dosen')->get();
    }

    return view('classes.index', compact('classes'));
}

    public function create()
    {
        $dosens = User::all(); // kalau ada role dosen bisa difilter
    
        return view('classes.create', compact('dosens'));
    }

    public function store(Request $request)
    {
        ClassModel::create($request->all());

        return redirect('/classes')->with('success','Class berhasil dibuat');
    }

    public function edit($id)
    {
        $class = ClassModel::findOrFail($id);
        $dosens = User::where('role', 'Dosen')->get();
    
        return view('classes.edit', compact('class','dosens'));
    }

    public function update(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);
    
        $class->update([
            'kode_kelas' => $request->kode_kelas,
            'nama_kelas' => $request->nama_kelas,
            'mata_kuliah' => $request->mata_kuliah,
            'dosen_id' => $request->dosen_id,
            'semester' => $request->semester,
            'tahun_ajaran' => $request->tahun_ajaran,
            'ruang' => $request->ruang
        ]);
    
        return redirect()->route('classes.index')
                ->with('success','Data kelas berhasil diupdate');
    }

    public function destroy($id)
    {
        ClassModel::destroy($id);
        return redirect('/classes');
    }
}