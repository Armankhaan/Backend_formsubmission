<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_submissions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('email')->index();
            $t->json('answers');            // [{question_id, selected_index, category, binary, valid}]
            $t->unsignedInteger('score');   // computed with your 4/5 rule
            $t->unsignedInteger('total');   // total items answered
            $t->string('category')->nullable();   // optional, kept for future
            $t->string('difficulty')->nullable(); // optional, not used
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};
