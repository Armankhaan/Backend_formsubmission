<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_titles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('email')->index();
            $t->unsignedInteger('score');
            $t->string('title');                 // e.g. "الريادة – المايسترو"
            // $t->string('image_path')->nullable(); // enable later if you save a screenshot
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_titles');
    }
};
