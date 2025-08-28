<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuizSubmission;
use App\Models\QuizTitle;
use Illuminate\Http\Request;

class PublicQuizController extends Controller
{
    /** Arabic titles per category (from your screenshot) */
    private const CATEGORY_TITLES = [
        'Leadership'  => 'الريادة – المايسترو',   // 1
        'Flexibility' => 'المرونة – الجوكر',     // 2
        'Excellence'  => 'التميز – القدوة',       // 3
        'Empowerment' => 'التمكين – الملهم',     // 4
        'Initiative'  => 'الإقدام – الفزعة',      // 5
    ];

    /** Deterministic tie-break order */
    private const CATEGORY_PRIORITY = [
        'Leadership','Flexibility','Excellence','Empowerment','Initiative'
    ];

    /**
     * POST /public/quiz/submit
     * Body:
     * {
     *   "email":"user@example.com",
     *   "answers":[{"question_id":"...","selected_index":1..5,"category":"Leadership"}, ...]
     * }
     */
public function submit(Request $request)
{
    $payload = $request->validate([
        'email'   => 'required|email',
        'answers' => 'required|array|min:1',
        // Allow 0..5 so we can accept either 0-based (0..4) or 1-based (1..5) from clients
        'answers.*.question_id'    => 'required|string',
        'answers.*.selected_index' => 'required|integer|min:0|max:5',
        'answers.*.category'       => 'required|string',
    ]);

    $rawAnswers = $payload['answers'];

    // Optional: verify question IDs exist
    $ids = collect($rawAnswers)->pluck('question_id')->unique()->values();
    $existingIds = Question::whereIn('id', $ids)->pluck('id')->all();
    $existsH = array_flip($existingIds);

    // Normalize selected_index to 0-based (0..4), accept either 0..4 or 1..5
    $decorated = collect($rawAnswers)->map(function ($a) use ($existsH) {
        $val = (int)($a['selected_index'] ?? 0);
        // if client sent 1..5, convert to 0..4; if 0..4 keep as-is
        $idx0 = ($val >= 1 && $val <= 5) ? $val - 1 : $val; // now 0..4
        if ($idx0 < 0 || $idx0 > 4) {
            // safety—should not happen due to validation
            $idx0 = max(0, min(4, $idx0));
        }

        // Your rule: 1,2,3 -> 0 and 4,5 -> 1  (in 0-based => 0,1,2 -> 0  and 3,4 -> 1)
        $binary = ($idx0 >= 3) ? 1 : 0;

        return [
            'question_id'    => (string)$a['question_id'],
            'selected_index' => $idx0,                    // stored 0-based
            'category'       => (string)$a['category'],
            'binary'         => $binary,
            'valid'          => isset($existsH[$a['question_id']]),
        ];
    })->values();

    // Totals
    $overallScore = $decorated->sum('binary');
    $overallTotal = $decorated->count();

    // Per-category tallies to pick a title
    $cat = [];
    foreach ($decorated as $d) {
        $k = $d['category'];
        if (!isset($cat[$k])) $cat[$k] = ['score'=>0,'total'=>0];
        $cat[$k]['score'] += $d['binary'];
        $cat[$k]['total'] += 1;
    }

    // Title selection rules
    $priority = ['Leadership','Flexibility','Excellence','Empowerment','Initiative'];
    $titles = [
        'Leadership'  => 'الريادة – المايسترو',
        'Flexibility' => 'المرونة – الجوكر',
        'Excellence'  => 'التميز – القدوة',
        'Empowerment' => 'التمكين – الملهم',
        'Initiative'  => 'الإقدام – الفزعة',
    ];

    // 1) any full marks?
    $winner = null;
    foreach ($priority as $p) {
        if (isset($cat[$p]) && $cat[$p]['total'] > 0 && $cat[$p]['score'] === $cat[$p]['total']) {
            $winner = $p; break;
        }
    }
    // 2) else highest score -> better ratio -> priority
    if ($winner === null && !empty($cat)) {
        $maxScore = max(array_column($cat, 'score'));
        $cands = array_keys(array_filter($cat, fn($v) => $v['score'] === $maxScore));
        if (count($cands) > 1) {
            $bestRatio = -1.0; $pool = [];
            foreach ($cands as $k) {
                $r = $cat[$k]['total'] > 0 ? $cat[$k]['score'] / $cat[$k]['total'] : 0.0;
                if ($r > $bestRatio) { $bestRatio = $r; $pool = [$k]; }
                elseif (abs($r - $bestRatio) < 1e-9) { $pool[] = $k; }
            }
            $cands = $pool;
        }
        if (count($cands) === 1) $winner = $cands[0];
        else {
            foreach ($priority as $p) { if (in_array($p, $cands, true)) { $winner = $p; break; } }
        }
    }
    $title = $winner && isset($titles[$winner]) ? $titles[$winner] : null;

    // Save
    $submission = QuizSubmission::create([
        'email'      => $payload['email'],
        'answers'    => $decorated->all(),
        'score'      => $overallScore,
        'total'      => $overallTotal,
        'category'   => null,
        'difficulty' => null,
    ]);
    if ($title) {
        QuizTitle::create([
            'email' => $payload['email'],
            'score' => $overallScore,
            'title' => $title,
        ]);
    }

    return response()->json([
        'id'              => $submission->id,
        'email'           => $submission->email,
        'score'           => $overallScore,
        'total'           => $overallTotal,
        'winner_category' => $winner,
        'title'           => $title,
        'category_scores' => $cat,
        'saved'           => true,
    ], 201);
}


public function index()
{
    $quizTitles = QuizTitle::all();
    $count = QuizTitle::count();

    return response()->json([
        'data' => $quizTitles,
        'count' => $count
    ]);
}

