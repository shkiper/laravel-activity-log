<?php

namespace Shkiper\ActivityLog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\ActivityLogServiceProvider;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Tests\Models\Article;
use Shkiper\ActivityLog\Tests\Models\User;

class ModelEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Run test migrations
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
    public function it_logs_the_creation_of_a_model()
    {
        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
            'status' => 'draft'
        ]);

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('created', $activity->event);
        $this->assertEquals(Article::class, $activity->subject_type);
        $this->assertEquals($article->id, $activity->subject_id);
    }

    /** @test */
    public function it_logs_the_update_of_a_model_with_changed_properties()
    {
        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
            'status' => 'draft'
        ]);

        Activity::truncate();

        $article->update([
            'title' => 'Updated Title',
            'status' => 'published',
            'published_at' => now()
        ]);

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals(Article::class, $activity->subject_type);
        $this->assertEquals($article->id, $activity->subject_id);

        // Check the changes
        $changes = $activity->properties['changes'] ?? [];
        $this->assertArrayHasKey('title', $changes);
        $this->assertArrayHasKey('status', $changes);
        $this->assertArrayHasKey('published_at', $changes);

        $this->assertEquals('Test Article', $changes['title']['old']);
        $this->assertEquals('Updated Title', $changes['title']['new']);
        $this->assertEquals('draft', $changes['status']['old']);
        $this->assertEquals('published', $changes['status']['new']);
    }

    /** @test */
    public function it_does_not_log_update_when_nothing_changed()
    {
        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
        ]);

        Activity::truncate();

        $article->update([
            'title' => 'Test Article', // Same value, no change
            'content' => 'Test Content', // Same value, no change
        ]);

        $this->assertEquals(0, Activity::count());
    }

    /** @test */
    public function it_logs_the_deletion_of_a_model()
    {
        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
        ]);

        Activity::truncate();

        $article->delete();

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('deleted', $activity->event);
        $this->assertEquals(Article::class, $activity->subject_type);
        $this->assertEquals($article->id, $activity->subject_id);
    }

    public function it_logs_activity_with_causer_when_authenticated()
    {
        // Create a user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Auth user
        $this->actingAs($user);

        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test Content',
            'user_id' => $user->id,
        ]);

        $activity = Activity::first();

        $this->assertEquals(User::class, $activity->causer_type);
        $this->assertEquals($user->id, $activity->causer_id);
    }
}
