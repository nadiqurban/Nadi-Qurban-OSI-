<?php

use App\Support\UserAgent;

it('parses browser, platform and device', function (string $ua, string $browser, string $platform, string $device) {
    $parsed = UserAgent::parse($ua);

    expect($parsed->browser())->toBe($browser)
        ->and($parsed->platform())->toBe($platform)
        ->and($parsed->device())->toBe($device);
})->with([
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36', 'Chrome', 'Windows 11', 'desktop'],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'Safari', 'iPhone', 'mobile'],
    ['Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36', 'Chrome', 'Android', 'mobile'],
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0', 'Firefox', 'Windows 11', 'desktop'],
]);
