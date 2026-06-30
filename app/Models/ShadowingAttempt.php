<?php

namespace App\Models;

use App\Enums\SelfRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShadowingAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'page_number',
        'word_index_start',
        'word_index_end',
        'sentence_hash',
        'attempts_count',
        'self_rating',
    ];

    protected function casts(): array
    {
        return [
            'self_rating' => SelfRating::class,
            'page_number' => 'integer',
            'word_index_start' => 'integer',
            'word_index_end' => 'integer',
            'attempts_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
