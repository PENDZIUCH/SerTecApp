<?php

namespace App\Traits;

use App\Models\ActivityLog;

/**
 * Trait LogsActivity
 * 
 * Automatically logs model activities to activity_log table.
 * Tracks create, update, and delete events with full context.
 * 
 * @package App\Traits
 */
trait LogsActivity
{
    /**
     * Boot the trait.
     * 
     * @return void
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', 'Registro creado');
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            
            if (!empty($changes)) {
                $model->logActivity('updated', 'Registro actualizado', [
                    'changes' => $changes,
                    'original' => $model->getOriginal(),
                ]);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', 'Registro eliminado');
        });
    }

    /**
     * Log an activity.
     * 
     * @param string $eventType
     * @param string $description
     * @param array $metadata
     * @return ActivityLog|null
     */
    public function logActivity(string $eventType, string $description, array $metadata = []): ?ActivityLog
    {
        try {
            return ActivityLog::create([
                'event_type' => $eventType,
                'description' => $description,
                'actor_id' => auth()->id(),
                'actor_type' => auth()->check() ? get_class(auth()->user()) : null,
                'target_id' => $this->getKey(),
                'target_type' => get_class($this),
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Fail gracefully if ActivityLog table doesn't exist
            return null;
        }
    }

    /**
     * Get all activity logs for this model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'target');
    }
}
