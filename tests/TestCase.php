<?php

namespace KarimTao\LaravelViolationLogger\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use KarimTao\LaravelViolationLogger\Tests\Models\Author;
use KarimTao\LaravelViolationLogger\Tests\Models\Post;
use KarimTao\LaravelViolationLogger\ViolationLoggerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ViolationLoggerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Model::shouldBeStrict();
        File::delete(storage_path('logs/violations.json'));

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id');
            $table->string('title');
            $table->string('body')->nullable();
        });

        $author = Author::query()->create(['name' => 'Karim']);
        Post::query()->create(['title' => 'Hello', 'author_id' => $author->id]);
        Post::query()->create(['title' => 'World', 'author_id' => $author->id]);
    }
}
