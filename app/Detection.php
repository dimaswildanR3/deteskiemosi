<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Detection extends Model
{
    public $timestamps = false;
    protected $table = 'detections';
    protected $fillable = [
        'session_id',
        'nomor_mahasiswa',
        'label',
        'confidence',
        'timestamp'
    ];
}
