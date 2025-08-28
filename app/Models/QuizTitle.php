<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuizTitle extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id','email','score','title'/*,'image_path'*/];

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->id)) $m->id = (string) Str::uuid();
        });
    }
}
