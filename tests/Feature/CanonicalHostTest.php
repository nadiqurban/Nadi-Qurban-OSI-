<?php

beforeEach(function () {
    config(['app.url' => 'https://www.appnadiqurban.my']);
    app()->detectEnvironment(fn () => 'staging');
});

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

it('redirects other hosts to the APP_URL host', function () {
    $this->get('https://nadi-qurban-osi.on-forge.com/login?a=1')
        ->assertStatus(301)
        ->assertRedirect('https://www.appnadiqurban.my/login?a=1');
});

it('serves the canonical host normally', function () {
    $this->get('https://www.appnadiqurban.my/login')->assertOk();
});

it('does not redirect webhooks or the health check', function () {
    expect($this->post('https://nadi-qurban-osi.on-forge.com/webhooks/chip')->status())->not->toBe(301);
    $this->get('https://nadi-qurban-osi.on-forge.com/up')->assertOk();
});
