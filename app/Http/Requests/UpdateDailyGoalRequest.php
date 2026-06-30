<?php

namespace App\Http\Requests;

use App\Services\DailyGoalSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDailyGoalRequest extends FormRequest
{
    public function rules(DailyGoalSettingsService $settingsService): array
    {
        $settings = $settingsService->get();
        $rules = [
            'daily_goal_minutes' => [
                'required',
                'integer',
                'min:'.$settings['minimum_minutes'],
                'max:'.$settings['maximum_minutes'],
            ],
        ];

        if (! $settings['custom_goal_enabled']) {
            $rules['daily_goal_minutes'][] = Rule::in($settings['preset_minutes']);
        }

        return $rules;
    }
}
