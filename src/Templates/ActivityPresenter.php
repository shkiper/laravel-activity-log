<?php

namespace Shkiper\ActivityLog\Templates;

use Shkiper\ActivityLog\Models\Activity;

class ActivityPresenter
{
    /**
     * @var Activity
     */
    protected $activity;

    /**
     * Create a new presenter instance.
     *
     * @param Activity $activity
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Get the formatted description for the activity.
     *
     * @return string
     */
    public function description(): string
    {
        return TemplateProcessor::process($this->activity);
    }

    /**
     * Get all available data for presentation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->activity->id,
            'description' => $this->description(),
            'raw_description' => $this->activity->description,
            'log_name' => $this->activity->log_name,
            'causer' => $this->activity->causer,
            'subject' => $this->activity->subject,
            'subject_type' => $this->activity->subject_type,
            'subject_id' => $this->activity->subject_id,
            'properties' => $this->activity->properties,
            'context' => $this->activity->context,
            'event' => $this->activity->event,
            'created_at' => $this->activity->created_at,
            'updated_at' => $this->activity->updated_at,
        ];
    }

    /**
     * Render a specific template with this activity
     *
     * @param string $template
     * @return string
     */
    public function renderTemplate(string $template): string
    {
        return TemplateProcessor::process($this->activity, $template);
    }

    /**
     * Get an attribute from the activity model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->activity->{$key};
    }
}
