<?php

namespace App\Models;

use App\Enums\WordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserWord extends Model
{
    protected $fillable = [
        'user_id',
        'language',
        'lemma',
        'display_word',
        'translation',
        'status',
        'seen_count',
        'translation_count',
        'explanation_count',
        'correct_count',
        'incorrect_count',
        'listening_correct_count',
        'listening_incorrect_count',
        'last_seen_at',
        'last_practiced_at',
        'mastery_score',
    ];

    protected function casts(): array
    {
        return [
            'status' => WordStatus::class,
            'seen_count' => 'integer',
            'translation_count' => 'integer',
            'explanation_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'listening_correct_count' => 'integer',
            'listening_incorrect_count' => 'integer',
            'last_seen_at' => 'datetime',
            'last_practiced_at' => 'datetime',
            'mastery_score' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(UserWordEvent::class);
    }
}
