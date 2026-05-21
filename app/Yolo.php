<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Yolo extends Model
{
    protected $table = 'Yolo';
    protected $fillable = [
        'user_id',
        'class_id',
        'session_name',
        'total_captures',
        'positive_rate',
        'avg_sentiment',
        'started_at',
        'ended_at'
    ];


    public function logs()
    {
        return $this->hasMany(EmotionLog::class, 'session_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}