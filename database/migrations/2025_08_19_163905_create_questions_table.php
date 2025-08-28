<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('question');
            $table->json('options');               // array of strings
            $table->unsignedInteger('correct_answer');
            $table->text('explanation')->nullable();
            $table->string('category');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy');
            $table->timestamps();
            $table->index(['category', 'difficulty']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('questions');
    }
};
