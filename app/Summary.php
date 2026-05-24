<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    protected $table = 'summary';
    protected $fillable = [
        'session_id',
        'total_positif',
        'total_negatif',
        'persen_positif',
        'persen_negatif',
        'updated_at'
    ];
}
