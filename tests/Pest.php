<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Viewports used by the verification loop (CLAUDE.md)
|--------------------------------------------------------------------------
*/

const DESKTOP = [1440, 900];
const PHONE = [390, 844];
