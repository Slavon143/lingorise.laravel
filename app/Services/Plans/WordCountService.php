<?php

namespace App\Services\Plans;

class WordCountService
{
    public function count(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        preg_match_all("/[\p{L}\p{N}]+(?:[’'ʼ-][\p{L}\p{N}]+)*/u", $text, $matches);

        return count($matches[0] ?? []);
    }
}
