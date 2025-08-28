<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    // /questions?category=Leadership&order=asc
    public function index(Request $request) {
        $order = $request->get('order','desc') === 'asc' ? 'asc' : 'desc';
        $q = Question::query();

        if ($category = $request->get('category')) {
            $q->where('category', $category);
        }

        // We don't need difficulty; ignore it completely
        return $q->orderBy('created_at', $order)->get([
            'id','question','question_ar','options','options_ar','category'
        ]);
    }

    public function categories() {
        return Question::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    // If you still add questions via API: no difficulty, no correct_answer (likert use-case)
    public function store(Request $request) {
        $data = $request->validate([
            'question'     => 'required|string',
            'question_ar'  => 'nullable|string',
            'options'      => 'required|array|min:2|max:6',
            'options.*'    => 'required|string',
            'options_ar'   => 'nullable|array|min:2|max:6',
            'options_ar.*' => 'required_with:options_ar|string',
            'explanation'  => 'nullable|string', // ignored in UI
            'category'     => 'required|string',
            // 'difficulty'  => removed
            // 'correct_answer' => removed for likert
        ]);

        return response()->json(Question::create($data), 201);
    }

    public function update(string $id, Request $request) {
        $q = Question::findOrFail($id);
        $data = $request->validate([
            'question'     => 'required|string',
            'question_ar'  => 'nullable|string',
            'options'      => 'required|array|min:2|max:6',
            'options.*'    => 'required|string',
            'options_ar'   => 'nullable|array|min:2|max:6',
            'options_ar.*' => 'required_with:options_ar|string',
            'explanation'  => 'nullable|string',
            'category'     => 'required|string',
        ]);

        $q->update($data);
        return $q;
    }

    public function destroy(string $id) {
        Question::findOrFail($id)->delete();
        return response()->json(['deleted'=>true]);
    }
}
