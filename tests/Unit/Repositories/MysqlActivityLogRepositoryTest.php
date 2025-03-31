<?php

namespace Shkiper\ActivityLog\Tests\Unit\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Repositories\MysqlActivityLogRepository;
use Shkiper\ActivityLog\Tests\Models\TestModel;
use Shkiper\ActivityLog\Tests\Models\TestUser;

class MysqlActivityLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;
    protected $user;
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');

        // Set up repository
        $this->repository = new MysqlActivityLogRepository();

        // Create test instances
        $this->user = new TestUser(['id' => 1, 'name' => 'Test User']);
        $this->model = new TestModel(['id' => 1, 'name' => 'Test Model']);
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up database connection
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up activity log model
        $app['config']->set('activity-log.activity_model', Activity::class);
        $app['config']->set('activity-log.table_name', 'activity_logs');
    }

    /** @test */
    public function it_can_create_an_activity_log()
    {
        $result = $this->repository->log(
            'test_log',
            'Test Description',
            ['key' => 'value'],
            $this->model,
            $this->user,
            'created',
            ['ip' => '127.0.0.1'],
            'Template: {causer.name}'
        );

        $this->assertInstanceOf(Activity::class, $result);
        $this->assertEquals('test_log', $result->log_name);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals(['key' => 'value'], $result->properties);
        $this->assertEquals(['ip' => '127.0.0.1'], $result->context);
        $this->assertEquals('Template: {causer.name}', $result->template);
        $this->assertEquals('created', $result->event);
        $this->assertEquals(TestModel::class, $result->subject_type);
        $this->assertEquals(1, $result->subject_id);
        $this->assertEquals(TestUser::class, $result->causer_type);
        $this->assertEquals(1, $result->causer_id);
    }

    /** @test */
    public function it_can_find_logs_by_subject()
    {
        // Create a log
        $this->repository->log(
            'test_log',
            'Test Description',
            [],
            $this->model,
            $this->user,
            'created'
        );

        $logs = $this->repository->findBySubject($this->model);

        $this->assertCount(1, $logs);
        $this->assertEquals('Test Description', $logs->first()->description);
    }

    /** @test */
    public function it_can_find_logs_by_causer()
    {
        // Create a log
        $this->repository->log(
            'test_log',
            'Test Description',
            [],
            $this->model,
            $this->user,
            'created'
        );

        $logs = $this->repository->findByCauser($this->user);

        $this->assertCount(1, $logs);
        $this->assertEquals('Test Description', $logs->first()->description);
    }

    /** @test */
    public function it_can_find_logs_by_log_name()
    {
        // Create logs with different names
        $this->repository->log('log_1', 'Log 1', [], null, null, 'created');
        $this->repository->log('log_2', 'Log 2', [], null, null, 'created');

        $logs = $this->repository->findByLogName('log_1');

        $this->assertCount(1, $logs);
        $this->assertEquals('Log 1', $logs->first()->description);
    }

    /** @test */
    public function it_can_find_logs_by_event()
    {
        // Create logs with different events
        $this->repository->log('test_log', 'Created Log', [], null, null, 'created');
        $this->repository->log('test_log', 'Updated Log', [], null, null, 'updated');

        $logs = $this->repository->findByEvent('updated');

        $this->assertCount(1, $logs);
        $this->assertEquals('Updated Log', $logs->first()->description);
    }

    /** @test */
    public function it_can_clean_old_records()
    {
        // Create a log with old date
        $oldActivity = new Activity([
            'log_name' => 'test_log',
            'description' => 'Old Log',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);
        $oldActivity->save();

        // Create a recent log
        $this->repository->log('test_log', 'Recent Log', [], null, null, 'created');

        // Clean logs older than 5 days
        $deleted = $this->repository->clean(5);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, Activity::count());
        $this->assertEquals('Recent Log', Activity::first()->description);
    }
}
