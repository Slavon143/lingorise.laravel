<?php

namespace Database\Seeders;

use App\Enums\PlanCode;
use App\Models\Plan;
use App\Models\PlanReaderSettings;
use Illuminate\Database\Seeder;

class PlanReaderDefaults extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::where('code', PlanCode::Free->value)->first();
        $premiumPlan = Plan::where('code', PlanCode::Premium->value)->first();
        $proPlan = Plan::where('code', PlanCode::Pro->value)->first();

        if ($freePlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $freePlan->id], $this->defaults('free'));
        }

        if ($premiumPlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $premiumPlan->id], $this->defaults('premium'));
        }

        if ($proPlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $proPlan->id], $this->defaults('pro'));
        }
    }

    private function defaults(string $plan): array
    {
        return match ($plan) {
            'premium' => [
                'translation_max_words' => 30,
                'context_max_words' => 20,
                'grammar_max_words' => 30,
                'simplify_max_words' => 30,
                'tts_max_words' => 30,
                'pronunciation_max_words' => 25,
                'vocabulary_max_words' => 20,
                'ai_actions_daily_limit' => 100,
                'ai_tts_monthly_characters' => 50000,
                'ai_tts_enabled' => true,
                'browser_tts_enabled' => true,
                'pronunciation_recording_enabled' => true,
                'shadowing_enabled' => true,
                'voice_selection_enabled' => false,
                'context_enabled' => true,
                'grammar_enabled' => true,
                'simplify_enabled' => true,
                'translation_enabled' => true,
                'vocabulary_enabled' => true,
                'is_active' => true,
            ],
            'pro' => [
                'translation_max_words' => 50,
                'context_max_words' => 30,
                'grammar_max_words' => 50,
                'simplify_max_words' => 40,
                'tts_max_words' => 50,
                'pronunciation_max_words' => 30,
                'vocabulary_max_words' => 25,
                'ai_actions_daily_limit' => 300,
                'ai_tts_monthly_characters' => 200000,
                'ai_tts_enabled' => true,
                'browser_tts_enabled' => true,
                'pronunciation_recording_enabled' => true,
                'shadowing_enabled' => true,
                'voice_selection_enabled' => true,
                'context_enabled' => true,
                'grammar_enabled' => true,
                'simplify_enabled' => true,
                'translation_enabled' => true,
                'vocabulary_enabled' => true,
                'is_active' => true,
            ],
            default => [
                'translation_max_words' => 10,
                'context_max_words' => 6,
                'grammar_max_words' => 10,
                'simplify_max_words' => 10,
                'tts_max_words' => 10,
                'pronunciation_max_words' => 10,
                'vocabulary_max_words' => 10,
                'ai_actions_daily_limit' => 10,
                'ai_tts_monthly_characters' => null,
                'ai_tts_enabled' => false,
                'browser_tts_enabled' => true,
                'pronunciation_recording_enabled' => true,
                'shadowing_enabled' => false,
                'voice_selection_enabled' => false,
                'context_enabled' => true,
                'grammar_enabled' => true,
                'simplify_enabled' => true,
                'translation_enabled' => true,
                'vocabulary_enabled' => true,
                'is_active' => true,
            ],
        };
    }
}
