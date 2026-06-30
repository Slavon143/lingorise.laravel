<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'bio',
        'photo_path',
        'country',
        'birth_year',
        'death_year',
    ];

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'death_year' => 'integer',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function booksCount(): int
    {
        return $this->books()->count();
    }

    protected static function booted(): void
    {
        static::creating(function (Author $author): void {
            if (empty($author->slug)) {
                $author->slug = Str::slug($author->name);
            }
        });
    }
}
