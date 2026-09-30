<?php

use Illuminate\Support\Carbon;

it('formats sen as ringgit', function (int $sen, string $expected) {
    expect(rm($sen))->toBe($expected);
})->with([
    [245000, 'RM 2,450'],
    [85000, 'RM 850'],
    [0, 'RM 0'],
    [245050, 'RM 2,450.50'],
    [-5000, '-RM 50'],
]);

it('forces decimals when asked', function () {
    expect(rm(245000, true))->toBe('RM 2,450.00');
});

it('formats compact ringgit like the dashboard', function (int $sen, string $expected) {
    expect(rm_short($sen))->toBe($expected);
})->with([
    [382000000, 'RM 3.82j'],
    [132000000, 'RM 1.32j'],
    [500000000, 'RM 5j'],
    [16800000, 'RM 168k'],
    [1250000, 'RM 12.5k'],
    [85000, 'RM 850'],
]);

it('formats dates with Malay month names', function (string $date, string $expected) {
    expect(tarikh($date))->toBe($expected);
})->with([
    ['2027-06-12', '12 Jun 2027'],
    ['2027-03-01', '1 Mac 2027'],
    ['2027-05-20', '20 Mei 2027'],
    ['2027-08-09', '9 Ogos 2027'],
    ['2027-10-31', '31 Okt 2027'],
    ['2027-12-25', '25 Dis 2027'],
]);

it('formats date with time and handles empty values', function () {
    expect(tarikh(Carbon::parse('2027-06-12 09:14')->toImmutable(), true))->toBe('12 Jun 2027, 09:14')
        ->and(tarikh(null))->toBe('-');
});

it('builds initials skipping bin/binti', function (string $name, string $expected) {
    expect(initials($name))->toBe($expected);
})->with([
    ['Muhammad Nurfitkri', 'MN'],
    ['Ahmad Zaki bin Hassan', 'AZ'],
    ['Nurul binti Rahim', 'NR'],
    ['Aisyah', 'A'],
]);

it('formats relative time like the design', function () {
    $this->travelTo(Carbon::parse('2027-06-12 15:00'));

    expect(masa_lalu(now()->subMinutes(2)))->toBe('2 minit lalu')
        ->and(masa_lalu(now()->subHours(3)))->toBe('3 jam lalu')
        ->and(masa_lalu(now()->subDay()))->toBe('Semalam')
        ->and(masa_lalu(now()->subDays(4)))->toBe('4 hari lalu')
        ->and(masa_lalu(now()->subDays(20)))->toBe('23 Mei 2027')
        ->and(masa_lalu(null))->toBe('Belum pernah');
});
