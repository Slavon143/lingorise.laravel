<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_active',
        'supports_translation',
        'supports_tts',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'supports_translation' => 'boolean',
            'supports_tts' => 'boolean',
        ];
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'language_id');
    }

    public function booksCount(): int
    {
        return $this->books()->count();
    }
}
