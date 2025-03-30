<?php

namespace Shkiper\ActivityLog\Contracts;

interface ActivityLogRepository
{
    /**
     * Log an activity
     *
     * @param string $logName
     * @param string $description
     * @param array $properties
     * @param \Illuminate\Database\Eloquent\Model|null $subject
     * @param \Illuminate\Database\Eloquent\Model|null $causer
     * @param string|null $event
     * @param array $context
     * @param string|null $template
     * @return mixed
     */
    public function log(
        string $logName,
        string $description,
        array $properties = [],
               $subject = null,
               $causer = null,
        string $event = null,
        array $context = [],
        string $template = null
    );

    /**
     * Find activities by subject
     *
     * @param \Illuminate\Database\Eloquent\Model $subject
     * @return mixed
     */
    public function findBySubject($subject);

    /**
     * Find activities by causer
     *
     * @param \Illuminate\Database\Eloquent\Model $causer
     * @return mixed
     */
    public function findByCauser($causer);

    /**
     * Find activities by log name
     *
     * @param string $logName
     * @return mixed
     */
    public function findByLogName(string $logName);

    /**
     * Find activities by event
     *
     * @param string $event
     * @return mixed
     */
    public function findByEvent(string $event);

    /**
     * Clean old activity logs
     *
     * @param int $days Number of days to keep logs
     * @return int Number of deleted records
     */
    public function clean(int $days): int;
}
