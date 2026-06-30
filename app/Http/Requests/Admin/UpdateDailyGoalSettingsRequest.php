<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDailyGoalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'default_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'minimum_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'maximum_minutes' => ['required', 'integer', 'min:1', 'max:1440', 'gte:minimum_minutes'],
            'preset_minutes' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                $minutes = $this->parsePresetMinutes((string) $value);
                if ($minutes === []) {
                    $fail('Add at least one selectable preset.');
                    return;
                }

                if (count($minutes) !== count(array_unique($minutes))) {
                    $fail('Preset values must be unique.');
                    return;
                }

                $min = (int) $this->input('minimum_minutes');
                $max = (int) $this->input('maximum_minutes');

                foreach ($minutes as $minute) {
                    if ($minute < $min || $minute > $max) {
                        $fail('Every preset must be between the minimum and maximum goal.');
                        return;
                    }
                }
            }],
            'streak_enabled' => ['nullable', 'boolean'],
            'over_goal_message_enabled' => ['nullable', 'boolean'],
            'custom_goal_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $default = (int) $this->input('default_minutes');
            $min = (int) $this->input('minimum_minutes');
            $max = (int) $this->input('maximum_minutes');

            if ($default < $min || $default > $max) {
                $validator->errors()->add('default_minutes', 'Default goal must be between minimum and maximum.');
            }
        });
    }

    public function settingsData(): array
    {
        $validated = parent::validated();

        $validated['preset_minutes'] = $this->parsePresetMinutes((string) $this->input('preset_minutes'));
        $validated['streak_enabled'] = $this->boolean('streak_enabled');
        $validated['over_goal_message_enabled'] = $this->boolean('over_goal_message_enabled');
        $validated['custom_goal_enabled'] = $this->boolean('custom_goal_enabled');

        return $validated;
    }

    private function parsePresetMinutes(string $value): array
    {
        return collect(preg_split('/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => filter_var($item, FILTER_VALIDATE_INT))
            ->filter(fn ($item) => $item !== false)
            ->map(fn ($item) => (int) $item)
            ->sort()
            ->values()
            ->all();
    }
}
