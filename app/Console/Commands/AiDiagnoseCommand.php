<?php

namespace App\Console\Commands;

use App\Services\Intelligence\Cache\AiStructuredCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Exceptions\AiProviderException;
use Illuminate\Console\Command;

class AiDiagnoseCommand extends Command
{
    protected $signature = 'ai:diagnose
        {--test-context : Run a live context explanation test against the provider}
        {--test-grammar : Run a live grammar explanation test against the provider}
        {--test-simplify : Run a live simplification test against the provider}
    ';

    protected $description = 'Diagnose AI service configuration and connectivity';

    public function handle(AiProviderInterface $provider, AiStructuredCacheRepository $cache): int
    {
        $this->info('=== AI Diagnostics ===');
        $this->newLine();

        $this->line('Config:');
        $key = config('services.openai.key');
        if ($key) {
            $this->info('  OPENAI_KEY: set (' . mb_substr($key, 0, 8) . '...)');
        } else {
            $this->error('  OPENAI_KEY: not set');
        }

        $this->line('  Cache versions:');
        $this->line('    PROMPT_VERSION: ' . AiStructuredCacheRepository::PROMPT_VERSION);
        $this->line('    RESPONSE_FORMAT_VERSION: ' . AiStructuredCacheRepository::RESPONSE_FORMAT_VERSION);

        $this->newLine();

        $hasTest = $this->option('test-context') || $this->option('test-grammar') || $this->option('test-simplify');
        if (!$hasTest) {
            $this->warn('Pass --test-context, --test-grammar, or --test-simplify to run live provider tests.');
            return self::SUCCESS;
        }

        if (!$key) {
            $this->error('Cannot run tests: OPENAI_KEY not configured.');
            return self::FAILURE;
        }

        try {
            $provider->checkConnectivity();
            $this->info('Connectivity check: OK');
        } catch (AiProviderException $e) {
            $this->error('Connectivity check failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
