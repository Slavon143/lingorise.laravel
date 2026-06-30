<?php

namespace App\Models;

use App\Enums\WordEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWordEvent extends Model
{
    protected $fillable = [
        'user_id',
        'user_word_id',
        'book_id',
        'page_number',
        'word_index',
        'event_type',
        'context_hash',
        'score_delta',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => WordEventType::class,
            'score_delta' => 'float',
            'page_number' => 'integer',
            'word_index' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userWord(): BelongsTo
    {
        return $this->belongsTo(UserWord::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
