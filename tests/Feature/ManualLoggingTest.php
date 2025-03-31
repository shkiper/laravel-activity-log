<?php

namespace Shkiper\ActivityLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\ActivityLogServiceProvider;
use Shkiper\ActivityLog\Facades\ActivityLog;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Templates\TemplateProcessor;
use Shkiper\ActivityLog\Tests\Models\Article;
use Shkiper\ActivityLog\Tests\Models\User;

class ManualLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Run test migrations
        $this->setUpDatabase();

        // Create test models
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        // Clear activity logs
        Activity::truncate();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivityLogServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Activity log config
        $app['config']->set('activity-log.driver', 'mysql');
        $app['config']->set('activity-log.activity_model', Activity::class);
        $app['config']->set('activity-log.table_name', 'activity_logs');
        $app['config']->set('activity-log.default_auth_guard', 'web');
    }

    protected function setUpDatabase()
    {
        // Set up users table
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        // Set up articles table
        $this->app['db']->connection()->getSchemaBuilder()->create('articles', function ($table) {
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

    /** @test */
    public function it_can_log_an_activity_with_description()
    {
        ActivityLog::inLog('default')->withDescription('This is a manual log entry')->log();

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();

        $this->assertEquals('This is a manual log entry', $activity->description);
        $this->assertEquals('default', $activity->log_name);
    }

    /** @test */
    public function it_can_log_an_activity_with_subject()
    {
        ActivityLog::performedOn($this->article)
            ->withDescription('Action on article')
            ->log();

        $activity = Activity::first();

        $this->assertEquals(Article::class, $activity->subject_type);
        $this->assertEquals($this->article->id, $activity->subject_id);
    }

    /** @test */
    public function it_can_log_an_activity_with_causer()
    {
        ActivityLog::causedBy($this->user)
            ->withDescription('Action by user')
            ->log();

        $activity = Activity::first();

        $this->assertEquals(User::class, $activity->causer_type);
        $this->assertEquals($this->user->id, $activity->causer_id);
    }

    /** @test */
    public function it_can_log_an_activity_with_properties()
    {
        $properties = [
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => [
                'key3' => 'value3'
            ]
        ];

        ActivityLog::withProperties($properties)
            ->withDescription('Activity with properties')
            ->log();

        $activity = Activity::first();

        $this->assertEquals($properties, $activity->properties);
        $this->assertEquals('value1', $activity->getExtraProperty('key1'));
        $this->assertEquals('value3', $activity->getExtraProperty('nested.key3'));
    }

    /** @test */
    public function it_can_log_an_activity_with_context()
    {
        $context = [
            'ip' => '127.0.0.1',
            'browser' => 'Chrome',
            'user_agent' => 'Mozilla/5.0'
        ];

        ActivityLog::withContext($context)
            ->withDescription('Activity with context')
            ->log();

        $activity = Activity::first();

        $this->assertEquals($context, $activity->context);
        $this->assertEquals('127.0.0.1', $activity->getContextValue('ip'));
    }

    /** @test */
    public function it_can_log_an_activity_with_template()
    {
        // Register a template first
        TemplateProcessor::registerTemplate('test_action', 'User {causer.name} did {properties.action}');

        ActivityLog::causedBy($this->user)
            ->withEvent('test_action')
            ->withProperties(['action' => 'something'])
            ->withDescription('Default description')
            ->log();

        $activity = Activity::first();

        // Check the template is stored
        $this->assertEquals('test_action', $activity->event);

        // Check the formatted description from the template
        $this->assertEquals('User Test User did something', $activity->presenter()->description());
    }

    /** @test */
    public function it_can_log_an_activity_with_custom_template()
    {
        ActivityLog::causedBy($this->user)
            ->performedOn($this->article)
            ->withTemplate('{causer.name} performed action on {subject.title}')
            ->withDescription('Default description')
            ->log();

        $activity = Activity::first();

        // Check the formatted description from the template
        $this->assertEquals('Test User performed action on Test Article', $activity->presenter()->description());
    }

    /** @test */
    public function it_can_log_activity_in_a_batch()
    {
        $batchUuid = ActivityLog::withBatch()->inLog('batch_test')->withDescription('First in batch')->log()->getBatchUuid();

        ActivityLog::withBatch($batchUuid)
            ->inLog('batch_test')
            ->withDescription('Second in batch')
            ->log();

        ActivityLog::withBatch($batchUuid)
            ->inLog('batch_test')
            ->withDescription('Third in batch')
            ->log();

        $this->assertEquals(3, Activity::count());

        $activities = Activity::all();

        foreach ($activities as $activity) {
            $this->assertEquals($batchUuid, $activity->getExtraProperty('batch_uuid'));
        }
    }

    /** @test */
    public function it_can_log_activity_in_a_custom_log()
    {
        ActivityLog::inLog('custom')
            ->withDescription('Custom log entry')
            ->log();

        $activity = Activity::first();

        $this->assertEquals('custom', $activity->log_name);
    }

    /** @test */
    public function it_can_log_multiple_activities_with_fluent_interface()
    {
        ActivityLog::inLog('log1')
            ->withDescription('First log')
            ->log()
            ->inLog('log2')
            ->withDescription('Second log')
            ->log();

        $this->assertEquals(2, Activity::count());

        $logs = Activity::all();

        $this->assertEquals('log1', $logs[0]->log_name);
        $this->assertEquals('First log', $logs[0]->description);

        $this->assertEquals('log2', $logs[1]->log_name);
        $this->assertEquals('Second log', $logs[1]->description);
    }

    public function it_uses_authenticated_user_as_causer_when_using_causedByCurrentUser()
    {
        $this->actingAs($this->user);

        ActivityLog::causedByCurrentUser()
            ->withDescription('Logged by authenticated user')
            ->log();

        $activity = Activity::first();

        $this->assertEquals(User::class, $activity->causer_type);
        $this->assertEquals($this->user->id, $activity->causer_id);
    }
}
