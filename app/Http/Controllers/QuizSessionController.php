<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QuizSessionController extends Controller
{
    public function start(Request $request) {
        $user = $request->user();
        $data = $request->validate([
            'category' => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        $limit = $data['limit'] ?? 10;

        $query = Question::query();
        if (!empty($data['category']))   $query->where('category', $data['category']);
        if (!empty($data['difficulty'])) $query->where('difficulty', $data['difficulty']);
        $questions = $query->inRandomOrder()->limit($limit)->get();

        if ($questions->isEmpty()) {
            return response()->json(['message'=>'No questions found'], 404);
        }

        $session = QuizSession::create([
            'user_id' => $user->id,
            'questions' => $questions->pluck('id')->values()->all(),
            'current_question' => 0,
            'score' => 0,
            'completed' => false,
            'category' => $data['category'] ?? 'Mixed',
            'difficulty' => $data['difficulty'] ?? null,
        ]);

        return response()->json(['session'=>$session, 'questions'=>$questions]);
    }

    public function answer(string $id, Request $request) {
        $session = QuizSession::where('id',$id)->where('completed',false)->firstOrFail();
        $data = $request->validate([
            'question_id' => 'required|string',
            'selected_index' => 'required|integer|min:0',
            'correct' => 'required|boolean',
            'next_index' => 'required|integer|min:0',
        ]);

        // Safety: verify correctness server-side
        $question = Question::findOrFail($data['question_id']);
        $isCorrect = ((int)$data['selected_index'] === (int)$question->correct_answer);

        $session->score = $isCorrect ? $session->score + 1 : $session->score;
        $session->current_question = $data['next_index'];
        $session->save();

        return $session;
    }

    public function complete(string $id) {
        $session = QuizSession::findOrFail($id);
        $session->completed = true;
        $session->completed_at = Carbon::now();
        $session->save();
        return response()->json(['completed'=>true]);
    }
}

