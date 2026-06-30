<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DictionaryEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'original_text' => fake()->word(),
            'translated_text' => fake()->word(),
            'status' => 'new',
        ];
    }
}
