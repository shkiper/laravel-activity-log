<?php

namespace Shkiper\ModelAuditLog\Observers;

use Illuminate\Database\Eloquent\Model;
use Shkiper\ModelAuditLog\Jobs\SaveAuditLogJob;
use Shkiper\ModelAuditLog\Models\AuditLog as AuditLogModel;

class AuditLog
{
    public function updated(Model $model)
    {
        $dispatchToQueue = config('model-audit-log.dispatch_to_queue');
        if ($model->wasChanged()) {
            foreach ($model->getChanges() as $property => $value) {
                $log = new AuditLogModel();
                $log->model = get_class($model);
                $log->model_id = $model->id;
                $log->user_id = auth()->id();
                $log->field = $property;
                $log->old_value = $model->getOriginal($property);
                $log->new_value = $value;
                $log->event_time = now();

                if ($dispatchToQueue) {
                    dispatch(new SaveAuditLogJob($log))->onQueue(config('model-audit-log.queue'));
                } else {
                    $log->saveQuietly();
                }
            }
        }
    }
}
