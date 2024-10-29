<?php

namespace Shkiper\ModelAuditLog\Facades;

use Illuminate\Support\Facades\Facade;
use Shkiper\ModelAuditLog\AuditLogService;

class Audit extends Facade
{
    public static function getFacadeAccessor()
    {
        return AuditLogService::class;
    }
}
