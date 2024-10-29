<?php

namespace Shkiper\ModelAuditLog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AuditLog
 *
 * Represents an audit log entry that tracks changes made to model attributes.
 *
 * @package App\Models
 *
 * @property int $id The unique identifier for the audit log entry
 * @property string $model The fully qualified class name of the model being audited
 * @property string $field The name of the field that was changed
 * @property string $old_value The previous value of the field before the change
 * @property string $new_value The new value of the field after the change
 * @property string $model_id The ID of the model instance being audited
 * @property string|null $user_id The ID of the user who made the change (nullable)
 * @property \DateTime $event_time The timestamp when the change occurred
 * @property \DateTime $created_at Timestamp when the audit log entry was created
 * @property \DateTime $updated_at Timestamp when the audit log entry was last updated
 */
class AuditLog extends Model
{
    public $guarded = [];

    protected $casts = [
        'event_time' => 'datetime',
    ];
}
