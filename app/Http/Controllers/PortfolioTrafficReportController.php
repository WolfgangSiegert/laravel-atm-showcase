<?php

namespace App\Http\Controllers;

use App\Support\PortfolioTraffic;
use App\Support\ShowcaseTraffic;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioTrafficReportController extends Controller
{
    public function __invoke(Request $request, PortfolioTraffic $portfolioTraffic, ShowcaseTraffic $showcaseTraffic): Response
    {
        abort_unless($request->user()?->canManageAdministration(), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'project' => ['nullable', Rule::in(['portfolio', 'joinsplit', 'all'])],
        ]);
        $timezone = (string) config('portfolio_traffic.report_timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $earliest = $today->subDays(max(1, (int) config('portfolio_traffic.retention_days')) - 1);
        $from = isset($validated['from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $validated['from'], $timezone)
            : $today->subDays(29);
        $to = isset($validated['to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $validated['to'], $timezone)
            : $today;

        if ($from->lt($earliest) || $from->gt($today) || $to->lt($from) || $to->gt($today)) {
            throw ValidationException::withMessages([
                'from' => __('Der Zeitraum muss vollständig innerhalb der Aufbewahrungsfrist liegen.'),
            ]);
        }

        $project = (string) ($validated['project'] ?? 'portfolio');
        $portfolioReport = $project !== 'joinsplit' ? $portfolioTraffic->report($from, $to) : null;
        $joinSplitReport = $project !== 'portfolio' ? $showcaseTraffic->report($from, $to) : null;
        $report = match ($project) {
            'portfolio' => ['project' => $project, ...$portfolioReport],
            'joinsplit' => ['project' => $project, ...$joinSplitReport],
            'all' => [
                'project' => $project,
                'filters' => $portfolioReport['filters'],
                'totals' => [
                    'views' => $portfolioReport['totals']['views'] + $joinSplitReport['totals']['views'],
                ],
                'daily' => collect($portfolioReport['daily'])->map(fn (array $day, int $index): array => [
                    'date' => $day['date'],
                    'label' => $day['label'],
                    'views' => $day['views'] + $joinSplitReport['daily'][$index]['views'],
                ])->all(),
                'paths' => [
                    ['source' => 'Portfolio', 'path' => 'Allowlist', 'views' => $portfolioReport['totals']['views']],
                    ['source' => 'JoinSplit', 'path' => (string) config('showcase_traffic.joinsplit.path'), 'views' => $joinSplitReport['totals']['views']],
                ],
            ],
        };

        return Inertia::render('Operator/PortfolioTraffic', [
            'operatorName' => $request->user()->name,
            'operatorRole' => $request->user()->operator_role,
            'report' => $report,
            'retentionDays' => max(1, (int) config('portfolio_traffic.retention_days')),
        ]);
    }
}
