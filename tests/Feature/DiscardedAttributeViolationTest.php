<?php

use KarimTao\LaravelViolationLogger\Tests\Models\Post;

it('logs attributes discarded by mass assignment instead of throwing', function () {
    $line = __LINE__ + 1;
    $post = Post::query()->create(['title' => 'Second', 'author_id' => 1, 'body' => 'Ignored', 'rating' => 5]);

    expect($post->exists)->toBeTrue()
        ->and(violations())->toBe([
            'tests/Feature/DiscardedAttributeViolationTest.php:' . $line => [
                Post::class => ['discarded' => ['body' => 1, 'rating' => 1]],
            ],
        ]);
});

it('does not log fillable attributes', function () {
    Post::query()->create(['title' => 'Third', 'author_id' => 1]);

    expect(file_exists(storage_path('logs/violations.json')))->toBeFalse();
});
