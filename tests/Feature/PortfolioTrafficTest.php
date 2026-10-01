<?php

use App\Models\PortfolioTrafficEvent;
use App\Models\User;
use App\Support\PortfolioTraffic;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

const PORTFOLIO_ORIGIN = 'https://tiny-bits.org';
const PORTFOLIO_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/140.0 Safari/537.36';

beforeEach(function () {
    $this->withoutVite();
    config([
        'portfolio_traffic.enabled' => true,
        'portfolio_traffic.rate_limit_per_minute' => 60,
        'portfolio_traffic.retention_days' => 90,
    ]);
    RateLimiter::clear(app(PortfolioTraffic::class)->rateLimitKey(request()));
});

function portfolioRequest(array $overrides = [], string $origin = PORTFOLIO_ORIGIN, string $userAgent = PORTFOLIO_USER_AGENT, string $ip = '203.0.113.10')
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders(['Origin' => $origin, 'User-Agent' => $userAgent, 'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'])
        ->post('/api/portfolio-traffic', [
            'version' => '1',
            'site' => 'portfolio',
            'path' => '/',
            ...$overrides,
        ]);
}

function portfolioUser(bool $operator, string $role = User::OPERATOR_ROLE_SUPERADMIN): User
{
    return User::create([
        'name' => 'Portfolio Test',
        'email' => Str::uuid().'@example.test',
        'password' => 'test-password',
        'is_operator' => $operator,
        'operator_role' => $role,
    ]);
}

it('stores exactly one minimal event and returns no content without cookies', function () {
    portfolioRequest()
        ->assertNoContent()
        ->assertHeaderMissing('Set-Cookie');

    expect(PortfolioTrafficEvent::count())->toBe(1);
    $event = PortfolioTrafficEvent::firstOrFail();
    expect($event->site)->toBe('portfolio')
        ->and($event->path)->toBe('/')
        ->and($event->visitor_hash)->toMatch('/\A[a-f0-9]{64}\z/')
        ->and(Schema::hasColumn('portfolio_traffic_events', 'ip_address'))->toBeFalse()
        ->and(Schema::hasColumn('portfolio_traffic_events', 'user_agent'))->toBeFalse()
        ->and(Schema::hasColumn('portfolio_traffic_events', 'query'))->toBeFalse();
});

it('accepts every allowlisted portfolio path', function (string $path) {
    portfolioRequest(['path' => $path])->assertNoContent();
    $this->assertDatabaseHas('portfolio_traffic_events', ['path' => $path]);
})->with(['/', '/portfolio/', '/portfolio/en/', '/portfolio-vue/', '/portfolio-vue/en/']);

it('rejects invalid contract fields without storing an event', function (array $payload) {
    portfolioRequest($payload)->assertUnprocessable();
    expect(PortfolioTrafficEvent::count())->toBe(0);
})->with([
    'version' => [['version' => '2']],
    'site' => [['site' => 'other']],
    'path' => [['path' => '/portfolio/private/']],
]);

it('rejects missing and unknown origins', function (?string $origin) {
    $request = test()
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
        ->withHeader('User-Agent', PORTFOLIO_USER_AGENT);

    if ($origin !== null) {
        $request->withHeader('Origin', $origin);
    }

    $request->post('/api/portfolio-traffic', ['version' => '1', 'site' => 'portfolio', 'path' => '/'])
        ->assertForbidden();
    expect(PortfolioTrafficEvent::count())->toBe(0);
})->with([null, 'https://attacker.invalid', 'https://tiny-bits.org.evil.invalid']);

it('stores nothing when disabled or when an obvious bot calls', function () {
    config(['portfolio_traffic.enabled' => false]);
    portfolioRequest()->assertNotFound();

    config(['portfolio_traffic.enabled' => true]);
    portfolioRequest(userAgent: 'Googlebot/2.1 (+http://www.google.com/bot.html)')->assertNoContent();

    expect(PortfolioTrafficEvent::count())->toBe(0);
});

it('rate limits per pseudonymized client ip', function () {
    config(['portfolio_traffic.rate_limit_per_minute' => 2]);

    portfolioRequest()->assertNoContent();
    portfolioRequest()->assertNoContent();
    portfolioRequest()->assertTooManyRequests();
    portfolioRequest(ip: '203.0.113.12')->assertNoContent();

    expect(PortfolioTrafficEvent::count())->toBe(3);
});

