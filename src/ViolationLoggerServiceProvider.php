<?php

namespace KarimTao\LaravelViolationLogger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class ViolationLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $logger = new ViolationLogger($this->app->storagePath('logs/violations.json'));

        Model::handleLazyLoadingViolationUsing(
            fn (Model $model, string $relation) => $logger->log('lazy', $model, $relation)
        );

        Model::handleDiscardedAttributeViolationUsing(
            fn (Model $model, array $attributes) => $logger->log('discarded', $model, $attributes)
        );

        Model::handleMissingAttributeViolationUsing(
            fn (Model $model, string $attribute) => $logger->log('missing', $model, $attribute)
        );
    }
}
