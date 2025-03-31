<?php

namespace Shkiper\ActivityLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\ActivityLogServiceProvider;
use Shkiper\ActivityLog\Facades\ActivityLog;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Tests\Models\Article;
use Shkiper\ActivityLog\Tests\Models\User;

class QueryingActivityTest extends TestCase
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

        // Create some activity logs
        $this->createActivityLogs();
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

    protected function createActivityLogs()
    {
        // Create article logs
        ActivityLog::inLog('articles')
            ->causedBy($this->user)
            ->performedOn($this->article)
            ->withEvent('created')
            ->withDescription('Article was created')
            ->log();

        ActivityLog::inLog('articles')
            ->causedBy($this->user)
            ->performedOn($this->article)
            ->withEvent('updated')
            ->withDescription('Article was updated')
            ->log();

        // Create user logs
        ActivityLog::inLog('users')
            ->causedBy($this->user)
            ->withEvent('login')
            ->withDescription('User logged in')
            ->log();

        ActivityLog::inLog('users')
            ->causedBy($this->user)
            ->withEvent('profile')
            ->withDescription('User updated profile')
            ->log();

        // Create system logs
        ActivityLog::inLog('system')
            ->withEvent('maintenance')
            ->withDescription('System maintenance')
            ->log();
    }

//    /** @test */
    public function it_can_query_activity_by_log_name()
    {
        $articleLogs = Activity::inLog('articles')->get();
        $userLogs = Activity::inLog('users')->get();
        $systemLogs = Activity::inLog('system')->get();

        $this->assertCount(2, $articleLogs);
        $this->assertCount(2, $userLogs);
        $this->assertCount(1, $systemLogs);
    }

//    /** @test */
    public function it_can_query_activity_by_multiple_log_names()
    {
        $logs = Activity::inLog(['articles', 'users'])->get();

        $this->assertCount(4, $logs);

        foreach ($logs as $log) {
            $this->assertTrue(in_array($log->log_name, ['articles', 'users']));
        }
    }

    /** @test */
    public function it_can_query_activity_by_causer()
    {
        $logs = Activity::causedBy($this->user)->get();

        $this->assertCount(4, $logs);

        foreach ($logs as $log) {
            $this->assertEquals(User::class, $log->causer_type);
            $this->assertEquals($this->user->id, $log->causer_id);
        }
    }

//    /** @test */
    public function it_can_query_activity_by_subject()
    {
        $logs = Activity::forSubject($this->article)->get();

        $this->assertCount(2, $logs);

        foreach ($logs as $log) {
            $this->assertEquals(Article::class, $log->subject_type);
            $this->assertEquals($this->article->id, $log->subject_id);
        }
    }

    /** @test */
    public function it_can_query_activity_by_event()
    {
        $logs = Activity::forEvent('updated')->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('Article was updated', $logs->first()->description);
    }

    /** @test */
    public function it_can_chain_query_scopes()
    {
        $logs = Activity::causedBy($this->user)
            ->forSubject($this->article)
            ->forEvent('updated')
            ->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('Article was updated', $logs->first()->description);
    }

    /** @test */
    public function it_can_use_the_created_at_for_sorting()
    {
        // Add a new log that should be first when sorted by created_at desc
        sleep(1); // Make sure created_at is different
        ActivityLog::inLog('latest')
            ->withDescription('Latest log')
            ->log();

        $logs = Activity::latest()->limit(1)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('Latest log', $logs->first()->description);
    }

    /** @test */
    public function it_can_access_properties_through_the_extra_property_method()
    {
        ActivityLog::inLog('properties')
            ->withProperties([
                'nested' => [
                    'key' => 'value'
                ],
                'simple' => 'test'
            ])
            ->withDescription('Log with properties')
            ->log();

        $log = Activity::inLog('properties')->first();

        $this->assertEquals('value', $log->getExtraProperty('nested.key'));
        $this->assertEquals('test', $log->getExtraProperty('simple'));
        $this->assertNull($log->getExtraProperty('unknown'));
    }

    /** @test */
    public function it_can_access_context_through_the_context_value_method()
    {
        ActivityLog::inLog('context')
            ->withContext([
                'nested' => [
                    'key' => 'value'
                ],
                'simple' => 'test'
            ])
            ->withDescription('Log with context')
            ->log();

        $log = Activity::inLog('context')->first();

        $this->assertEquals('value', $log->getContextValue('nested.key'));
        $this->assertEquals('test', $log->getContextValue('simple'));
        $this->assertNull($log->getContextValue('unknown'));
    }

//    /** @test */
    public function it_can_format_the_description_using_the_presenter()
    {
        ActivityLog::causedBy($this->user)
            ->performedOn($this->article)
            ->withTemplate('{causer.name} performed action on {subject.title}')
            ->withDescription('Default description')
            ->log();

        $log = Activity::latest()->first();

        $this->assertEquals('Test User performed action on Test Article', $log->presenter()->description());
        $this->assertEquals('Test User performed action on Test Article', $log->formatted_description);
    }

//    /** @test */
    public function it_can_convert_the_presenter_to_array()
    {
        ActivityLog::causedBy($this->user)
            ->performedOn($this->article)
            ->withTemplate('{causer.name} performed action on {subject.title}')
            ->withDescription('Default description')
            ->log();

        $log = Activity::latest()->first();
        $array = $log->presenter()->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('raw_description', $array);
        $this->assertArrayHasKey('causer', $array);
        $this->assertArrayHasKey('subject', $array);

        $this->assertEquals('Test User performed action on Test Article', $array['description']);
        $this->assertEquals('Default description', $array['raw_description']);
    }
}
