<?php

namespace Shkiper\ModelAuditLog;

use Illuminate\Support\ServiceProvider;

class ModelAuditLogServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'../../config/model-audit-log.php', 'model-audit-log'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'../../config/model-audit-log.php' => config_path('model-audit-log.php'),
        ]);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ]);
    }
}
