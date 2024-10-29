<?php

namespace Shkiper\ModelAuditLog\Traits;

use Shkiper\ModelAuditLog\Models\AuditLog;

trait AuditLogTrait
{
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model', 'model');
    }
}
