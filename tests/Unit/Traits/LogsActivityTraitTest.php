<?php

namespace Shkiper\ActivityLog\Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Traits\LogsActivity;

class LogsActivityTraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');

        // Set up models
        $this->setUpTestModel();
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

        // Set up activity log config
        $app['config']->set('activity-log.activity_model', Activity::class);
        $app['config']->set('activity-log.table_name', 'activity_logs');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Shkiper\ActivityLog\ActivityLogServiceProvider::class,
        ];
    }

    protected function setUpTestModel()
    {
        $tableName = 'test_models';

        if (!$this->app['db']->getSchemaBuilder()->hasTable($tableName)) {
            $this->app['db']->getSchemaBuilder()->create($tableName, function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->string('text')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /** @test */
    public function it_logs_activity_when_creating_a_model()
    {
        $model = LogActivityModel::create(['name' => 'test name']);

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('created', $activity->event);
        $this->assertEquals($model->id, $activity->subject_id);
        $this->assertEquals(LogActivityModel::class, $activity->subject_type);
        $this->assertEquals('test_model', $activity->log_name);
    }

    /** @test */
    public function it_logs_activity_when_updating_a_model()
    {
        $model = LogActivityModel::create(['name' => 'test name']);

        Activity::truncate();

        $model->name = 'updated name';
        $model->save();

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('updated', $activity->event);
        $this->assertEquals($model->id, $activity->subject_id);

        // Check if changes were logged
        $changes = $activity->properties['changes'] ?? [];
        $this->assertArrayHasKey('name', $changes);
        $this->assertEquals('test name', $changes['name']['old']);
        $this->assertEquals('updated name', $changes['name']['new']);
    }

    /** @test */
    public function it_logs_activity_when_deleting_a_model()
    {
        $model = LogActivityModel::create(['name' => 'test name']);

        Activity::truncate();

        $model->delete();

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $this->assertEquals('deleted', $activity->event);
        $this->assertEquals($model->id, $activity->subject_id);
    }

    /** @test */
    public function it_does_not_log_when_attribute_is_not_in_log_attributes()
    {
        $model = LogActivityModel::create(['name' => 'test name']);

        Activity::truncate();

        $model->text = 'updated text';
        $model->save();

        $this->assertEquals(0, Activity::count());
    }

    /** @test */
    public function it_logs_specified_attributes_when_updating()
    {
        $model = LogActivityModel::create(['name' => 'test name', 'text' => 'test text']);

        Activity::truncate();

        $model->name = 'updated name';
        $model->text = 'updated text';
        $model->save();

        $this->assertEquals(1, Activity::count());

        $activity = Activity::first();
        $changes = $activity->properties['changes'] ?? [];

        $this->assertArrayHasKey('name', $changes);
        $this->assertArrayNotHasKey('text', $changes);
    }

    /** @test */
    public function it_uses_custom_log_name()
    {
        $reflectionClass = new \ReflectionClass(LogActivityModel::class);
        $logNameProperty = $reflectionClass->getProperty('logName');
        $logNameProperty->setAccessible(true);
        $logNameProperty->setValue('custom_log_name');

        $model = LogActivityModel::create(['name' => 'test name']);

        $activity = Activity::first();
        $this->assertEquals('custom_log_name', $activity->log_name);

        $logNameProperty = $reflectionClass->getProperty('logName');
        $logNameProperty->setAccessible(true);
        $logNameProperty->setValue('custom_log_name');
    }

    /** @test */
    public function it_uses_custom_description_method_when_available()
    {
        $model = CustomDescriptionModel::create(['name' => 'test name']);

        $activity = Activity::first();
        $this->assertEquals('Custom created description for test name', $activity->description);
    }

    /** @test */
    public function it_can_use_batch_logging()
    {
        $batchUuid = LogActivityModel::startBatch();

        $model1 = LogActivityModel::create(['name' => 'model 1']);
        $model2 = LogActivityModel::create(['name' => 'model 2']);

        LogActivityModel::endBatch();

        $activities = Activity::all();

        $this->assertEquals(2, $activities->count());

        foreach ($activities as $activity) {
            $this->assertEquals($batchUuid, $activity->properties['batch_uuid']);
        }
    }
}

class LogActivityModel extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['name'];
    protected static $logName = 'test_model';

    protected $table = 'test_models';
    protected $guarded = [];
    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}

class CustomDescriptionModel extends Model
{
    use LogsActivity;

    protected $table = 'test_models';
    protected $guarded = [];

    public function getActivityDescription(string $eventName): string
    {
        return "Custom {$eventName} description for {$this->name}";
    }
}
