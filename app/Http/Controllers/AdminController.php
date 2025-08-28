<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function userScores(Request $request) {
        if (Gate::denies('admin')) {
            return response()->json(['message'=>'Forbidden'], 403);
        }

        // Aggregate per user
        $sessions = QuizSession::where('completed',true)
            ->select('user_id','score','questions','completed_at')
            ->get();

        $users = User::pluck('email','id'); // id => email

        $agg = [];
        foreach ($sessions as $s) {
            $uid = $s->user_id;
            $totalQ = count($s->questions);
            if ($totalQ === 0) continue;

            $pct = ($s->score / $totalQ) * 100;

            if (!isset($agg[$uid])) {
                $agg[$uid] = [
                    'user_id' => $uid,
                    'user_email' => $users[$uid] ?? 'unknown@local',
                    'total_quizzes' => 0,
                    'average_score' => 0.0,
                    'best_score' => 0.0,
                    'last_quiz_date' => null,
                    '_sum_pct' => 0.0,
                ];
            }
            $agg[$uid]['total_quizzes'] += 1;
            $agg[$uid]['_sum_pct'] += $pct;
            if ($pct > $agg[$uid]['best_score']) $agg[$uid]['best_score'] = $pct;

            $date = $s->completed_at;
            if (!$agg[$uid]['last_quiz_date'] || ($date && $date->gt($agg[$uid]['last_quiz_date']))) {
                $agg[$uid]['last_quiz_date'] = $date;
            }
        }

        // finalize averages
        $out = array_values(array_map(function($row){
            if ($row['total_quizzes'] > 0) {
                $row['average_score'] = $row['_sum_pct'] / $row['total_quizzes'];
            }
            unset($row['_sum_pct']);
            $row['average_score'] = round($row['average_score']);
            $row['best_score'] = round($row['best_score']);
            $row['last_quiz_date'] = optional($row['last_quiz_date'])->toIso8601String();
            return $row;
        }, $agg));

        return $out;
    }
}

