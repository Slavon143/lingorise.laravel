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
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function dictionaryEntries(): HasMany
    {
        return $this->hasMany(DictionaryEntry::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }
}
