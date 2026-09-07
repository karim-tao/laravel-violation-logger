<?php

use KarimTao\LaravelViolationLogger\Tests\Models\Post;

it('logs an attribute that was not selected instead of throwing', function () {
    $post = Post::query()->select('id')->firstOrFail();

    $line = __LINE__ + 1;
    $title = $post->title;

    expect($title)->toBeNull()
        ->and(violations())->toBe([
            'tests/Feature/MissingAttributeViolationTest.php:' . $line => [
                Post::class => ['missing' => ['title']],
            ],
        ]);
});

it('does not log a selected attribute', function () {
    Post::query()->select('id', 'title')->firstOrFail()->title;

    expect(file_exists(storage_path('logs/violations.json')))->toBeFalse();
});
