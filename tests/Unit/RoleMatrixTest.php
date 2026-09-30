<?php

use App\Enums\AccessLevel;
use App\Enums\Module;
use App\Enums\RoleName;

it('has 23 modules and 46 permissions', function () {
    expect(Module::cases())->toHaveCount(23)
        ->and(Module::allPermissions())->toHaveCount(46);
});

it('matches the design matrix for the five design roles', function (RoleName $role, string $module, AccessLevel $level) {
    expect($role->defaultMatrix()[$module])->toBe($level);
})->with([
    [RoleName::AdminHq, 'crm', AccessLevel::View],
    [RoleName::AdminHq, 'webhooks', AccessLevel::None],
    [RoleName::Finance, 'installments', AccessLevel::Full],
    [RoleName::Finance, 'akad', AccessLevel::None],
    [RoleName::Sales, 'products', AccessLevel::Full],
    [RoleName::Sales, 'payments', AccessLevel::None],
    [RoleName::VendorPic, 'execution', AccessLevel::Full],
    [RoleName::VendorPic, 'notifications', AccessLevel::View],
    [RoleName::Operations, 'shipping', AccessLevel::Full],
]);

it('gives Super Admin full access to everything', function () {
    expect(collect(RoleName::SuperAdmin->defaultMatrix())->unique()->all())->toBe(['dashboard' => AccessLevel::Full]);
});

it('cycles Penuh → Lihat → Tiada → Penuh', function () {
    expect(AccessLevel::Full->next())->toBe(AccessLevel::View)
        ->and(AccessLevel::View->next())->toBe(AccessLevel::None)
        ->and(AccessLevel::None->next())->toBe(AccessLevel::Full);
});
