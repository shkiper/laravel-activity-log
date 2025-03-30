<?php

namespace Shkiper\ActivityLog\Contracts;

interface ShouldLogActivity
{
    /**
     * Get the log name for the activity
     *
     * @return string
     */
    public function getActivityLogName(): string;

    /**
     * Get the description for the activity
     *
     * @param string $eventName
     * @return string
     */
    public function getActivityDescription(string $eventName): string;

    /**
     * Get the attributes that should be logged
     *
     * @return array
     */
    public function getActivityLogAttributes(): array;

    /**
     * Get the events that should be logged
     *
     * @return array
     */
    public function getActivityLogEvents(): array;
}
