<?php

namespace App\Http\Controllers;

use App\Support\PortfolioTraffic;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioTrafficReportController extends Controller
{
    public function __invoke(Request $request, PortfolioTraffic $traffic): Response
    {
        abort_unless($request->user()?->canManageAdministration(), 403);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
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

        return Inertia::render('Operator/PortfolioTraffic', [
            'operatorName' => $request->user()->name,
            'operatorRole' => $request->user()->operator_role,
            'report' => $traffic->report($from, $to),
            'retentionDays' => max(1, (int) config('portfolio_traffic.retention_days')),
        ]);
    }
}
