<?php

namespace Shkiper\ActivityLog;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Shkiper\ActivityLog\Contracts\ActivityLogRepository;

class ActivityLogger
{
    protected $logName = 'default';
    protected $description = '';
    protected $properties = [];
    protected $context = [];
    protected $template = null;
    protected $event = null;
    protected $subject = null;
    protected $causer = null;
    protected $batchUuid = null;
    protected $auth;
    protected Repository $config;
    public function __construct(
        protected ActivityLogRepository $repository,
    ) {
        $this->auth = new \Illuminate\Auth\AuthManager(app());
        $this->config = config();
    }

    public function performedOn(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function on(Model $subject): self
    {
        return $this->performedOn($subject);
    }

    public function causedBy($causer): self
    {
        $this->causer = $causer;

        return $this;
    }

    public function causedByCurrentUser(): self
    {
        if ($this->causer !== null) {
            return $this;
        }

        $guard = $this->config->get('activity-log.default_auth_guard');

        if (is_null($guard)) {
            return $this->causedBy(null);
        }

        $user = $this->auth->guard($guard)->user();

        return $this->causedBy($user);
    }

    public function withProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);

        return $this;
    }

    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function withContextItem(string $key, $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function withTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function withProperty(string $key, $value): self
    {
        $this->properties[$key] = $value;

        return $this;
    }

    public function inLog(string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    public function withEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    public function withBatch(?string $batchUuid = null): self
    {
        $this->batchUuid = $batchUuid ?? (string) Str::uuid();
        $this->withProperty('batch_uuid', $this->batchUuid);

        return $this;
    }

    public function logChanges(Model $subject, string $event = null): self
    {
        if ($event === 'updated' && method_exists($subject, 'getChanges')) {
            $changes = $subject->getChanges();

            if (empty($changes)) {
                return $this;
            }

            $original = $subject->getOriginal();

            $this->withProperty('changes', [
                'old' => array_intersect_key($original, $changes),
                'new' => $changes,
            ]);
        }

        return $this
            ->performedOn($subject)
            ->withEvent($event)
            ->withDescription(
                $this->description ?: "{$event} " . class_basename($subject) . " \"{$subject->name}\""
            )
            ->log();
    }

    public function log(): self
    {
        $this->causedByCurrentUser();

        $properties = $this->properties;

        if ($this->batchUuid) {
            $properties['batch_uuid'] = $this->batchUuid;
        }

        $activity = $this->repository->log(
            $this->logName,
            $this->description,
            $properties,
            $this->subject,
            $this->causer,
            $this->event,
            $this->context,
            $this->template
        );

        $this->reset();

        return $this;
    }

    public function getBatchUuid(): string|null
    {
        return $this->batchUuid;
    }

    protected function reset(): self
    {
        $this->description = '';
        $this->subject = null;
        $this->causer = null;
        $this->properties = [];
        $this->context = [];
        $this->template = null;
        $this->event = null;

        return $this;
    }
}
