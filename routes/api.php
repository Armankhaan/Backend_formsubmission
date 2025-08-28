<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\PublicQuizController;
use App\Http\Controllers\QuizSessionController; // keep if needed
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

// Auth (optional, kept for your admin/dashboard)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

Route::get('/quiz-titles', [PublicQuizController::class, 'index']);
// PUBLIC: categories & questions
Route::get('/questions/categories', [QuestionController::class, 'categories']);
Route::get('/questions',            [QuestionController::class, 'index']);

// PUBLIC: submit all answers at once
Route::post('/public/quiz/submit',  [PublicQuizController::class, 'submit']);

// Protected admin/authoring
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [UserController::class, 'me']);

    Route::post('/questions',              [QuestionController::class, 'store']);
    Route::put('/questions/{id}',          [QuestionController::class, 'update']);
    Route::delete('/questions/{id}',       [QuestionController::class, 'destroy']);

    // (Optional) old session-based endpoints
    Route::post('/quiz-sessions/start',                   [QuizSessionController::class, 'start']);
    Route::patch('/quiz-sessions/{id}/answer',            [QuizSessionController::class, 'answer']);
    Route::patch('/quiz-sessions/{id}/complete',          [QuizSessionController::class, 'complete']);

    Route::get('/user/stats',           [UserController::class, 'stats']);
    Route::get('/user/recent-quizzes',  [UserController::class, 'recentQuizzes']);

    Route::get('/admin/user-scores',    [AdminController::class, 'userScores']);
});
