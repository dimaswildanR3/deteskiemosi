<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'session';
    protected $fillable = [
        'nama_kelas',
        'dosen',
        'waktu_mulai',
        'waktu_selesai',
        'total_mahasiswa'
    ];
}