<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuizSession extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','user_id','questions','current_question','score','completed',
        'started_at','completed_at','category','difficulty'
    ];
    protected $casts = [
        'questions' => 'array',
        'completed' => 'boolean',
        'current_question' => 'integer',
        'score' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted() {
        static::creating(function ($m) { if (!$m->id) $m->id = (string) Str::uuid(); });
    }

    public function user() { return $this->belongsTo(User::class); }
}
