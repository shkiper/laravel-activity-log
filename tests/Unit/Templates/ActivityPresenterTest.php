<?php

namespace Shkiper\ActivityLog\Tests\Unit\Templates;

use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Templates\ActivityPresenter;
use Shkiper\ActivityLog\Templates\TemplateProcessor;
use Shkiper\ActivityLog\Tests\Models\TestModel;
use Shkiper\ActivityLog\Tests\Models\TestUser;

class ActivityPresenterTest extends TestCase
{
    /** @test */
    public function it_presents_formatted_description()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'template' => 'Custom template with {properties.key}',
            'properties' => ['key' => 'value'],
        ]);

        $presenter = new ActivityPresenter($activity);

        $this->assertEquals('Custom template with value', $presenter->description());
    }

    /** @test */
    public function it_can_present_with_a_custom_template()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'properties' => ['status' => 'published'],
        ]);

        $presenter = new ActivityPresenter($activity);
        $result = $presenter->renderTemplate('Status: {properties.status}');

        $this->assertEquals('Status: published', $result);
    }

    /** @test */
    public function it_converts_presenter_to_array()
    {
        $user = new TestUser(['id' => 1, 'name' => 'John Doe']);
        $model = new TestModel(['id' => 1, 'name' => 'Test Model']);

        $activity = new Activity([
            'id' => 1,
            'description' => 'Default description',
            'template' => 'Custom template',
            'log_name' => 'test',
            'properties' => ['key' => 'value'],
            'context' => ['ip' => '127.0.0.1'],
            'event' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activity->subject_type = get_class($model);
        $activity->subject_id = $model->id;
        $activity->causer_type = get_class($user);
        $activity->causer_id = $user->id;

        // Mock relations
        $activity->setRelation('subject', $model);
        $activity->setRelation('causer', $user);

        $presenter = new ActivityPresenter($activity);
        $array = $presenter->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('test', $array['log_name']);
        $this->assertEquals('Default description', $array['raw_description']);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('causer', $array);
        $this->assertArrayHasKey('subject', $array);
        $this->assertArrayHasKey('properties', $array);
        $this->assertArrayHasKey('context', $array);
    }

    /** @test */
    public function it_proxies_attributes_to_activity()
    {
        $activity = new Activity([
            'id' => 123,
            'log_name' => 'test_log',
            'description' => 'Test description',
        ]);

        $presenter = new ActivityPresenter($activity);

        $this->assertEquals(123, $presenter->id);
        $this->assertEquals('test_log', $presenter->log_name);
        $this->assertEquals('Test description', $presenter->description);
    }
}
