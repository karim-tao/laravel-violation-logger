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
        'tests/Unit/ViolationLoggerTest.php:' . $first => [Post::class => ['lazy' => ['author' => 1]]],
        'tests/Unit/ViolationLoggerTest.php:' . $second => [Post::class => ['missing' => ['title' => 1]]],
    ]);
});

it('counts the occurrences of each detail per call site, model and type', function () {
    foreach ([1, 2] as $attempt) {
        $first = __LINE__ + 1;
        $this->logger->log('discarded', new Post, ['body', 'rating']);
        $second = __LINE__ + 1;
        $this->logger->log('discarded', new Post, 'body');
    }

    expect(violations())->toBe([
        'tests/Unit/ViolationLoggerTest.php:' . $first => [Post::class => ['discarded' => ['body' => 2, 'rating' => 2]]],
        'tests/Unit/ViolationLoggerTest.php:' . $second => [Post::class => ['discarded' => ['body' => 2]]],
    ]);
});

it('keeps the existing file content', function () {
    file_put_contents(storage_path('logs/violations.json'), json_encode(['app/Http/Controllers/PostController.php:12' => [Post::class => ['lazy' => ['author' => 3]]]]));

    $line = __LINE__ + 1;
    $this->logger->log('lazy', new Post, 'author');

    expect(violations())->toHaveKeys(['app/Http/Controllers/PostController.php:12', 'tests/Unit/ViolationLoggerTest.php:' . $line]);
});

it('writes readable json', function () {
    $line = __LINE__ + 1;
    $this->logger->log('lazy', new Post, 'author');

    expect(file_get_contents(storage_path('logs/violations.json')))
        ->toContain("\n    \"tests/Unit/ViolationLoggerTest.php:" . $line . '"')
        ->toContain("\"lazy\": {\n");
});

it('throws when the file cannot be opened', function () {
    (new ViolationLogger('/nonexistent/violations.json'))->log('lazy', new Post, 'author');
})->throws(ViolationLoggerException::class, 'Cannot open violations file');
