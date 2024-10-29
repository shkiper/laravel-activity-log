<?php

declare(strict_types=1);

namespace Shkiper\ModelAuditLog;

use Illuminate\Database\Eloquent\Model;
use Shkiper\ModelAuditLog\Jobs\SaveAuditLogJob;
use Shkiper\ModelAuditLog\Models\AuditLog;

class AuditLogService
{
    public function log(AuditLogRecord|Model|array $record)
    {
        $model = $this->logRecordToModel($record);

        $dispatchToQueue = config('model-audit-log.dispatch_to_queue', false);

        if ($dispatchToQueue) {
            dispatch(new SaveAuditLogJob($model))->onQueue(config('model-audit-log.queue', 'default'));
        } else {
            $model->saveQuietly();
        }
    }

    public function logArray(array $data): void
    {
        $logRecord = new AuditLogRecord();
        $logRecord->model = $data['model'];
        $logRecord->modelId = $data['modelId'];
        $logRecord->userId = $data['userId'];
        $logRecord->field = $data['field'];
        $logRecord->oldValue = $data['oldValue'] ?? null;
        $logRecord->newValue = $data['newValue'] ?? null;
        $logRecord->description = $data['description'] ?? null;
        $logRecord->eventTime = $data['eventTime'] ?? now();

        $this->log($logRecord);
    }

    public function logModel(Model $model, string|null $description = null, \DateTime|null $eventTime = null): void
    {
        if ($model->wasChanged()) {
            $excludedProperties = property_exists($model, 'audit_excluded_properties')
                ? array_merge(['updated_at'], $model->audit_excluded_properties)
                : ['updated_at'];

            $changedProperties = array_diff_key($model->getChanges(), array_flip($excludedProperties));

            foreach ($changedProperties as $property => $value) {
                $log = new AuditLogRecord();
                $log->model = get_class($model);
                $log->modelId = $model->id;
                $log->userId = auth()->id();
                $log->field = $property;
                $log->oldValue = $model->getOriginal($property);
                $log->newValue = $value;
                $log->description = $description;
                $log->eventTime = $eventTime ?? now();

                $this->log($log);
            }
        }
    }

    private function logRecordToModel(AuditLogRecord $record): AuditLog
    {
        $model = new AuditLog();
        $model->model = $record->model;
        $model->model_id = $record->modelId;
        $model->user_id = $record->userId;
        $model->field = $record->field;
        $model->old_value = $record->oldValue;
        $model->new_value = $record->newValue;
        $model->description = $record->description;
        $model->event_time = $record->eventTime ?? now();

        return $model;
    }
}
