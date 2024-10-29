<?php

namespace Shkiper\ModelAuditLog\Observers;

use Illuminate\Database\Eloquent\Model;
use Shkiper\ModelAuditLog\Jobs\SaveAuditLogJob;
use Shkiper\ModelAuditLog\Models\AuditLog as AuditLogModel;

class AuditLog
{
    public function updated(Model $model)
    {
        $dispatchToQueue = config('model-audit-log.dispatch_to_queue', false);
        if ($model->wasChanged()) {
            $excludedProperties = property_exists($model, 'audit_excluded_properties')
                ? array_merge(['updated_at'], $model->audit_excluded_properties)
                : ['updated_at'];

            $changedProperties = array_diff_key($model->getChanges(), array_flip($excludedProperties));

            foreach ($changedProperties as $property => $value) {
                $log = new AuditLogModel();
                $log->model = get_class($model);
                $log->model_id = $model->id;
                $log->user_id = auth()->id();
                $log->field = $property;
                $log->old_value = $model->getOriginal($property);
                $log->new_value = $value;
                $log->event_time = now();

                if ($dispatchToQueue) {
                    dispatch(new SaveAuditLogJob($log))->onQueue(config('model-audit-log.queue', 'default'));
                } else {
                    $log->saveQuietly();
                }
            }
        }
    }
}
