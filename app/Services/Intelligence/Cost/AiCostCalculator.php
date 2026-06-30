<?php

namespace App\Services\Intelligence\Cost;

use App\Enums\CostCalculationType;

class AiCostCalculator
{
    public function __construct(
        private readonly PricingRegistry $pricing,
    ) {}

    /**
     * @return array{cost: float, calculationType: string, pricingVersion: string|null}
     */
    public function calculateTextCost(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        ?int $cachedInputTokens = null,
    ): array {
        $pricing = $this->pricing->getModelPricing($provider, $model);

        if (! $pricing) {
            return [
                'cost' => 0.0,
                'calculationType' => CostCalculationType::Unknown->value,
                'pricingVersion' => null,
            ];
        }

        $inputPrice = $this->pricing->getInputPricePerMillion($provider, $model);
        $cachedInputPrice = $this->pricing->getCachedInputPricePerMillion($provider, $model);
        $outputPrice = $this->pricing->getOutputPricePerMillion($provider, $model);

        if ($inputPrice === null || $outputPrice === null) {
            return [
                'cost' => 0.0,
                'calculationType' => CostCalculationType::Unknown->value,
                'pricingVersion' => $this->pricing->getPricingVersion($provider, $model),
            ];
        }

        $nonCachedInput = $inputTokens - ($cachedInputTokens ?? 0);
        $inputCost = ($nonCachedInput / 1_000_000) * $inputPrice;

        if ($cachedInputPrice !== null && ($cachedInputTokens ?? 0) > 0) {
            $inputCost += (($cachedInputTokens ?? 0) / 1_000_000) * $cachedInputPrice;
        }

        $outputCost = ($outputTokens / 1_000_000) * $outputPrice;

        return [
            'cost' => $inputCost + $outputCost,
            'calculationType' => CostCalculationType::TokenEstimate->value,
            'pricingVersion' => $this->pricing->getPricingVersion($provider, $model),
        ];
    }

    /**
     * @return array{cost: float, calculationType: string, pricingVersion: string|null}
     */
    public function calculateCacheHitSavedCost(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        ?int $cachedInputTokens = null,
    ): array {
        $result = $this->calculateTextCost($provider, $model, $inputTokens, $outputTokens, $cachedInputTokens);
        $result['calculationType'] = CostCalculationType::CacheReference->value;

        return $result;
    }

    /**
     * @return array{cost: float, calculationType: string, pricingVersion: string|null}
     */
    public function estimateFromDuration(
        string $provider,
        string $model,
        int $durationMs,
    ): array {
        return [
            'cost' => 0.0,
            'calculationType' => CostCalculationType::DurationEstimate->value,
            'pricingVersion' => $this->pricing->getPricingVersion($provider, $model),
        ];
    }

    /**
     * @return array{cost: float, calculationType: string, pricingVersion: string|null}
     */
    public function estimateFromCharacters(
        string $provider,
        string $model,
        int $characters,
    ): array {
        return [
            'cost' => 0.0,
            'calculationType' => CostCalculationType::CharacterEstimate->value,
            'pricingVersion' => $this->pricing->getPricingVersion($provider, $model),
        ];
    }
}
