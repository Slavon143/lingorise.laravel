<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use PharData;
use RecursiveIteratorIterator;
use RuntimeException;

class EpubTextExtractor
{
    public function metadata(string $path): array
    {
        try {
            $archive = new PharData($path);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The EPUB file could not be opened.', previous: $exception);
        }

        $packagePath = $this->packagePath($archive);

        if (! $packagePath) {
            return [];
        }

        $packageXml = file_get_contents($packagePath);

        if ($packageXml === false) {
            return [];
        }

        $package = new DOMDocument;

        if (! @$package->loadXML($packageXml, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [];
        }

        $xpath = new DOMXPath($package);
        $value = fn (string $name): ?string => $this->metadataValue($xpath, $name);
        $language = $value('language');

        return array_filter([
            'title' => $value('title'),
            'author' => $value('creator'),
            'language_locale' => $language ? strtolower(substr($language, 0, 2)) : null,
        ], fn ($item) => $item !== null && $item !== '');
    }

    public function extract(string $path): string
    {
        try {
            $archive = new PharData($path);
        } catch (\Throwable $exception) {
            throw new RuntimeException('The EPUB file could not be opened.', previous: $exception);
        }

        $chapters = [];

        foreach ($this->orderedDocuments($archive) as $document) {
            $extension = strtolower(pathinfo($document['filename'], PATHINFO_EXTENSION));
            $filename = strtolower($document['filename']);

            if (! in_array($extension, ['html', 'htm', 'xhtml'], true)) {
                continue;
            }

            if (preg_match('/(?:^|[-_.])(toc|nav|cover)(?:[-_.]|$)/i', $filename)) {
                continue;
            }

            $html = file_get_contents($document['path']);

            if ($html === false) {
                continue;
            }

            $document = new DOMDocument;
            @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

            foreach (iterator_to_array($document->getElementsByTagName('script')) as $node) {
                $node->parentNode?->removeChild($node);
            }

            foreach (iterator_to_array($document->getElementsByTagName('style')) as $node) {
                $node->parentNode?->removeChild($node);
            }

            $xpath = new DOMXPath($document);
            $blocks = [];

            foreach ($xpath->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::p or self::blockquote]') ?: [] as $node) {
                $text = preg_replace('/\s+/u', ' ', trim($node->textContent));

                if ($text !== '') {
                    $blocks[] = $text;
                }
            }

            if ($blocks === []) {
                $body = $document->getElementsByTagName('body')->item(0);
                $text = preg_replace('/\s+/u', ' ', trim($body?->textContent ?? $document->textContent));

                if ($text !== '') {
                    $blocks[] = $text;
                }
            }

            if ($blocks !== []) {
                $chapters[] = implode("\n\n", $blocks);
            }
        }

        $content = trim(implode("\n\n", $chapters));

        if ($content === '') {
            throw new RuntimeException('No readable text was found in this EPUB.');
        }

        return $content;
    }

    private function orderedDocuments(PharData $archive): array
    {
        $documents = [];
        $iterator = new RecursiveIteratorIterator($archive);

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));

            if (in_array($extension, ['html', 'htm', 'xhtml'], true)) {
                $documents[] = [
                    'path' => $path,
                    'filename' => $file->getFilename(),
                ];
            }
        }

        $packagePath = $this->packagePath($archive);

        if (! $packagePath) {
            return $documents;
        }

        $packageXml = file_get_contents($packagePath);

        if ($packageXml === false) {
            return $documents;
        }

        $package = new DOMDocument;

        if (! @$package->loadXML($packageXml, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return $documents;
        }

        $xpath = new DOMXPath($package);
        $manifest = [];

        foreach ($xpath->query('//*[local-name()="manifest"]/*[local-name()="item"]') ?: [] as $item) {
            $manifest[$item->getAttribute('id')] = $item->getAttribute('href');
        }

        $ordered = [];
        $packageDirectory = dirname($packagePath);

        foreach ($xpath->query('//*[local-name()="spine"]/*[local-name()="itemref"]') ?: [] as $itemref) {
            $href = $manifest[$itemref->getAttribute('idref')] ?? null;

            if (! $href) {
                continue;
            }

            $expectedSuffix = str_replace('\\', '/', $packageDirectory.'/'.$href);

            foreach ($documents as $document) {
                if ($document['path'] === $expectedSuffix || str_ends_with($document['path'], '/'.$href)) {
                    $ordered[] = $document;
                    break;
                }
            }
        }

        return $ordered !== [] ? $ordered : $documents;
    }

    private function packagePath(PharData $archive): ?string
    {
        $iterator = new RecursiveIteratorIterator($archive);

        foreach ($iterator as $file) {
            if (strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION)) === 'opf') {
                return str_replace('\\', '/', $file->getPathname());
            }
        }

        return null;
    }

    private function metadataValue(DOMXPath $xpath, string $name): ?string
    {
        $node = $xpath->query('//*[local-name()="metadata"]/*[local-name()="'.$name.'"]')->item(0);
        $value = preg_replace('/\s+/u', ' ', trim($node?->textContent ?? ''));

        return $value !== '' ? $value : null;
    }

    public function extractCover(string $path): ?array
    {
        try {
            $archive = new PharData($path);
        } catch (\Throwable) {
            return null;
        }

        $images = [];
        $iterator = new RecursiveIteratorIterator($archive);

        foreach ($iterator as $file) {
            $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $images[] = [
                'path' => $file->getPathname(),
                'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
                'score' => preg_match('/cover|front/i', $file->getFilename()) ? 100 : 1,
            ];
        }

        usort($images, fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        $cover = $images[0] ?? null;

        if (! $cover) {
            return null;
        }

        $contents = file_get_contents($cover['path']);

        if ($contents === false || $contents === '') {
            return null;
        }

        return [
            'contents' => $contents,
            'extension' => $cover['extension'],
        ];
    }
}
