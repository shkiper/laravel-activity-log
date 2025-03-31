<?php

namespace Shkiper\ActivityLog\Tests\Unit\Templates;

use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\Models\Activity;
use Shkiper\ActivityLog\Templates\TemplateProcessor;
use Shkiper\ActivityLog\Tests\Models\TestModel;
use Shkiper\ActivityLog\Tests\Models\TestUser;

class TemplateProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TemplateProcessor::registerTemplates([]);
    }

    /** @test */
    public function it_can_register_and_retrieve_templates()
    {
        TemplateProcessor::registerTemplate('test_event', 'This is a test template');

        $template = TemplateProcessor::getTemplate('test_event');

        $this->assertEquals('This is a test template', $template);
    }

    /** @test */
    public function it_can_register_multiple_templates_at_once()
    {
        TemplateProcessor::registerTemplates([
            'event_1' => 'Template 1',
            'event_2' => 'Template 2',
        ]);

        $this->assertEquals('Template 1', TemplateProcessor::getTemplate('event_1'));
        $this->assertEquals('Template 2', TemplateProcessor::getTemplate('event_2'));
    }

    /** @test */
    public function it_returns_null_for_unknown_template()
    {
        $template = TemplateProcessor::getTemplate('unknown_event');

        $this->assertNull($template);
    }

    /** @test */
    public function it_processes_a_template_with_basic_variables()
    {
        $user = new TestUser(['id' => 1, 'name' => 'John Doe']);
        $model = new TestModel(['id' => 1, 'name' => 'Test Model']);

        $activity = new Activity([
            'description' => 'Default description',
            'template' => '{causer.name} did something to {subject.name}',
            'properties' => ['key' => 'value'],
            'context' => ['ip' => '127.0.0.1'],
            'event' => 'created',
        ]);

        $activity->subject_type = get_class($model);
        $activity->subject_id = $model->id;
        $activity->causer_type = get_class($user);
        $activity->causer_id = $user->id;

        // Mock relations
        $activity->setRelation('subject', $model);
        $activity->setRelation('causer', $user);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('John Doe did something to Test Model', $result);
    }

    /** @test */
    public function it_processes_a_template_with_property_variables()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'template' => 'Action with {properties.status} status',
            'properties' => ['status' => 'completed', 'id' => 123],
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('Action with completed status', $result);
    }

    /** @test */
    public function it_processes_a_template_with_context_variables()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'template' => 'Action from IP {context.ip}',
            'context' => ['ip' => '192.168.1.1', 'browser' => 'Chrome'],
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('Action from IP 192.168.1.1', $result);
    }

    /** @test */
    public function it_processes_a_template_with_changes_variables()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'template' => 'Changed status from {properties.changes.old.status} to {properties.changes.new.status}',
            'properties' => [
                'changes' => [
                    'old' => ['status' => 'draft'],
                    'new' => ['status' => 'published'],
                ]
            ],
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('Changed status from draft to published', $result);
    }

    /** @test */
    public function it_falls_back_to_description_when_no_template_available()
    {
        $activity = new Activity([
            'description' => 'This is the fallback description',
            'template' => null,
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('This is the fallback description', $result);
    }

    /** @test */
    public function it_uses_event_template_when_activity_template_is_null()
    {
        TemplateProcessor::registerTemplate('custom_event', 'Template from event: {properties.key}');

        $activity = new Activity([
            'description' => 'Default description',
            'template' => null,
            'event' => 'custom_event',
            'properties' => ['key' => 'test value'],
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('Template from event: test value', $result);
    }

    /** @test */
    public function it_handles_unknown_variables_gracefully()
    {
        $activity = new Activity([
            'description' => 'Default description',
            'template' => 'Testing {unknown.variable} and {another.missing}',
        ]);

        $result = TemplateProcessor::process($activity);

        $this->assertEquals('Testing  and ', $result);
    }
}
