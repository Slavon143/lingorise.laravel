<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'request_uuid',
        'user_id',
        'book_id',
        'operation_type',
        'provider',
        'model',
        'cache_key',
        'cache_hit',
        'provider_called',
        'request_characters',
        'response_characters',
        'input_tokens',
        'output_tokens',
        'cached_input_tokens',
        'audio_input_tokens',
        'audio_output_tokens',
        'audio_duration_ms',
        'audio_size_bytes',
        'pricing_version',
        'cost_calculation_type',
        'estimated_cost_usd',
        'actual_cost_usd',
        'saved_cost_usd',
        'duration_ms',
        'provider_duration_ms',
        'status',
        'http_status',
        'error_code',
        'safe_error_message',
        'ip_hash',
        'user_agent_hash',
    ];

    protected function casts(): array
    {
        return [
            'cache_hit' => 'boolean',
            'provider_called' => 'boolean',
            'request_characters' => 'integer',
            'response_characters' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cached_input_tokens' => 'integer',
            'audio_input_tokens' => 'integer',
            'audio_output_tokens' => 'integer',
            'audio_duration_ms' => 'integer',
            'audio_size_bytes' => 'integer',
            'estimated_cost_usd' => 'decimal:8',
            'actual_cost_usd' => 'decimal:8',
            'saved_cost_usd' => 'decimal:8',
            'duration_ms' => 'integer',
            'provider_duration_ms' => 'integer',
            'http_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
