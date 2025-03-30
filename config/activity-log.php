<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Log Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the storage driver that will be used to store
    | activity logs. Supported drivers: "mysql", "mongodb", "clickhouse"
    |
    */
    'driver' => env('ACTIVITY_LOG_DRIVER', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Default Authenticator
    |--------------------------------------------------------------------------
    |
    | The name of the authenticator guard that will be used to retrieve the user
    | performing the activity. Set to null to disable automatic actor logging.
    |
    */
    'default_auth_guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Activity Model
    |--------------------------------------------------------------------------
    |
    | The model that will be used to store the activities. You can change this
    | to any class that implements the ActivityLogRepository contract.
    |
    */
    'activity_model' => \Shkiper\ActivityLog\Models\Activity::class,

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for storing activity logs.
    |
    */
    'database_connection' => env('ACTIVITY_LOG_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | The names of the tables used to store activity logs.
    |
    */
    'table_name' => 'activity_logs',

    /*
    |--------------------------------------------------------------------------
    | Queue Logging
    |--------------------------------------------------------------------------
    |
    | If you want the activity logging to happen in a queue, set this to true.
    |
    */
    'queue_enabled' => env('ACTIVITY_LOG_QUEUE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The name of the queue to use for logging if queue_enabled is true.
    |
    */
    'queue_name' => env('ACTIVITY_LOG_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | MongoDB Settings
    |--------------------------------------------------------------------------
    |
    | MongoDB specific settings.
    |
    */
    'mongodb' => [
        'connection' => env('ACTIVITY_LOG_MONGODB_CONNECTION', 'mongodb'),
        'collection' => env('ACTIVITY_LOG_MONGODB_COLLECTION', 'activity_logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ClickHouse Settings
    |--------------------------------------------------------------------------
    |
    | ClickHouse specific settings.
    |
    */
    'clickhouse' => [
        'connection' => env('ACTIVITY_LOG_CLICKHOUSE_CONNECTION', 'clickhouse'),
        'table' => env('ACTIVITY_LOG_CLICKHOUSE_TABLE', 'activity_logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Templates for rendering activity descriptions. You can define custom
    | templates for specific events here. Available variables:
    | - {causer} - The model that performed the activity
    | - {causer.name} - Access properties of the causer model
    | - {subject} - The model on which the activity was performed
    | - {subject.name} - Access properties of the subject model
    | - {properties.key} - Access properties array values
    | - {context.key} - Access context array values
    | - {changes.old.field} - Old value of a changed field
    | - {changes.new.field} - New value of a changed field
    |
    */
    'templates' => [
        'created' => '{causer.name} created {subject.type} "{subject.name}"',
        'updated' => '{causer.name} updated {subject.type} "{subject.name}"',
        'deleted' => '{causer.name} deleted {subject.type} "{subject.name}"',
        'restored' => '{causer.name} restored {subject.type} "{subject.name}"',
        'login' => '{causer.name} logged in to the system',
        'logout' => '{causer.name} logged out from the system',
    ],
];
