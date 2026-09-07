<?php

use KarimTao\LaravelViolationLogger\Tests\Models\Post;

it('logs a lazily loaded relation instead of throwing', function () {
    $posts = Post::query()->get();

    $line = __LINE__ + 1;
    $author = $posts->first()->author;

    expect($author->name)->toBe('Karim')
        ->and(violations())->toBe([
            'tests/Feature/LazyLoadingViolationTest.php:' . $line => [
                Post::class => ['lazy' => ['author']],
            ],
        ]);
});

it('does not log an eager loaded relation', function () {
    Post::query()->with('author')->get()->first()->author;

    expect(file_exists(storage_path('logs/violations.json')))->toBeFalse();
});
