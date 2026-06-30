<?php

namespace App\Services;

use App\Models\AppSetting;

class DailyGoalSettingsService
{
    public const KEY = 'daily_goal';

    public function defaults(): array
    {
        return [
            'default_minutes' => 10,
            'minimum_minutes' => 1,
            'maximum_minutes' => 180,
            'preset_minutes' => [5, 10, 15, 20, 30],
            'streak_enabled' => true,
            'over_goal_message_enabled' => true,
            'custom_goal_enabled' => true,
        ];
    }

    public function get(): array
    {
        $stored = AppSetting::query()->where('key', self::KEY)->value('value') ?? [];

        return $this->normalize(array_replace($this->defaults(), is_array($stored) ? $stored : []));
    }

    public function update(array $settings): array
    {
        $normalized = $this->normalize(array_replace($this->defaults(), $settings));

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $normalized],
        );

        return $normalized;
    }

    private function normalize(array $settings): array
    {
        $min = max(1, (int) ($settings['minimum_minutes'] ?? 1));
        $max = min(1440, max($min, (int) ($settings['maximum_minutes'] ?? 180)));
        $default = min($max, max($min, (int) ($settings['default_minutes'] ?? 10)));
        $presets = collect($settings['preset_minutes'] ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value >= $min && $value <= $max)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($presets === []) {
            $presets = [$default];
        }

        return [
            'default_minutes' => $default,
            'minimum_minutes' => $min,
            'maximum_minutes' => $max,
            'preset_minutes' => $presets,
            'streak_enabled' => (bool) ($settings['streak_enabled'] ?? true),
            'over_goal_message_enabled' => (bool) ($settings['over_goal_message_enabled'] ?? true),
            'custom_goal_enabled' => (bool) ($settings['custom_goal_enabled'] ?? true),
        ];
    }
}
