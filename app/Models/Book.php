<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'author',
        'category',
        'language_locale',
        'level',
        'source_type',
        'cover_path',
        'visibility',
        'content',
        'total_words',
        'processing_status',
        'original_book_id',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'string',
            'author' => 'string',
            'category' => 'string',
            'language_locale' => 'string',
            'level' => 'string',
            'source_type' => 'string',
            'cover_path' => 'string',
            'visibility' => 'string',
            'content' => 'string',
            'total_words' => 'integer',
            'processing_status' => 'string',
            'original_book_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function originalBook(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_book_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(self::class, 'original_book_id');
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function dictionaryEntries(): HasMany
    {
        return $this->hasMany(DictionaryEntry::class);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('author', 'like', "%{$term}%")
              ->orWhere('category', 'like', "%{$term}%");
        });
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }
}
