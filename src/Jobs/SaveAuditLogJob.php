<?php

namespace Shkiper\ModelAuditLog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Shkiper\ModelAuditLog\Models\AuditLog;

class SaveAuditLogJob implements ShouldQueue
{
    public function __construct(private readonly AuditLog $auditLog)
    {
    }

    public function handle()
    {
        $this->auditLog->save();
    }
}
