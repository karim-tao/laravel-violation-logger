<?php

use KarimTao\LaravelViolationLogger\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

function violations(): array
{
    return json_decode((string) file_get_contents(storage_path('logs/violations.json')), true);
}
