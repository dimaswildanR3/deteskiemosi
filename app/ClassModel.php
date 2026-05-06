<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'kode_kelas',
        'nama_kelas',
        'mata_kuliah',
        'dosen_id',
        'semester',
        'tahun_ajaran',
        'ruang'
    ];
    public function dosen()
{
    return $this->belongsTo(User::class, 'dosen_id');
}
}