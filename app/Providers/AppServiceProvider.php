<?php

namespace App\Providers;

use App\Services\Intelligence\Budget\AiBudgetGuard;
use App\Services\Intelligence\Cache\AiCacheKeyFactory;
use App\Services\Intelligence\Cache\AiTextNormalizer;
use App\Services\Intelligence\Cache\ExplanationCacheRepository;
use App\Services\Intelligence\Cache\TranslationCacheRepository;
use App\Services\Intelligence\Cache\TtsCacheRepository;
use App\Services\Intelligence\Contracts\AiProviderInterface;
use App\Services\Intelligence\Cost\AiCostCalculator;
use App\Services\Intelligence\Cost\ExchangeRateService;
use App\Services\Intelligence\Cost\PricingRegistry;
use App\Services\Intelligence\Providers\OpenAiProvider;
use App\Services\Intelligence\Subscription\AiQuotaGuard;
use App\Services\Intelligence\Subscription\BookAccessService;
use App\Services\Intelligence\Subscription\EffectiveAiLimitsResolver;
use App\Services\Intelligence\Subscription\SubscriptionResolver;
use App\Services\Intelligence\Subscription\UserQuotaService;
use App\Services\Intelligence\Tts\TtsFileManager;
use App\Services\Intelligence\Usage\AiUsageRecorder;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiTextNormalizer::class);
        $this->app->singleton(AiCacheKeyFactory::class);
        $this->app->singleton(PricingRegistry::class);
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(AiUsageRecorder::class);

        $this->app->singleton(SubscriptionResolver::class);
        $this->app->singleton(UserQuotaService::class);
        $this->app->singleton(EffectiveAiLimitsResolver::class);
        $this->app->singleton(AiQuotaGuard::class);
        $this->app->singleton(BookAccessService::class);

        $this->app->singleton(TranslationCacheRepository::class, function ($app) {
            return new TranslationCacheRepository(
                $app->make(AiTextNormalizer::class),
                $app->make(AiCacheKeyFactory::class),
            );
        });

        $this->app->singleton(ExplanationCacheRepository::class, function ($app) {
            return new ExplanationCacheRepository(
                $app->make(AiTextNormalizer::class),
                $app->make(AiCacheKeyFactory::class),
            );
        });

        $this->app->singleton(TtsCacheRepository::class, function ($app) {
            return new TtsCacheRepository(
                $app->make(AiTextNormalizer::class),
                $app->make(AiCacheKeyFactory::class),
            );
        });

        $this->app->singleton(TtsFileManager::class, function ($app) {
            return new TtsFileManager($app->make(TtsCacheRepository::class));
        });

        $this->app->singleton(AiCostCalculator::class, function ($app) {
            return new AiCostCalculator($app->make(PricingRegistry::class));
        });

        $this->app->bind(AiProviderInterface::class, OpenAiProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('ai-translation', function (Request $request): Limit {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('ai-speech', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
