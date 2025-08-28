<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Question extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id','question','options','correct_answer','explanation','category','difficulty'
    ];
    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'integer',
    ];

    protected static function booted() {
        static::creating(function ($m) { if (!$m->id) $m->id = (string) Str::uuid(); });
    }
}
