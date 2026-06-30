<?php

return [
    'openai' => [
        'models' => [
            'gpt-5.4-mini' => [
                'effective_from' => '2026-01-01',
                'currency' => 'USD',
                'pricing_version' => '2026-01',
                'input_per_million_tokens' => 0.15,
                'cached_input_per_million_tokens' => 0.075,
                'output_per_million_tokens' => 0.60,
                'audio_input_per_million_tokens' => null,
                'audio_output_per_million_tokens' => null,
                'per_million_characters' => null,
                'per_minute' => null,
            ],
            'gpt-4o-mini-tts' => [
                'effective_from' => '2026-01-01',
                'currency' => 'USD',
                'pricing_version' => '2026-01',
                'input_per_million_tokens' => null,
                'cached_input_per_million_tokens' => null,
                'output_per_million_tokens' => null,
                'audio_input_per_million_tokens' => null,
                'audio_output_per_million_tokens' => null,
                'per_million_characters' => null,
                'per_minute' => null,
            ],
        ],
    ],
];
