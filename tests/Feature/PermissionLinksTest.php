<?php

use App\Actions\Roles\SaveMatrix;
use App\Actions\Roles\SyncRolePermissions;
use App\Enums\AccessLevel;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

/*
| Matrix "Tiada" = the module is hidden everywhere. For every role, crawl each
| page it can open and follow every internal link it is shown: none may 403.
*/

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(DatabaseSeeder::class);
});

/** @return list<string> internal paths linked from the HTML */
function internalLinks(string $html): array
{
    preg_match_all('/href="([^"#]+)"/', $html, $m);

    return collect($m[1])
        ->map(fn (string $href) => html_entity_decode($href))
        ->map(fn (string $href) => str_starts_with($href, 'http') ? (parse_url($href, PHP_URL_HOST) === parse_url((string) config('app.url'), PHP_URL_HOST) ? (parse_url($href, PHP_URL_PATH) ?? '/').(($q = parse_url($href, PHP_URL_QUERY)) ? '?'.$q : '') : null) : $href)
        ->filter(fn (?string $p) => is_string($p) && str_starts_with($p, '/') && ! str_starts_with($p, '//'))
        // Files, signed media and actions are covered by their own tests.
        ->reject(fn (string $p) => preg_match('#\.(pdf|csv|xlsx|css|js|png|svg|ico|woff2?)(\?|$)|signature=|/build/|/livewire|log-keluar|/muat-turun|/buka$|/bayar/|/jejak#', $p) === 1)
        ->unique()->values()->all();
}

it('never shows a link to a page the role cannot open', function (RoleName $roleName) {
    $user = User::role($roleName->value)->firstOrFail();
    $this->actingAs($user);

    $pages = app(Navigation::class)->for($user)
        ->flatMap(fn (array $g) => $g['items'])
        ->reject(fn (array $i) => ($i['modal'] ?? null) !== null)
        ->map(fn (array $i) => parse_url($i['href'], PHP_URL_PATH))
        ->push('/tetapan/profil')
        ->unique()->values();

    $broken = [];
    $checked = [];

    foreach ($pages as $page) {
        $response = $this->get($page);
        expect($response->getStatusCode())->toBeLessThan(400, "{$roleName->value}: {$page} returned {$response->getStatusCode()}");

        foreach (internalLinks($response->getContent()) as $link) {
            if (isset($checked[$link])) {
                continue;
            }

            $checked[$link] = true;
            $status = $this->get($link)->baseResponse->getStatusCode();

            if ($status === 403) {
                $broken[] = "{$page} → {$link}";
            }
        }
    }

    expect($broken)->toBe([], "{$roleName->value} sees links it cannot open:\n".implode("\n", $broken));
})->with([RoleName::AdminHq, RoleName::Finance, RoleName::Sales, RoleName::Operations, RoleName::VendorPic]);

it('hides a module everywhere once the matrix sets it to Tiada', function () {
    $admin = User::role(RoleName::SuperAdmin->value)->firstOrFail();
    $sales = User::role(RoleName::Sales->value)->firstOrFail();
    $role = Role::findByName(RoleName::Sales->value);

    $matrix = [$role->id => collect(SyncRolePermissions::levelsFor($role))->map->value->all()];
    $matrix[$role->id]['dashboard'] = AccessLevel::None->value;
    $matrix[$role->id]['notifications'] = AccessLevel::None->value;
    app(SaveMatrix::class)->handle($matrix, $admin);

    $sales->refresh();
    app()['auth']->forgetGuards();
    $this->actingAs($sales);

    // Logged-in users hitting /login land on their first allowed module, not a 403 dashboard.
    $this->get('/login')->assertRedirect(route('home'));
    $this->get('/')->assertRedirect()->assertRedirectContains('/ansuran');
    $this->get('/dashboard')->assertForbidden();

    $html = $this->get('/ansuran')->assertOk()->getContent();
    expect($html)->not->toContain('href="'.route('dashboard').'"')
        ->and($html)->not->toContain('href="'.route('notifications.index').'"')
        ->and($html)->not->toContain('Lihat semua notifikasi');
});