    /**
     * Apply the scoring rule (1/2/3=0, 4/5=1), compute per-category & overall,
     * and choose the title:
     *  - If any category has full marks (score==total), pick that by priority.
     *  - Else pick the category with the highest score (ties -> better ratio -> priority).
     *
     * @param array $rawAnswers Each: ['question_id'=>..., 'selected_index'=>1..5, 'category'=>...]
     * @return array
     */
    private function pickTitleFromAnswers(array $rawAnswers): array
    {
        $cat = [];           // [category => ['score'=>int,'total'=>int]]
        $overallScore = 0;
        $overallTotal = 0;

        foreach ($rawAnswers as $a) {
            $category = (string)($a['category'] ?? '');
            if ($category === '') continue;

            $idx = (int)($a['selected_index'] ?? 0);   // 1..5
            $bin = ($idx >= 4) ? 1 : 0;

            if (!isset($cat[$category])) $cat[$category] = ['score' => 0, 'total' => 0];
            $cat[$category]['score'] += $bin;
            $cat[$category]['total'] += 1;

            $overallScore += $bin;
            $overallTotal += 1;
        }

        // (1) Any full-score categories?
        $fulls = [];
        foreach ($cat as $k => $v) {
            if ($v['total'] > 0 && $v['score'] === $v['total']) {
                $fulls[$k] = true;
            }
        }

        $winnerCategory = null;
        if (!empty($fulls)) {
            foreach (self::CATEGORY_PRIORITY as $p) {
                if (isset($fulls[$p])) { $winnerCategory = $p; break; }
            }
        }

        // (2) Else pick highest score; tie-break by ratio then priority
        if ($winnerCategory === null && !empty($cat)) {
            $maxScore = max(array_column($cat, 'score'));
            $candidates = array_keys(array_filter($cat, fn($v) => $v['score'] === $maxScore));

            if (count($candidates) === 1) {
                $winnerCategory = $candidates[0];
            } else {
                // compare ratios
                $bestCats = [];
                $bestRatio = -1.0;
                foreach ($candidates as $k) {
                    $v = $cat[$k];
                    $ratio = $v['total'] > 0 ? ($v['score'] / $v['total']) : 0.0;
                    if ($ratio > $bestRatio) {
                        $bestRatio = $ratio;
                        $bestCats = [$k];
                    } elseif (abs($ratio - $bestRatio) < 1e-9) {
                        $bestCats[] = $k;
                    }
                }
                if (count($bestCats) === 1) {
                    $winnerCategory = $bestCats[0];
                } else {
                    foreach (self::CATEGORY_PRIORITY as $p) {
                        if (in_array($p, $bestCats, true)) { $winnerCategory = $p; break; }
                    }
                }
            }
        }

        $title = $winnerCategory && isset(self::CATEGORY_TITLES[$winnerCategory])
            ? self::CATEGORY_TITLES[$winnerCategory]
            : null;

        return [
            'overall_score'   => $overallScore,
            'overall_total'   => $overallTotal,
            'category_scores' => $cat,
            'winner_category' => $winnerCategory,
            'title'           => $title,
        ];
    }
}
