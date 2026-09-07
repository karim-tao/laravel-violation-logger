<?php

use KarimTao\LaravelViolationLogger\Tests\Models\Post;
use KarimTao\LaravelViolationLogger\ViolationLogger;
use KarimTao\LaravelViolationLogger\ViolationLoggerException;

beforeEach(fn () => $this->logger = new ViolationLogger(storage_path('logs/violations.json')));

it('groups violations by call site, model and type', function () {
    $first = __LINE__ + 1;
    $this->logger->log('lazy', new Post, 'author');
    $second = __LINE__ + 1;
    $this->logger->log('missing', new Post, 'title');

    expect(violations())->toBe([
        'tests/Unit/ViolationLoggerTest.php:' . $first => [Post::class => ['lazy' => ['author']]],
        'tests/Unit/ViolationLoggerTest.php:' . $second => [Post::class => ['missing' => ['title']]],
    ]);
});

it('merges details of the same call site, model and type without duplicates', function () {
    foreach ([1, 2] as $attempt) {
        $first = __LINE__ + 1;
        $this->logger->log('discarded', new Post, ['body', 'rating']);
        $second = __LINE__ + 1;
        $this->logger->log('discarded', new Post, 'body');
    }

    expect(violations())->toBe([
        'tests/Unit/ViolationLoggerTest.php:' . $first => [Post::class => ['discarded' => ['body', 'rating']]],
        'tests/Unit/ViolationLoggerTest.php:' . $second => [Post::class => ['discarded' => ['body']]],
    ]);
});

it('keeps the existing file content', function () {
    file_put_contents(storage_path('logs/violations.json'), json_encode(['app/Http/Controllers/PostController.php:12' => [Post::class => ['lazy' => ['author']]]]));

    $line = __LINE__ + 1;
    $this->logger->log('lazy', new Post, 'author');

    expect(violations())->toHaveKeys(['app/Http/Controllers/PostController.php:12', 'tests/Unit/ViolationLoggerTest.php:' . $line]);
});

it('writes readable json', function () {
    $line = __LINE__ + 1;
    $this->logger->log('lazy', new Post, 'author');

    expect(file_get_contents(storage_path('logs/violations.json')))
        ->toContain("\n    \"tests/Unit/ViolationLoggerTest.php:" . $line . '"')
        ->toContain("\"lazy\": [\n");
});

it('throws when the file cannot be opened', function () {
    (new ViolationLogger('/nonexistent/violations.json'))->log('lazy', new Post, 'author');
})->throws(ViolationLoggerException::class, 'Cannot open violations file');
