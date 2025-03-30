<?php

namespace Shkiper\ActivityLog;

use Illuminate\Support\ServiceProvider;
use Shkiper\ActivityLog\Contracts\ActivityLogRepository;
//use Shkiper\ActivityLog\Repositories\ClickhouseActivityLogRepository;
//use Shkiper\ActivityLog\Repositories\MongodbActivityLogRepository;
use Shkiper\ActivityLog\Repositories\MysqlActivityLogRepository;
use Shkiper\ActivityLog\Templates\TemplateProcessor;

class ActivityLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/activity-log.php' => config_path('activity-log.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/activity-log.php', 'activity-log');

        // Register default templates
        if (config('activity-log.templates')) {
            TemplateProcessor::registerTemplates(config('activity-log.templates'));
        }

        $this->app->singleton(ActivityLogger::class, function ($app) {
            return new ActivityLogger($app->make(ActivityLogRepository::class));
        });

        $this->app->singleton(ActivityLogRepository::class, function ($app) {
            $driver = config('activity-log.driver', 'mysql');

            return match ($driver) {
                'mysql' => new MysqlActivityLogRepository(),
//                'mongodb' => new MongodbActivityLogRepository(),
//                'clickhouse' => new ClickhouseActivityLogRepository(),
                default => new MysqlActivityLogRepository(),
            };
        });

        $this->app->alias(ActivityLogger::class, 'activity-log');
    }
}
