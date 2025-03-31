<?php

namespace Shkiper\ActivityLog\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Shkiper\ActivityLog\Facades\ActivityLog;

trait LogsActivity
{
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $ignoreChangedAttributes = [];
    protected static $batchUuid = null;

    public static function bootLogsActivity()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {

            static::registerModelEvent($eventName, function (Model $model) use ($eventName) {
                $model->logActivity($eventName);
            });

            // Handle regular events
            app('events')->listen($eventName, function (string $event, array $data) use ($eventName) {
                if (isset($data[0]) && $data[0] instanceof Model) {
                    $data[0]->logActivity($eventName);
                }
            });
        });
    }

    public function logActivity(string $event)
    {
        if (!$this->shouldLogActivity($event)) {
            return;
        }

        $logger = ActivityLog::inLog($this->getActivityLogName())
            ->on($this)
            ->withEvent($event);

        if (static::$batchUuid) {
            $logger->withBatch(static::$batchUuid);
        }

        if (method_exists($this, 'getActivityDescription')) {
            $logger->withDescription($this->getActivityDescription($event));
        } else {
            $logger->withDescription($this->getDefaultActivityDescription($event));
        }

        if ($event === 'updated') {
            $dirty = $this->getDirty();
            $changes = [];

            foreach ($dirty as $key => $value) {
                if (in_array($key, static::$ignoreChangedAttributes)) {
                    continue;
                }

                if (static::$logOnlyDirty && !in_array($key, $this->getActivityLogAttributes())) {
                    continue;
                }

                $changes[$key] = [
                    'old' => $this->getOriginal($key),
                    'new' => $value,
                ];
            }

            if (empty($changes) && !static::$submitEmptyLogs) {
                return;
            }

            $logger->withProperty('changes', $changes);
        }

        $logger->log();
    }

    public function shouldLogActivity(string $event): bool
    {
        if (!in_array($event, $this->getActivityLogEvents())) {
            return false;
        }

        if ($event === 'updated' && static::$logOnlyDirty) {
            return $this->isDirty($this->getActivityLogAttributes());
        }

        return true;
    }

    public function getDefaultActivityDescription(string $eventName): string
    {
        return $eventName . ' ' . Str::lower(class_basename($this));
    }

    public static function eventsToBeRecorded(): \Illuminate\Support\Collection
    {
        if (isset(static::$logEvents)) {
            return collect(static::$logEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        return $events;
    }

    public function getActivityLogName(): string
    {
        if (isset(static::$logName)) {
            return static::$logName;
        }
        return 'default';
    }

    public function getActivityLogAttributes(): array
    {
        if (isset(static::$logAttributes)) {
            return static::$logAttributes;
        }
        return $this->fillable;
    }

    public function getActivityLogEvents(): array
    {
        return self::eventsToBeRecorded()->toArray();
    }

    public function getActivityChanges(): array
    {
        $changes = [];
        $attributes = $this->getActivityLogAttributes();

        foreach ($attributes as $attribute) {
            if ($this->isDirty($attribute)) {
                $changes[$attribute] = [
                    'old' => $this->getOriginal($attribute),
                    'new' => $this->getAttribute($attribute),
                ];
            }
        }

        return $changes;
    }

    public static function startBatch(): string
    {
        static::$batchUuid = (string) Str::uuid();

        return static::$batchUuid;
    }

    public static function endBatch()
    {
        static::$batchUuid = null;
    }
}
