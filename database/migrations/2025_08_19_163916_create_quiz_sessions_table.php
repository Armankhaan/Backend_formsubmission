<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('questions');                 // array of question UUIDs (strings)
            $table->unsignedInteger('current_question')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->string('category')->nullable();
            $table->enum('difficulty', ['easy','medium','hard'])->nullable();
            $table->timestamps();
            $table->index(['user_id','completed','completed_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('quiz_sessions');
    }
};
