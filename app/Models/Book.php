<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'short_description',
        'description',
        'author',
        'author_id',
        'category',
        'category_id',
        'language_locale',
        'language_id',
        'level',
        'difficulty',
        'source_type',
        'cover_path',
        'visibility',
        'access_type',
        'content',
        'total_words',
        'processing_status',
        'is_featured',
        'published_at',
        'archived_at',
        'original_book_id',
        'owner_id',
        'user_id',
        'created_by',
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
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'original_book_id' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bookOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorRelation(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function languageRelation(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
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

    public function scopeAccessType($query, string $type)
    {
        return $query->where('access_type', $type);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('processing_status', $status);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('author', 'like', "%{$term}%");
        });
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isFeatured(): bool
    {
        return (bool) $this->is_featured;
    }

    protected static function booted(): void
    {
        static::creating(function (Book $book): void {
            if (empty($book->slug)) {
                $book->slug = Str::slug($book->title);
            }
        });
    }
}
