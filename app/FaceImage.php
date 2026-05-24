<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FaceImage extends Model
{
    public $timestamps = false;
    protected $table = 'face_detection_summary';
    protected $fillable = [
        'session_id',
        'nomor_mahasiswa',
        'label',
        'file_path',
        'created_at'
    ];
}
