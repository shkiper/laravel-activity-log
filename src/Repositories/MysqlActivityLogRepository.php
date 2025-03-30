<?php

namespace Shkiper\ActivityLog\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Shkiper\ActivityLog\Contracts\ActivityLogRepository;

class MysqlActivityLogRepository implements ActivityLogRepository
{
    protected $activityModel;

    public function __construct()
    {
        $this->activityModel = app(config('activity-log.activity_model'));
    }

    public function log(
        string $logName,
        string $description,
        array $properties = [],
               $subject = null,
               $causer = null,
        string $event = null,
        array $context = [],
        string $template = null
    ) {
        $activity = $this->activityModel->newInstance();

        $activity->log_name = $logName;
        $activity->description = $description;
        $activity->properties = $properties;
        $activity->context = $context;
        $activity->template = $template;
        $activity->event = $event;

        if ($subject instanceof Model) {
            $activity->subject()->associate($subject);
        }

        if ($causer instanceof Model) {
            $activity->causer()->associate($causer);
        }

        $activity->save();

        return $activity;
    }

    public function findBySubject($subject)
    {
        return $this->activityModel
            ->forSubject($subject)
            ->latest()
            ->get();
    }

    public function findByCauser($causer)
    {
        return $this->activityModel
            ->causedBy($causer)
            ->latest()
            ->get();
    }

    public function findByLogName(string $logName)
    {
        return $this->activityModel
            ->inLog($logName)
            ->latest()
            ->get();
    }

    public function findByEvent(string $event)
    {
        return $this->activityModel
            ->forEvent($event)
            ->latest()
            ->get();
    }

    public function clean(int $days): int
    {
        $date = Carbon::now()->subDays($days);

        return $this->activityModel
            ->where('created_at', '<', $date)
            ->delete();
    }
}
