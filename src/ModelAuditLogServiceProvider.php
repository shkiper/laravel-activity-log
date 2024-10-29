<?php

namespace Shkiper\ModelAuditLog;

use Illuminate\Support\ServiceProvider;

class ModelAuditLogServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}