<?php

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Support\Audit;

it('renders Aktiviti Terkini and Audit Log with activity from several users', function () {
    $admin = superAdmin();
    $sales = userWithRoles(RoleName::Sales);
    $finance = userWithRoles(RoleName::Finance);

    Audit::log('user.updated', 'Ujian A', $sales, Severity::Info, causer: $sales);
    Audit::log('user.updated', 'Ujian B', $finance, Severity::Info, causer: $finance);
    Audit::log('user.updated', 'Ujian C', $admin, Severity::Info, causer: $admin);

    $this->actingAs($admin);

    $this->get('/dashboard')->assertOk()->assertSee('Ujian A')->assertSee('(Sales)');
    $this->get('/audit-log')->assertOk()->assertSee('Ujian B');
    $this->get('/audit-log/eksport.csv')->assertOk();
});
