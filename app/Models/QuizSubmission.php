<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuizSubmission extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','email','answers','score','total','category','difficulty'
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->id)) $m->id = (string) Str::uuid();
        });
    }
}
