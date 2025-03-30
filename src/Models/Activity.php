<?php

namespace Shkiper\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Shkiper\ActivityLog\Templates\ActivityPresenter;

class Activity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'properties' => 'array',
        'context' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('activity-log.table_name', 'activity_logs'));

        parent::__construct($attributes);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getExtraProperty(string $propertyName)
    {
        return Arr::get($this->properties, $propertyName);
    }

    public function getContextValue(string $key)
    {
        return Arr::get($this->context, $key);
    }

    public function getChangesAttribute(): array
    {
        return Arr::get($this->properties, 'changes', []);
    }

    public function getOldAttributesAttribute(): array
    {
        return Arr::get($this->changes, 'old', []);
    }

    public function getNewAttributesAttribute(): array
    {
        return Arr::get($this->changes, 'new', []);
    }

    public function scopeInLog($query, $logNames)
    {
        if (is_array($logNames)) {
            return $query->whereIn('log_name', $logNames);
        }

        return $query->where('log_name', $logNames);
    }

    public function scopeCausedBy($query, $causer)
    {
        if (is_null($causer)) {
            return $query->whereNull('causer_type')
                ->whereNull('causer_id');
        }

        return $query->where('causer_type', $causer::class)
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject($query, $subject)
    {
        if (is_null($subject)) {
            return $query->whereNull('subject_type')
                ->whereNull('subject_id');
        }

        return $query->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Get a presenter instance for the activity.
     *
     * @return ActivityPresenter
     */
    public function presenter(): ActivityPresenter
    {
        return new ActivityPresenter($this);
    }

    /**
     * Get the formatted description for the activity.
     *
     * @return string
     */
    public function getFormattedDescriptionAttribute(): string
    {
        return $this->presenter()->description();
    }
}
