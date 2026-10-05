<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ShowcaseTraffic
{
    public function enabled(): bool
    {
        return (bool) config('showcase_traffic.enabled');
    }

    public function acceptsOrigin(?string $origin): bool
    {
        return is_string($origin)
            && hash_equals((string) config('showcase_traffic.joinsplit.origin'), $origin);
    }

    public function isObviousBot(?string $userAgent): bool
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return true;
        }

        return preg_match('/bot|crawler|spider|slurp|headless|lighthouse|curl|wget|python-requests|httpclient/i', $userAgent) === 1;
    }

    public function record(string $site, string $path, ?CarbonInterface $now = null): void
    {
        $now = CarbonImmutable::instance($now ?? CarbonImmutable::now('UTC'));
        $visitDate = $now->setTimezone((string) config('showcase_traffic.report_timezone'))->toDateString();

        DB::transaction(function () use ($visitDate, $site, $path, $now): void {
            $this->prune($now);

            DB::statement(
                'INSERT INTO showcase_traffic_daily (visit_date, site, path, view_count) VALUES (?, ?, ?, 1)
                 ON CONFLICT (visit_date, site, path) DO UPDATE
                 SET view_count = showcase_traffic_daily.view_count + 1',
                [$visitDate, $site, $path],
            );
        });
    }

    public function rateLimitKey(): string
    {
        return 'joinsplit|'.(string) config('showcase_traffic.joinsplit.origin');
    }

    /**
     * @return array{
     *   filters: array{from: string, to: string, earliest: string, latest: string},
     *   totals: array{views: int},
     *   daily: list<array{date: string, label: string, views: int}>,
     *   paths: list<array{source: string, path: string, views: int}>
     * }
     */
    public function report(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = (string) config('showcase_traffic.report_timezone');
        $fromLocal = $from->setTimezone($timezone)->startOfDay();
        $toLocal = $to->setTimezone($timezone)->startOfDay();
        $counts = DB::table('showcase_traffic_daily')
            ->where('site', 'joinsplit')
            ->whereBetween('visit_date', [$fromLocal->toDateString(), $toLocal->toDateString()])
            ->pluck('view_count', 'visit_date');
        $daily = [];

        for ($date = $fromLocal; $date->lte($toLocal); $date = $date->addDay()) {
            $daily[] = [
                'date' => $date->toDateString(),
                'label' => $date->locale(app()->getLocale())->translatedFormat('d. M'),
                'views' => (int) ($counts[$date->toDateString()] ?? 0),
            ];
        }

        $views = array_sum(array_column($daily, 'views'));

        return [
            'filters' => [
                'from' => $fromLocal->toDateString(),
                'to' => $toLocal->toDateString(),
                'earliest' => CarbonImmutable::now($timezone)->startOfDay()->subDays($this->retentionDays() - 1)->toDateString(),
                'latest' => CarbonImmutable::now($timezone)->toDateString(),
            ],
            'totals' => ['views' => $views],
            'daily' => $daily,
            'paths' => [[
                'source' => 'JoinSplit',
                'path' => (string) config('showcase_traffic.joinsplit.path'),
                'views' => $views,
            ]],
        ];
    }

    public function prune(?CarbonInterface $now = null): int
    {
        $timezone = (string) config('showcase_traffic.report_timezone');
        $earliest = CarbonImmutable::instance($now ?? CarbonImmutable::now('UTC'))
            ->setTimezone($timezone)
            ->startOfDay()
            ->subDays($this->retentionDays() - 1)
            ->toDateString();

        return DB::table('showcase_traffic_daily')
            ->where('visit_date', '<', $earliest)
            ->delete();
    }

    private function retentionDays(): int
    {
        return max(1, (int) config('showcase_traffic.retention_days'));
    }
}
