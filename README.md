[![PHP Tests](https://github.com/shkiper/laravel-activity-log/actions/workflows/php-tests.yml/badge.svg)](https://github.com/shkiper/laravel-activity-log/actions/workflows/php-tests.yml)
[![codecov](https://codecov.io/gh/shkiper/laravel-activity-log/branch/main/graph/badge.svg)](https://codecov.io/gh/shkiper/laravel-activity-log)

# Laravel Activity Log

A package for logging actions and changes in models for Laravel 12. Developed with support for different data stores and Laravel 12.

## Installation

```bash
composer require shkiper/laravel-activity-log
```

## Publishing Configuration and Migrations

```bash
php artisan vendor:publish --provider="Shkiper\ActivityLog\ActivityLogServiceProvider"
```

## Configuration

Configuration is located in the `config/activity-log.php` file. You can specify:
- Driver for storing logs (`mysql`, `mongodb`, `clickhouse`)
- Database connection
- Queue settings for asynchronous logging
- Other parameters

## Main Features

### 1. Logging Changes in Models with Template Support

Add the `LogsActivity` trait to your model:

```php
use Shkiper\ActivityLog\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;
    
    // Which fields to log
    protected static $logAttributes = ['name', 'email'];
    
    // Log name
    protected static $logName = 'users';
    
    // Log only changed attributes (recommended)
    protected static $logOnlyDirty = true;
    
    // Which events to log
    protected static $logEvents = ['created', 'updated', 'deleted'];
}
```

### 2. Manual Logging of Actions

```php
use Shkiper\ActivityLog\Facades\ActivityLog;

// Example of logging a user action with a template
ActivityLog::inLog('user_actions')
    ->causedBy($user)
    ->performedOn($article)
    ->withEvent('published')
    ->withTemplate('{causer.name} published article "{subject.title}"')
    ->withProperties([
        'action' => 'published',
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ])
    ->withContext([
        'platform' => 'web',
        'browser' => 'Chrome'
    ])
    ->log();
    
// Example of logging a system action
ActivityLog::inLog('system')
    ->withDescription('Mailing started')
    ->withProperties([
        'email_count' => 150,
        'campaign_id' => 123,
    ])
    ->log();
```

### 3. Grouping Logs in a Batch

```php
// Start logging with one UUID for related actions
$batchUuid = \Shkiper\ActivityLog\Traits\LogsActivity::startBatch();

// Creation/updating of models

// Or you can manually add to the group
ActivityLog::withBatch($batchUuid)
    ->inLog('batch_actions')
    ->withDescription('Action in group')
    ->log();

// End batch logging
\Shkiper\ActivityLog\Traits\LogsActivity::endBatch();
```

### 4. Retrieving Logs

```php
// Through the repository
app(\Shkiper\ActivityLog\Contracts\ActivityLogRepository::class)
    ->findByLogName('users');
    
// Through Eloquent (if using MySQL)
\Shkiper\ActivityLog\Models\Activity::inLog('users')
    ->causedBy($user)
    ->latest()
    ->get();
```

## Usage

Add logging to a model:
```php
use Shkiper\ActivityLog\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;
    
    protected static $logAttributes = ['name', 'email'];
}
```

Log an action manually:
```php
ActivityLog::inLog('user_actions')
    ->causedByCurrentUser()
    ->withTemplate("User {causer.name} performed an action")
    ->withContext(['additional' => 'data'])
    ->log();
```

## Usage Examples

### Logging User Changes

```php
// User.php
use Shkiper\ActivityLog\Traits\LogsActivity;

class User extends Authenticatable
{
    use LogsActivity;
    
    protected static $logAttributes = ['name', 'email', 'status'];
    protected static $logName = 'users';
}

// When $user->update(['status' => 'active']) is called,
// a log entry will be created with the old and new values saved
```

### Custom Messages in Logs

```php
// Comment.php
use Shkiper\ActivityLog\Traits\LogsActivity;

class Comment extends Model
{
    use LogsActivity;
    
    protected static $logAttributes = ['content'];
    protected static $logName = 'comments';
    
    // Custom event description
    public function getActivityDescription(string $eventName): string
    {
        return match($eventName) {
            'created' => "User added a comment to article {$this->article->title}",
            'updated' => "User edited a comment on article {$this->article->title}",
            'deleted' => "User deleted a comment from article {$this->article->title}",
            default => $eventName,
        };
    }
}
```

### Manual Action Logging

```php
class MessageController
{
    public function send(Request $request, User $recipient)
    {
        // Message sending logic
        $message = Message::create([...]);
        
        // Logging the action
        ActivityLog::inLog('messaging')
            ->causedByCurrentUser()
            ->performedOn($message)
            ->withDescription("Message sent to user {$recipient->name}")
            ->withProperties([
                'message_type' => $request->type,
                'recipient_id' => $recipient->id,
                'content_length' => strlen($request->content),
            ])
            ->log();
            
        return response()->json(['success' => true]);
    }
}
```

## Working with Different Storages

### MySQL (default)
```php
// .env
ACTIVITY_LOG_DRIVER=mysql
```

### MongoDB
```php
// .env
ACTIVITY_LOG_DRIVER=mongodb
ACTIVITY_LOG_MONGODB_CONNECTION=mongodb
ACTIVITY_LOG_MONGODB_COLLECTION=activity_logs
```

### ClickHouse
```php
// .env
ACTIVITY_LOG_DRIVER=clickhouse
ACTIVITY_LOG_CLICKHOUSE_CONNECTION=clickhouse
ACTIVITY_LOG_CLICKHOUSE_TABLE=activity_logs
```

## Message Templating

The package supports customizing log display through a template system.

### Standard Templates

Standard templates for events can be configured in the configuration file:

```php
// config/activity-log.php
'templates' => [
    'created' => '{causer.name} created {subject.type} "{subject.name}"',
    'updated' => '{causer.name} updated {subject.type} "{subject.name}"',
    'deleted' => '{causer.name} deleted {subject.type} "{subject.name}"',
    // add your templates for different events
],
```

### Available Variables in Templates

- `{causer}` - the model that performed the action
- `{causer.name}` - access to causer properties
- `{subject}` - the model on which the action was performed
- `{subject.title}` - access to subject properties
- `{properties.key}` - access to values from the properties array
- `{context.key}` - access to values from the context array
- `{changes.old.field}` - old value of a changed field
- `{changes.new.field}` - new value of a changed field

### Registering Custom Templates

```php
// In a service provider
use Shkiper\ActivityLog\Templates\TemplateProcessor;

public function boot()
{
    TemplateProcessor::registerTemplates([
        'login' => 'User {causer.name} logged into the system from IP {context.ip}',
        'payment' => 'Payment of {properties.amount} for {subject.type} #{subject.id}'
    ]);
}
```

### Getting Formatted Descriptions

```php
// Through the presenter
$activity = Activity::first();
$formattedDescription = $activity->presenter()->description();

// Or through the accessor
$formattedDescription = $activity->formatted_description;

// Applying another template for display
$customDescription = $activity->presenter()->renderTemplate(
    'User {causer.name} changed field {changes.new.title}'
);
```

## License

MIT
