<?php

namespace Shkiper\ActivityLog\Templates;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Shkiper\ActivityLog\Models\Activity;

class TemplateProcessor
{
    /**
     * Default templates for common events
     *
     * @var array
     */
    protected static $defaultTemplates = [
        'created' => '{causer.name} created {subject.type} "{subject.name}"',
        'updated' => '{causer.name} updated {subject.type} "{subject.name}"',
        'deleted' => '{causer.name} deleted {subject.type} "{subject.name}"',
        'restored' => '{causer.name} restored {subject.type} "{subject.name}"',
        'login' => '{causer.name} logged in to the system',
        'logout' => '{causer.name} logged out from the system',
    ];

    /**
     * Custom templates registered by the application
     *
     * @var array
     */
    protected static $customTemplates = [];

    /**
     * Register a new template for an event
     *
     * @param string $event
     * @param string $template
     * @return void
     */
    public static function registerTemplate(string $event, string $template): void
    {
        static::$customTemplates[$event] = $template;
    }

    /**
     * Register multiple templates at once
     *
     * @param array $templates
     * @return void
     */
    public static function registerTemplates(array $templates): void
    {
        foreach ($templates as $event => $template) {
            static::registerTemplate($event, $template);
        }
    }

    /**
     * Get a template for a specific event
     *
     * @param string $event
     * @return string|null
     */
    public static function getTemplate(string $event): ?string
    {
        return static::$customTemplates[$event] ?? static::$defaultTemplates[$event] ?? null;
    }

    /**
     * Process a template with an activity instance
     *
     * @param Activity $activity
     * @param string|null $template
     * @return string
     */
    public static function process(Activity $activity, ?string $template = null): string
    {
        if (empty($template)) {
            // Try to get template from the activity
            $template = $activity->template;

            // If still empty, try to get template from the event
            if (empty($template) && !empty($activity->event)) {
                $template = static::getTemplate($activity->event);
            }

            // If still empty, use the existing description
            if (empty($template)) {
                return $activity->description;
            }
        }

        return static::replaceVariables($template, $activity);
    }

    /**
     * Replace all variables in a template
     *
     * @param string $template
     * @param Activity $activity
     * @return string
     */
    protected static function replaceVariables(string $template, Activity $activity): string
    {
        // Pattern to match placeholders like {causer}, {causer.name}, {property.key}, etc.
        preg_match_all('/{([^}]+)}/', $template, $matches);

        if (empty($matches[1])) {
            return $template;
        }

        $replacements = [];

        foreach ($matches[1] as $placeholder) {
            $value = static::resolvePlaceholder($placeholder, $activity);
            $replacements['{' . $placeholder . '}'] = $value;
        }

        return strtr($template, $replacements);
    }

    /**
     * Resolve a placeholder's value from activity data
     *
     * @param string $placeholder
     * @param Activity $activity
     * @return string
     */
    protected static function resolvePlaceholder(string $placeholder, Activity $activity): string
    {
        $parts = explode('.', $placeholder);
        $root = array_shift($parts);

        switch ($root) {
            case 'causer':
                if (empty($parts)) {
                    return $activity->causer ? (string) $activity->causer : 'System';
                }

                return $activity->causer && isset($activity->causer->{$parts[0]})
                    ? (string) $activity->causer->{$parts[0]}
                    : 'unknown';

            case 'subject':
                if (empty($parts)) {
                    return $activity->subject ? (string) $activity->subject : 'unknown';
                }

                if ($parts[0] === 'type' && $activity->subject) {
                    return Str::lower(class_basename($activity->subject));
                }

                return $activity->subject && isset($activity->subject->{$parts[0]})
                    ? (string) $activity->subject->{$parts[0]}
                    : 'unknown';

            case 'properties':
                if (empty($parts)) {
                    return json_encode($activity->properties);
                }

                return (string) Arr::get($activity->properties, implode('.', $parts), '');

            case 'context':
                if (empty($parts)) {
                    return json_encode($activity->context);
                }

                return (string) Arr::get($activity->context, implode('.', $parts), '');

            case 'changes':
                if (empty($parts)) {
                    return json_encode($activity->changes);
                }

                if ($parts[0] === 'old' && isset($parts[1])) {
                    return (string) Arr::get($activity->old_attributes, $parts[1], '');
                }

                if ($parts[0] === 'new' && isset($parts[1])) {
                    return (string) Arr::get($activity->new_attributes, $parts[1], '');
                }

                return (string) Arr::get($activity->changes, implode('.', $parts), '');

            default:
                if (isset($activity->{$root})) {
                    return (string) $activity->{$root};
                }

                return '';
        }
    }
}
