<?php

namespace Shkiper\ActivityLog\Tests;

use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;
use Shkiper\ActivityLog\ActivityLogServiceProvider;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivityLogServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('activity-log.database_connection', 'testing');
        $app['config']->set('activity-log.table_name', 'activity_logs');
        $app['config']->set('activity-log.default_auth_guard', 'web');
    }

    protected function setUpDatabase()
    {
        include_once __DIR__ . '/../database/migrations/2023_01_01_000000_create_activity_logs_table.php';

        (new \CreateActivityLogsTable())->up();

        $this->createTestTables();
        $this->seedModels();
    }

    protected function createTestTables()
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('text')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function seedModels()
    {
        // Override in specific test classes if needed
    }
}
