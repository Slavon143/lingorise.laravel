<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'category' => 'Fiction',
            'language_locale' => 'en',
            'level' => 'A2',
            'source_type' => 'text',
            'visibility' => 'private',
            'content' => fake()->paragraphs(3, true),
            'total_words' => 100,
            'processing_status' => 'ready',
        ];
    }
}
