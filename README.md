# Log model changes inside your Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shkiper/model-audit-log.svg?style=flat-square)](https://packagist.org/packages/shkiper/model-audit-log)

The `shkiper/model-audit-log` package for log all model changes to database.
All changes will be stored in `audit_logs` table.

## Instalation

You can install the package via composer:

```bash
composer require shkiper/model-audit-log
```

The package will automatically register itself.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Shkiper\ModelAuditLog\ModelAuditLogServiceProvider"
```

After publishing the migration you can create the `audit_logs` table by running the migrations:

```bash
php artisan migrate
```

## Configuration
By default, package send log to database immediately. You can use queue for change this behavior. In `.env` file set parameter `MODEL_AUDIT_LOG_DISPATCH_TO_QUEUE=true`.

To dispatch to other queue set `MODEL_AUDIT_LOG_QUEUE={queue_name}`.

## Examples

For start tracking changes of model, add observer using attribute `#[ObservedBy()]`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shkiper\ModelAuditLog\Observers\AuditLog;

#[ObservedBy([AuditLog::class])]
class Example extends Model
{
    use HasFactory;
}
```



