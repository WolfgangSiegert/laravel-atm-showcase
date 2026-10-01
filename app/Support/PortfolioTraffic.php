<?php

namespace App\Support;

use App\Models\PortfolioTrafficEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PortfolioTraffic
{
    public function enabled(): bool
    {
        return (bool) config('portfolio_traffic.enabled');
    }

    public function acceptsOrigin(?string $origin): bool
    {
        return is_string($origin)
            && in_array($origin, config('portfolio_traffic.origins'), true);
    }

    public function isObviousBot(?string $userAgent): bool
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return true;
        }

        return preg_match('/bot|crawler|spider|slurp|headless|lighthouse|curl|wget|python-requests|httpclient/i', $userAgent) === 1;
    }

    public function record(Request $request, string $site, string $path): void
    {
        $now = CarbonImmutable::now('UTC');

        DB::transaction(function () use ($request, $site, $path, $now): void {
            $this->prune($now);

            PortfolioTrafficEvent::create([
                'occurred_at' => $now,
                'site' => $site,
                'path' => $path,
                'visitor_hash' => $this->visitorHash($request),
            ]);
        });
    }

    public function visitorHash(Request $request): string
    {
        $secret = (string) config('app.key');
        if ($secret === '') {
            throw new RuntimeException('APP_KEY is required for portfolio traffic hashing.');
        }

        $ip = (string) $request->ip();
        $signature = $this->userAgentSignature((string) $request->userAgent());

        return hash_hmac('sha256', $ip."\0".$signature, $secret);
    }

    public function rateLimitKey(Request $request): string
    {
        $secret = (string) config('app.key');

        return hash_hmac('sha256', (string) $request->ip(), $secret);
    }

    /**
     * @return array{
     *   filters: array{from: string, to: string, earliest: string, latest: string},
     *   totals: array{views: int, unique: int, repeat: int, returning: int},
     *   daily: list<array{date: string, label: string, views: int, unique: int}>,
     *   paths: list<array{path: string, views: int, unique: int}>
     * }
     */
    public function report(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = (string) config('portfolio_traffic.report_timezone');
        $fromLocal = $from->setTimezone($timezone)->startOfDay();
        $toLocal = $to->setTimezone($timezone)->startOfDay();
        $fromUtc = $fromLocal->utc();
        $untilUtc = $toLocal->addDay()->utc();
        $retentionStartUtc = CarbonImmutable::now('UTC')->subDays($this->retentionDays());

        $period = DB::table('portfolio_traffic_events')
            ->where('site', 'portfolio')
            ->where('occurred_at', '>=', $fromUtc)
            ->where('occurred_at', '<', $untilUtc);

        $views = (clone $period)->count();
        $unique = (clone $period)->distinct()->count('visitor_hash');
        $returning = DB::table('portfolio_traffic_events as current')
            ->where('current.site', 'portfolio')
            ->where('current.occurred_at', '>=', $fromUtc)
            ->where('current.occurred_at', '<', $untilUtc)
            ->whereExists(function ($query) use ($fromUtc, $retentionStartUtc): void {
                $query->selectRaw('1')
                    ->from('portfolio_traffic_events as earlier')
                    ->whereColumn('earlier.visitor_hash', 'current.visitor_hash')
                    ->where('earlier.site', 'portfolio')
                    ->where('earlier.occurred_at', '>=', $retentionStartUtc)
                    ->where('earlier.occurred_at', '<', $fromUtc);
            })
            ->distinct()
            ->count('current.visitor_hash');

        $events = (clone $period)->get(['occurred_at', 'path', 'visitor_hash']);
        $eventsByDay = $events->groupBy(fn ($event) => CarbonImmutable::parse($event->occurred_at, 'UTC')->setTimezone($timezone)->toDateString());
        $daily = [];
        for ($date = $fromLocal; $date->lte($toLocal); $date = $date->addDay()) {
            $items = $eventsByDay->get($date->toDateString(), collect());
            $daily[] = [
                'date' => $date->toDateString(),
                'label' => $date->locale(app()->getLocale())->translatedFormat('d. M'),
                'views' => $items->count(),
                'unique' => $items->pluck('visitor_hash')->unique()->count(),
            ];
        }

        $pathCounts = $events->groupBy('path');
        $paths = collect(config('portfolio_traffic.paths'))->map(function (string $path) use ($pathCounts): array {
            $items = $pathCounts->get($path, collect());

            return [
                'path' => $path,
                'views' => $items->count(),
                'unique' => $items->pluck('visitor_hash')->unique()->count(),
            ];
        })->all();

        return [
            'filters' => [
                'from' => $fromLocal->toDateString(),
                'to' => $toLocal->toDateString(),
                'earliest' => CarbonImmutable::now($timezone)->startOfDay()->subDays($this->retentionDays() - 1)->toDateString(),
                'latest' => CarbonImmutable::now($timezone)->toDateString(),
            ],
            'totals' => [
                'views' => $views,
                'unique' => $unique,
                'repeat' => $views - $unique,
                'returning' => $returning,
            ],
            'daily' => $daily,
            'paths' => $paths,
        ];
    }

    public function prune(?CarbonInterface $now = null): int
    {
        $cutoff = CarbonImmutable::instance($now ?? CarbonImmutable::now('UTC'))
            ->utc()
            ->subDays($this->retentionDays());

        return DB::table('portfolio_traffic_events')
            ->where('occurred_at', '<', $cutoff)
            ->delete();
    }

    private function retentionDays(): int
    {
        return max(1, (int) config('portfolio_traffic.retention_days'));
    }

    private function userAgentSignature(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);
        $browser = match (true) {
            str_contains($userAgent, 'edg/') => 'edge',
            str_contains($userAgent, 'firefox/') => 'firefox',
            str_contains($userAgent, 'chrome/'), str_contains($userAgent, 'crios/') => 'chrome',
            str_contains($userAgent, 'safari/') => 'safari',
            default => 'other',
        };
        $platform = match (true) {
            str_contains($userAgent, 'android') => 'android',
            str_contains($userAgent, 'iphone'), str_contains($userAgent, 'ipad') => 'ios',
            str_contains($userAgent, 'windows') => 'windows',
            str_contains($userAgent, 'mac os') => 'macos',
            str_contains($userAgent, 'linux') => 'linux',
            default => 'other',
        };

        return $browser.':'.$platform;
    }
}
