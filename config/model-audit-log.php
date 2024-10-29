<?php
return [
    'dispatch_to_queue' => env('MODEL_AUDIT_LOG_DISPATCH_TO_QUEUE', false),
    'queue' => env('MODEL_AUDIT_LOG_QUEUE', 'default'),
];
