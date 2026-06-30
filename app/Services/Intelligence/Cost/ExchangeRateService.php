<?php

namespace App\Services\Intelligence\Cost;

class ExchangeRateService
{
    public function getRate(): float
    {
        return (float) config('ai_pricing.usd_to_sek_rate', 10.5);
    }

    public function usdToSek(float $usd): float
    {
        return round($usd * $this->getRate(), 2);
    }

    public function updatedAt(): ?string
    {
        return config('ai_pricing.exchange_rate_updated_at');
    }
}
