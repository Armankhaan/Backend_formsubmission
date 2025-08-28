<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request) {
        $u = $request->user();
        return ['id'=>$u->id, 'name'=>$u->name, 'email'=>$u->email, 'is_admin'=>$u->is_admin];
    }

    public function stats(Request $request) {
        $user = $request->user();
        $rows = QuizSession::where('user_id',$user->id)
            ->where('completed',true)
            ->orderByDesc('completed_at')
            ->get(['score','questions','completed_at','category']);

        if ($rows->isEmpty()) {
            return [
                'total_quizzes'=>0,'average_score'=>0,'best_score'=>0,
                'total_questions'=>0,'recent_scores'=>[],
            ];
        }

        $percentages = [];
        $totalQ = 0; $best = 0;
        foreach ($rows as $r) {
            $qCount = count($r->questions);
            $totalQ += $qCount;
            $pct = $qCount > 0 ? ($r->score / $qCount) * 100 : 0;
            $percentages[] = $pct;
            if ($pct > $best) $best = $pct;
        }

        $avg = array_sum($percentages)/count($percentages);
        return [
            'total_quizzes' => $rows->count(),
            'average_score' => round($avg),
            'best_score' => round($best),
            'total_questions' => $totalQ,
            'recent_scores' => array_map(fn($v)=>round($v), array_slice($percentages, 0, 5)),
        ];
    }

    public function recentQuizzes(Request $request) {
        $user = $request->user();
        $rows = QuizSession::where('user_id',$user->id)
            ->where('completed',true)
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get(['id','score','questions','completed_at','category']);

        return $rows->map(function($r){
            return [
                'id'=>$r->id,
                'score'=>$r->score,
                'total_questions'=>count($r->questions),
                'completed_at'=>optional($r->completed_at)->toIso8601String(),
                'category'=>$r->category ?? 'Mixed',
            ];
        })->values();
    }
}

