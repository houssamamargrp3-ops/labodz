<?php

namespace Database\Seeders;

use App\Models\Analyse;
use App\Models\AnalysisRule;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Seeder;

class AnalysisMetadataSeeder extends Seeder
{
    public function run()
    {
        // 1. CBS - هل تتناول أدوية سيولة الدم؟
        $cbc = Analyse::where('code', 'CBC')->first();
        if ($cbc) {
            $q1 = Question::create([
                'analyse_id' => $cbc->id,
                'question' => 'هل تتناول أدوية سيولة الدم (مثل الأسبرين)؟',
                'type' => 'radio',
            ]);

            $optYes = Option::create(['question_id' => $q1->id, 'text' => 'نعم']);
            $optNo = Option::create(['question_id' => $q1->id, 'text' => 'لا']);

            // Rule: Warning if Yes
            AnalysisRule::create([
                'analysis_id' => $cbc->id,
                'question_id' => $q1->id,
                'disallowed_option_id' => $optYes->id,
                'action' => 'warning',
            ]);
        }

        // 2. FBS - هل أنت صائم؟
        $fbs = Analyse::where('code', 'FBS')->first();
        if ($fbs) {
            $q2 = Question::create([
                'analyse_id' => $fbs->id,
                'question' => 'هل أنت صائم منذ 8 ساعات على الأقل؟',
                'type' => 'radio',
            ]);

            $optFastingYes = Option::create(['question_id' => $q2->id, 'text' => 'نعم']);
            $optFastingNo = Option::create(['question_id' => $q2->id, 'text' => 'لا']);

            // Rule: Block if No
            AnalysisRule::create([
                'analysis_id' => $fbs->id,
                'question_id' => $q2->id,
                'disallowed_option_id' => $optFastingNo->id,
                'action' => 'block',
            ]);
        }
    }
}
