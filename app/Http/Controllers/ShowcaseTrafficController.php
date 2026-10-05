<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShowcaseTrafficRequest;
use App\Support\ShowcaseTraffic;
use Illuminate\Http\Response;

class ShowcaseTrafficController extends Controller
{
    public function __invoke(StoreShowcaseTrafficRequest $request, ShowcaseTraffic $traffic): Response
    {
        abort_unless($traffic->enabled(), 404);
        abort_unless($traffic->acceptsOrigin($request->header('Origin')), 403);

        if (! $traffic->isObviousBot($request->userAgent())) {
            $traffic->record(
                (string) $request->validated('site'),
                (string) $request->validated('path'),
            );
        }

        return response()->noContent();
    }
}
