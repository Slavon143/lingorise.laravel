<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SpeechController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        abort_unless(config('services.openai.key'), 503, 'Natural voice is not configured.');

        try {
            $audio = Http::withToken(config('services.openai.key'))
                ->accept('audio/mpeg')
                ->timeout(30)
                ->retry(2, 250)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => config('services.openai.tts_model', 'gpt-4o-mini-tts'),
                    'voice' => config('services.openai.tts_voice', 'marin'),
                    'input' => $validated['text'],
                    'instructions' => sprintf(
                        'Speak naturally and warmly in locale %s. Use clear pronunciation, gentle expression, and the unhurried pace of a skilled language teacher. Do not sound theatrical or robotic.',
                        $validated['locale'] ?? 'en',
                    ),
                    'response_format' => 'mp3',
                ]);

            $audio->throw();

            return response($audio->body(), 200, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, max-age=86400',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            abort(503, 'Natural voice is temporarily unavailable.');
        }
    }
}
