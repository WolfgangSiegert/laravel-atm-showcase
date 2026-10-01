<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioTrafficRequest;
use App\Support\PortfolioTraffic;
use Illuminate\Http\Response;

class PortfolioTrafficController extends Controller
{
    public function __invoke(StorePortfolioTrafficRequest $request, PortfolioTraffic $traffic): Response
    {
        abort_unless($traffic->enabled(), 404);
        abort_unless($traffic->acceptsOrigin($request->header('Origin')), 403);

        if (! $traffic->isObviousBot($request->userAgent())) {
            $traffic->record(
                $request,
                (string) $request->validated('site'),
                (string) $request->validated('path'),
            );
        }

        return response()->noContent();
    }
}
