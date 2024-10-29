<?php

declare(strict_types=1);

namespace Shkiper\ModelAuditLog;

class AuditLogRecord
{
    public string $model;
    public string $modelId;
    public string $userId;
    public string $field;
    public string|null $oldValue = null;
    public string|null $newValue = null;
    public string|null $description = null;
    public \DateTime|null $eventTime = null;
}
