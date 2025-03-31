<?php

namespace Shkiper\ActivityLog\Facades;

use Illuminate\Support\Facades\Facade;
use Shkiper\ActivityLog\ActivityLogger;

/**
 * @method static \Shkiper\ActivityLog\ActivityLogger performedOn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Shkiper\ActivityLog\ActivityLogger on(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Shkiper\ActivityLog\ActivityLogger causedBy($causer)
 * @method static \Shkiper\ActivityLog\ActivityLogger by($causer)
 * @method static \Shkiper\ActivityLog\ActivityLogger causedByCurrentUser()
 * @method static \Shkiper\ActivityLog\ActivityLogger withProperties(array $properties)
 * @method static \Shkiper\ActivityLog\ActivityLogger withProperty(string $key, $value)
 * @method static \Shkiper\ActivityLog\ActivityLogger withContext(array $context)
 * @method static \Shkiper\ActivityLog\ActivityLogger withContextItem(string $key, $value)
 * @method static \Shkiper\ActivityLog\ActivityLogger withTemplate(string $template)
 * @method static \Shkiper\ActivityLog\ActivityLogger inLog(string $logName)
 * @method static \Shkiper\ActivityLog\ActivityLogger withEvent(string $event)
 * @method static \Shkiper\ActivityLog\ActivityLogger withDescription(string $description)
 * @method static \Shkiper\ActivityLog\ActivityLogger tap(callable $callback)
 * @method static \Shkiper\ActivityLog\ActivityLogger withBatch(?string $batchUuid = null)
 * @method static \Shkiper\ActivityLog\ActivityLogger logChanges(\Illuminate\Database\Eloquent\Model $subject, string $event = null)
 * @method static \Shkiper\ActivityLog\ActivityLogger log()
 */
class ActivityLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogger::class;
    }
}
