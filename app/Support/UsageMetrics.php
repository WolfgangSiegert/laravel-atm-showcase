<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UsageMetrics
{
    public const PUBLIC_LANDING_VIEW = 'public_landing_view';

    public const PUBLIC_PAGE_VIEW = 'public_page_view';

    public const SUCCESSFUL_ATM_LOGIN = 'successful_atm_login';

    public const SUCCESSFUL_DEPOSIT = 'successful_deposit';

    public const SUCCESSFUL_WITHDRAWAL = 'successful_withdrawal';

    private const NAMES = [
        self::PUBLIC_LANDING_VIEW,
        self::PUBLIC_PAGE_VIEW,
        self::SUCCESSFUL_ATM_LOGIN,
        self::SUCCESSFUL_DEPOSIT,
        self::SUCCESSFUL_WITHDRAWAL,
    ];

    public function enabled(): bool
    {
        return (bool) config('usage_metrics.enabled');
    }

    public function increment(string $name, ?CarbonInterface $date = null): void
    {
        if (! $this->enabled() || ! in_array($name, self::NAMES, true)) {
            return;
        }

        try {
            $metricDate = ($date ?? now())->toDateString();
            DB::table('usage_metrics')->insertOrIgnore([
                'metric_date' => $metricDate,
                'metric_name' => $name,
                'count' => 0,
            ]);
            DB::table('usage_metrics')
                ->where('metric_date', $metricDate)
                ->where('metric_name', $name)
                ->increment('count');
            $this->pruneExpired();
        } catch (Throwable $exception) {
            // Statistics are deliberately best-effort and must never affect the ATM flow.
            try {
                Log::warning('Usage metric could not be recorded.', [
                    'metric_name' => $name,
                    'exception_class' => $exception::class,
                ]);
            } catch (Throwable) {
                // A secondary logging failure must not leak into the business request either.
            }
        }
    }

    /**
     * @return array{enabled: bool, totals: array<string, int>, daily: list<array{date: string, metrics: array<string, int>}>}
     */
    public function summary(int $days = 30): array
    {
        $empty = array_fill_keys(self::NAMES, 0);

        if (! $this->enabled()) {
            return ['enabled' => false, 'totals' => $empty, 'daily' => []];
        }

        try {
            $rows = DB::table('usage_metrics')
                ->where('metric_date', '>=', now()->startOfDay()->subDays(max(1, $days) - 1)->toDateString())
                ->orderBy('metric_date')
                ->get(['metric_date', 'metric_name', 'count']);

            $totals = $empty;
            foreach (DB::table('usage_metrics')->selectRaw('metric_name, SUM(count) AS aggregate')->groupBy('metric_name')->get() as $row) {
                if (array_key_exists($row->metric_name, $totals)) {
                    $totals[$row->metric_name] = (int) $row->aggregate;
                }
            }

            $daily = $rows->groupBy('metric_date')->map(function ($items, string $date) use ($empty): array {
                $metrics = $empty;
                foreach ($items as $item) {
                    if (array_key_exists($item->metric_name, $metrics)) {
                        $metrics[$item->metric_name] = (int) $item->count;
                    }
                }

                return ['date' => $date, 'metrics' => $metrics];
            })->values()->all();

            return ['enabled' => true, 'totals' => $totals, 'daily' => $daily];
        } catch (Throwable $exception) {
            return ['enabled' => true, 'totals' => $empty, 'daily' => []];
        }
    }

    private function pruneExpired(): void
    {
        $retentionDays = max(1, (int) config('usage_metrics.retention_days'));
        $oldestRetainedDate = now()->startOfDay()->subDays($retentionDays - 1)->toDateString();

        DB::table('usage_metrics')
            ->where('metric_date', '<', $oldestRetainedDate)
            ->delete();
    }
}
