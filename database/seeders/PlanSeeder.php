use App\Enums\PlanCode;
use App\Models\Plan;
use App\Models\PlanAiLimit;
use App\Models\PlanReaderSettings;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['code' => PlanCode::Free->value, 'name' => 'Free', 'description' => 'Free plan with basic features', 'price_amount' => 0, 'price_currency' => 'USD', 'is_active' => true, 'is_default' => true, 'position' => 1],
            ['code' => PlanCode::Premium->value, 'name' => 'Premium', 'description' => 'Premium plan with advanced features', 'price_amount' => 990, 'price_currency' => 'USD', 'is_active' => true, 'is_default' => false, 'position' => 2],
            ['code' => PlanCode::Pro->value, 'name' => 'Pro', 'description' => 'Pro plan with all features', 'price_amount' => 1990, 'price_currency' => 'USD', 'is_active' => true, 'is_default' => false, 'position' => 3],
            ['code' => PlanCode::Admin->value, 'name' => 'Admin', 'description' => 'Admin plan with all features', 'price_amount' => 0, 'price_currency' => 'USD', 'is_active' => true, 'is_default' => false, 'position' => 99],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::firstOrCreate(['code' => $planData['code']], $planData);

            $aiLimitData = $plan->isFree()
                ? [
                    'translations_per_day' => 10,
                    'explanations_per_day' => 10,
                    'tts_minutes_per_day' => 10,
                    'ai_translation_enabled' => false,
                    'ai_explanation_enabled' => false,
                    'ai_context_explanation_enabled' => false,
                    'ai_grammar_explanation_enabled' => false,
                    'ai_simplification_enabled' => false,
                    'ai_tts_enabled' => false,
                    'browser_tts_enabled' => true,
                    'premium_books_enabled' => false,
                    'shadowing_enabled' => false,
                ]
                : $plan->isPremium()
                ? [
                    'translations_per_day' => 100,
                    'explanations_per_day' => 100,
                    'context_explanations_per_day' => 30,
                    'grammar_explanations_per_day' => 30,
                    'simplifications_per_day' => 30,
                    'tts_minutes_per_day' => 60,
                    'max_translation_characters' => 50000,
                    'max_explanation_selected_characters' => 50000,
                    'max_explanation_context_characters' => 50000,
                    'max_context_explanation_characters' => 50000,
                    'max_grammar_explanation_characters' => 50000,
                    'max_simplification_characters' => 50000,
                    'max_tts_characters_per_request' => 5000,
                    'ai_translation_enabled' => true,
                    'ai_explanation_enabled' => true,
                    'ai_context_explanation_enabled' => true,
                    'ai_grammar_explanation_enabled' => true,
                    'ai_simplification_enabled' => true,
                    'ai_tts_enabled' => true,
                    'browser_tts_enabled' => true,
                    'premium_books_enabled' => true,
                    'private_books_limit' => 10,
                    'shadowing_enabled' => true,
                ]
                : [
                    'translations_per_day' => 300,
                    'explanations_per_day' => 300,
                    'context_explanations_per_day' => 100,
                    'grammar_explanations_per_day' => 100,
                    'simplifications_per_day' => 100,
                    'tts_minutes_per_day' => 600,
                    'max_translation_characters' => 200000,
                    'max_explanation_selected_characters' => 200000,
                    'max_explanation_context_characters' => 200000,
                    'max_context_explanation_characters' => 200000,
                    'max_grammar_explanation_characters' => 200000,
                    'max_simplification_characters' => 200000,
                    'max_tts_characters_per_request' => 50000,
                    'ai_translation_enabled' => true,
                    'ai_explanation_enabled' => true,
                    'ai_context_explanation_enabled' => true,
                    'ai_grammar_explanation_enabled' => true,
                    'ai_simplification_enabled' => true,
                    'ai_tts_enabled' => true,
                    'browser_tts_enabled' => true,
                    'premium_books_enabled' => true,
                    'private_books_limit' => 50,
                    'shadowing_enabled' => true,
                ];

            $plan->aiLimits()->updateOrCreate([], $aiLimitData);

            $this->call(PlanReaderDefaults::class);
        }
    }
}