<?php

namespace Shkiper\ActivityLog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Orchestra\Testbench\TestCase;
use Shkiper\ActivityLog\ActivityLogger;
use Shkiper\ActivityLog\Contracts\ActivityLogRepository;
use Shkiper\ActivityLog\Tests\Models\TestModel;
use Shkiper\ActivityLog\Tests\Models\TestUser;

class ActivityLoggerTest extends TestCase
{
    protected $repository;
    protected $logger;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ActivityLogRepository::class);
        $this->logger = new ActivityLogger($this->repository);
        $this->user = new TestUser(['id' => 1, 'name' => 'Test User']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_log_an_activity()
    {
        $this->repository->shouldReceive('log')
            ->once()
            ->with(
                'default',
                'Test Description',
                [],
                null,
                null,
                null,
                [],
                null
            )
            ->andReturn('created');

        $result = $this->logger
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_on_a_subject()
    {
        $subject = new TestModel(['id' => 1, 'name' => 'Test Model']);

        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $properties, $logSubject, $causer, $event, $context, $template) use ($subject) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    $logSubject instanceof TestModel &&
                    $logSubject->id === $subject->id;
            })
            ->andReturn('created');

        $result = $this->logger
            ->performedOn($subject)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_causer()
    {
        $this->repository->shouldReceive('log')
            ->once()
            ->withAnyArgs()
            ->andReturn('created');

        $result = $this->logger
            ->causedBy($this->user)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_properties()
    {
        $properties = ['key' => 'value', 'test' => true];

        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $logProperties, $subject, $causer, $event, $context, $template) use ($properties) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    $logProperties['key'] === $properties['key'] &&
                    $logProperties['test'] === $properties['test'];
            })
            ->andReturn('created');

        $result = $this->logger
            ->withProperties($properties)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_context()
    {
        $context = ['ip' => '127.0.0.1', 'browser' => 'Chrome'];

        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $properties, $subject, $causer, $event, $logContext, $template) use ($context) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    $logContext['ip'] === $context['ip'] &&
                    $logContext['browser'] === $context['browser'];
            })
            ->andReturn('created');

        $result = $this->logger
            ->withContext($context)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_template()
    {
        $template = 'User {causer.name} performed action on {subject.name}';

        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $properties, $subject, $causer, $event, $context, $logTemplate) use ($template) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    $logTemplate === $template;
            })
            ->andReturn('created');

        $result = $this->logger
            ->withTemplate($template)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_an_event()
    {
        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $properties, $subject, $causer, $event, $context, $template) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    $event === 'created';
            })
            ->andReturn('created');

        $result = $this->logger
            ->withEvent('created')
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }

    /** @test */
    public function it_can_log_activity_with_batch_uuid()
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';

        $this->repository->shouldReceive('log')
            ->once()
            ->withArgs(function ($logName, $description, $properties, $subject, $causer, $event, $context, $template) use ($uuid) {
                return $logName === 'default' &&
                    $description === 'Test Description' &&
                    isset($properties['batch_uuid']) &&
                    $properties['batch_uuid'] === $uuid;
            })
            ->andReturn('created');

        $result = $this->logger
            ->withBatch($uuid)
            ->withDescription('Test Description')
            ->log();

        $this->assertEquals($this->logger, $result);
    }
}
