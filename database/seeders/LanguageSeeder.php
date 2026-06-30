<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'supports_translation' => true, 'supports_tts' => true],
            ['code' => 'sv', 'name' => 'Swedish', 'native_name' => 'Svenska', 'supports_translation' => true, 'supports_tts' => false],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'supports_translation' => true, 'supports_tts' => true],
            ['code' => 'uk', 'name' => 'Ukrainian', 'native_name' => 'Українська', 'supports_translation' => true, 'supports_tts' => false],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'supports_translation' => true, 'supports_tts' => true],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'supports_translation' => true, 'supports_tts' => true],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'supports_translation' => true, 'supports_tts' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'supports_translation' => true, 'supports_tts' => false],
        ];

        foreach ($languages as $lang) {
            Language::updateOrCreate(['code' => $lang['code']], $lang);
        }
    }
}
