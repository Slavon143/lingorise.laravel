<?php

namespace App\Services\Intelligence\Cost;

use Illuminate\Support\Arr;

class PricingRegistry
{
    /**
     * @return array<string, mixed>|null
     */
    public function getModelPricing(string $provider, string $model): ?array
    {
        return config("ai_pricing.{$provider}.models.{$model}");
    }

    public function getInputPricePerMillion(string $provider, string $model): ?float
    {
        return Arr::get($this->getModelPricing($provider, $model), 'input_per_million_tokens');
    }

    public function getCachedInputPricePerMillion(string $provider, string $model): ?float
    {
        return Arr::get($this->getModelPricing($provider, $model), 'cached_input_per_million_tokens');
    }

    public function getOutputPricePerMillion(string $provider, string $model): ?float
    {
        return Arr::get($this->getModelPricing($provider, $model), 'output_per_million_tokens');
    }

    public function getPricingVersion(string $provider, string $model): ?string
    {
        return Arr::get($this->getModelPricing($provider, $model), 'pricing_version');
    }
}
