<?php

namespace Shkiper\ActivityLog\Facades;

use Illuminate\Support\Facades\Facade;
use Shkiper\ActivityLog\ActivityLogger;

/**
 * @method static \YourName\ActivityLog\ActivityLogger performedOn(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \YourName\ActivityLog\ActivityLogger on(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \YourName\ActivityLog\ActivityLogger causedBy($causer)
 * @method static \YourName\ActivityLog\ActivityLogger by($causer)
 * @method static \YourName\ActivityLog\ActivityLogger causedByCurrentUser()
 * @method static \YourName\ActivityLog\ActivityLogger withProperties(array $properties)
 * @method static \YourName\ActivityLog\ActivityLogger withProperty(string $key, $value)
 * @method static \YourName\ActivityLog\ActivityLogger withContext(array $context)
 * @method static \YourName\ActivityLog\ActivityLogger withContextItem(string $key, $value)
 * @method static \YourName\ActivityLog\ActivityLogger withTemplate(string $template)
 * @method static \YourName\ActivityLog\ActivityLogger inLog(string $logName)
 * @method static \YourName\ActivityLog\ActivityLogger withEvent(string $event)
 * @method static \YourName\ActivityLog\ActivityLogger withDescription(string $description)
 * @method static \YourName\ActivityLog\ActivityLogger tap(callable $callback)
 * @method static \YourName\ActivityLog\ActivityLogger withBatch(?string $batchUuid = null)
 * @method static \YourName\ActivityLog\ActivityLogger logChanges(\Illuminate\Database\Eloquent\Model $subject, string $event = null)
 * @method static \YourName\ActivityLog\ActivityLogger log()
 */
class ActivityLog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogger::class;
    }
}