it('derives stable and distinct hmac identifiers without storing source values', function () {
    portfolioRequest();
    portfolioRequest(['path' => '/portfolio/']);
    portfolioRequest(['path' => '/portfolio/en/'], ip: '203.0.113.13');
    portfolioRequest(['path' => '/portfolio-vue/'], userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/142.0');

    $hashes = PortfolioTrafficEvent::orderBy('id')->pluck('visitor_hash');
    expect($hashes[0])->toBe($hashes[1])
        ->and($hashes[2])->not->toBe($hashes[0])
        ->and($hashes[3])->not->toBe($hashes[0]);

    $serialized = PortfolioTrafficEvent::all()->toJson();
    expect($serialized)->not->toContain('203.0.113.')
        ->not->toContain('Mozilla')
        ->not->toContain('Chrome')
        ->not->toContain('Firefox');
});

it('prunes events older than the retention window during an accepted request', function () {
    CarbonImmutable::setTestNow('2026-10-01 12:00:00 UTC');
    PortfolioTrafficEvent::insert([
        ['occurred_at' => now()->subDays(91), 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => str_repeat('a', 64)],
        ['occurred_at' => now()->subDays(90), 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => str_repeat('b', 64)],
    ]);

    portfolioRequest()->assertNoContent();

    expect(PortfolioTrafficEvent::where('visitor_hash', str_repeat('a', 64))->exists())->toBeFalse()
        ->and(PortfolioTrafficEvent::where('visitor_hash', str_repeat('b', 64))->exists())->toBeTrue()
        ->and(PortfolioTrafficEvent::count())->toBe(2);
});

it('calculates berlin day boundaries totals repeats returning identifiers and path splits', function () {
    CarbonImmutable::setTestNow('2026-03-30 12:00:00 Europe/Berlin');
    $a = str_repeat('a', 64);
    $b = str_repeat('b', 64);
    $c = str_repeat('c', 64);
    PortfolioTrafficEvent::insert([
        ['occurred_at' => '2026-03-28 12:00:00', 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => $a],
        ['occurred_at' => '2026-03-28 23:30:00', 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => $a],
        ['occurred_at' => '2026-03-29 12:00:00', 'site' => 'portfolio', 'path' => '/portfolio/', 'visitor_hash' => $b],
        ['occurred_at' => '2026-03-29 22:30:00', 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => $a],
        ['occurred_at' => '2026-03-30 10:00:00', 'site' => 'portfolio', 'path' => '/portfolio-vue/', 'visitor_hash' => $c],
    ]);
    $operator = portfolioUser(true);

    $this->actingAs($operator)->get('/admin/portfolio-traffic?from=2026-03-29&to=2026-03-30')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Operator/PortfolioTraffic')
            ->where('report.totals.views', 4)
            ->where('report.totals.unique', 3)
            ->where('report.totals.repeat', 1)
            ->where('report.totals.returning', 1)
            ->where('report.daily.0.date', '2026-03-29')
            ->where('report.daily.0.views', 2)
            ->where('report.daily.1.date', '2026-03-30')
            ->where('report.daily.1.views', 2)
            ->where('report.paths.0.path', '/')
            ->where('report.paths.0.views', 2));
});

it('accepts freely selected periods only inside retention', function () {
    CarbonImmutable::setTestNow('2026-10-01 12:00:00 Europe/Berlin');
    $operator = portfolioUser(true);

    $this->actingAs($operator)->get('/admin/portfolio-traffic?from=2026-09-01&to=2026-09-10')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.filters.from', '2026-09-01')
            ->where('report.filters.to', '2026-09-10')
            ->has('report.daily', 10));

    $this->get('/admin/portfolio-traffic?from=2026-06-01&to=2026-09-10')->assertSessionHasErrors('from');
    $this->get('/admin/portfolio-traffic?from=2026-09-10&to=2026-09-01')->assertSessionHasErrors('from');
});

it('allows only private superadmins to access the report', function () {
    $this->get('/admin/portfolio-traffic')->assertRedirect(route('operator.login'));

    $normal = portfolioUser(false);
    $this->actingAs($normal)->get('/admin/portfolio-traffic')->assertForbidden();

    $viewer = portfolioUser(true, User::OPERATOR_ROLE_VIEWER);
    $this->actingAs($viewer)->get('/admin/portfolio-traffic')->assertForbidden();

    $superadmin = portfolioUser(true);
    $this->actingAs($superadmin)->get('/admin/portfolio-traffic')->assertOk();
});

it('does not expose portfolio traffic data in viewer inertia props', function () {
    $this->seed(DatabaseSeeder::class);
    PortfolioTrafficEvent::create([
        'occurred_at' => now(),
        'site' => 'portfolio',
        'path' => '/',
        'visitor_hash' => str_repeat('f', 64),
    ]);
    $viewer = portfolioUser(true, User::OPERATOR_ROLE_VIEWER);

    $this->actingAs($viewer)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->missing('portfolioTraffic')
        ->where('operatorRole', User::OPERATOR_ROLE_VIEWER));
});
