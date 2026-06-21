<?php

namespace App\Services;

class ReaderTextFormatter
{
    public function pages(string $content, int $wordsPerPage = 350): array
    {
        $blocks = $this->blocks($content);
        $pages = [];
        $page = [];
        $pageWordCount = 0;

        foreach ($blocks as $block) {
            $wordCount = $this->wordCount($block['text']);

            if ($page !== [] && $pageWordCount + $wordCount > $wordsPerPage) {
                $pages[] = $page;
                $page = [];
                $pageWordCount = 0;
            }

            $page[] = $block;
            $pageWordCount += $wordCount;
        }

        if ($page !== []) {
            $pages[] = $page;
        }

        return $pages !== [] ? $pages : [[['type' => 'paragraph', 'text' => '']]];
    }

    public function pageContaining(string $content, string $phrase): int
    {
        $needle = $this->normalizeForSearch($phrase);

        if ($needle === '') {
            return 1;
        }

        foreach ($this->pages($content) as $index => $blocks) {
            $pageText = implode(' ', array_column($blocks, 'text'));

            if (str_contains($this->normalizeForSearch($pageText), $needle)) {
                return $index + 1;
            }
        }

        return 1;
    }

    private function blocks(string $content): array
    {
        $rawBlocks = preg_split('/\R{2,}/u', trim($content), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $blocks = [];

        foreach ($rawBlocks as $rawBlock) {
            $text = preg_replace('/[ \t]+/u', ' ', trim($rawBlock));

            if ($text === '') {
                continue;
            }

            if ($this->isHeading($text)) {
                $blocks[] = ['type' => 'heading', 'text' => $text];

                continue;
            }

            foreach ($this->splitLongParagraph($text) as $paragraph) {
                $blocks[] = ['type' => 'paragraph', 'text' => $paragraph];
            }
        }

        return $blocks;
    }

    private function splitLongParagraph(string $text): array
    {
        if ($this->wordCount($text) <= 130) {
            return [$text];
        }

        $sentences = preg_split('/(?<=[.!?][”"\']?)\s+(?=[A-Z“"\'])/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! $sentences || count($sentences) === 1) {
            $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return array_map(
                fn (array $chunk): string => implode(' ', $chunk),
                array_chunk($words, 90),
            );
        }

        $paragraphs = [];
        $paragraph = [];
        $wordCount = 0;

        foreach ($sentences as $sentence) {
            $sentenceWords = $this->wordCount($sentence);

            if ($paragraph !== [] && $wordCount + $sentenceWords > 90) {
                $paragraphs[] = implode(' ', $paragraph);
                $paragraph = [];
                $wordCount = 0;
            }

            $paragraph[] = trim($sentence);
            $wordCount += $sentenceWords;
        }

        if ($paragraph !== []) {
            $paragraphs[] = implode(' ', $paragraph);
        }

        return $paragraphs;
    }

    private function isHeading(string $text): bool
    {
        if (preg_match('/^(?:chapter|part|book|note|prologue|epilogue)\b/iu', $text)) {
            return $this->wordCount($text) <= 14;
        }

        return (bool) preg_match('/^[IVXLCDM]{1,8}(?:[.\s-]+.{0,80})?$/u', $text);
    }

    private function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function normalizeForSearch(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
