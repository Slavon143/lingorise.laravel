<?php

namespace App\Services\Intelligence\Usage;

use App\Models\AiUsageEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiUsageAggregator
{
    /**
     * @return array<string, mixed>
     */
    public function overview(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfDay();
        $to ??= now()->endOfDay();

        $base = AiUsageEvent::whereBetween('created_at', [$from, $to]);

        $operations = (clone $base)->count();
        $providerCalls = (clone $base)->where('provider_called', true)->count();
        $cacheHits = (clone $base)->where('cache_hit', true)->count();
        $cacheMisses = (clone $base)->where('cache_hit', false)->count();
        $failed = (clone $base)->where('status', 'failed')->count();
        $budgetBlocked = (clone $base)->where('status', 'budget_blocked')->count();

        $costData = (clone $base)
            ->select(
                DB::raw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost'),
                DB::raw('COALESCE(SUM(saved_cost_usd), 0) as saved_cost'),
            )
            ->first();

        $ttsDuration = (clone $base)
            ->where('operation_type', 'tts')
            ->where('status', 'success')
            ->sum('audio_duration_ms');

        $breakdown = (clone $base)
            ->select('operation_type', DB::raw('COUNT(*) as count'))
            ->groupBy('operation_type')
            ->pluck('count', 'operation_type')
            ->toArray();

        $cacheRate = $operations > 0
            ? round(($cacheHits / $operations) * 100, 1)
            : 0;

        $costWithoutCache = (float) ($costData?->estimated_cost ?? 0) + (float) ($costData?->saved_cost ?? 0);

        return [
            'operations' => $operations,
            'provider_calls' => $providerCalls,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'cache_hit_rate' => $cacheRate,
            'failed' => $failed,
            'budget_blocked' => $budgetBlocked,
            'estimated_cost' => (float) ($costData?->estimated_cost ?? 0),
            'saved_cost' => (float) ($costData?->saved_cost ?? 0),
            'cost_without_cache' => $costWithoutCache,
            'tts_duration_ms' => (int) ($ttsDuration ?? 0),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function costByUser(?Carbon $from = null, ?Carbon $to = null, int $perPage = 20): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        return AiUsageEvent::select(
            'user_id',
            DB::raw('COUNT(*) as operations'),
            DB::raw('SUM(CASE WHEN provider_called = 1 THEN 1 ELSE 0 END) as provider_calls'),
            DB::raw('SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits'),
            DB::raw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost'),
            DB::raw('COALESCE(SUM(saved_cost_usd), 0) as saved_cost'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('estimated_cost')
            ->paginate($perPage)
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function costByBook(?Carbon $from = null, ?Carbon $to = null, int $perPage = 20): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        return AiUsageEvent::select(
            'book_id',
            DB::raw('COUNT(*) as operations'),
            DB::raw('SUM(CASE WHEN provider_called = 1 THEN 1 ELSE 0 END) as provider_calls'),
            DB::raw('SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits'),
            DB::raw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost'),
            DB::raw('COALESCE(SUM(saved_cost_usd), 0) as saved_cost'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('book_id')
            ->groupBy('book_id')
            ->orderByDesc('estimated_cost')
            ->paginate($perPage)
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function costByModel(?Carbon $from = null, ?Carbon $to = null, int $perPage = 20): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        return AiUsageEvent::select(
            'model',
            'provider',
            DB::raw('COUNT(*) as operations'),
            DB::raw('SUM(CASE WHEN provider_called = 1 THEN 1 ELSE 0 END) as provider_calls'),
            DB::raw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost'),
            DB::raw('COALESCE(SUM(saved_cost_usd), 0) as saved_cost'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('model', 'provider')
            ->orderByDesc('estimated_cost')
            ->paginate($perPage)
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dailyStats(?Carbon $from = null, ?Carbon $to = null, int $perPage = 31): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfDay();

        return AiUsageEvent::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as operations'),
            DB::raw('SUM(CASE WHEN provider_called = 1 THEN 1 ELSE 0 END) as provider_calls'),
            DB::raw('SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits'),
            DB::raw('COALESCE(SUM(estimated_cost_usd), 0) as estimated_cost'),
            DB::raw('COALESCE(SUM(saved_cost_usd), 0) as saved_cost'),
            DB::raw('COALESCE(SUM(CASE WHEN operation_type = \'tts\' THEN audio_duration_ms ELSE 0 END), 0) as tts_duration_ms'),
            DB::raw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->paginate($perPage)
            ->toArray();
    }
}
