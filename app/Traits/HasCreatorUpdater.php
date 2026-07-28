<?php

namespace App\Traits;

/**
 * Trait HasCreatorUpdater
 * 
 * Automatically tracks who created and updated a model.
 * Sets created_by on creation and updated_by on updates.
 * Works gracefully in CLI/seeder environments without authenticated user.
 * 
 * @package App\Traits
 */
trait HasCreatorUpdater
{
    /**
     * Boot the trait.
     * 
     * @return void
     */
    protected static function bootHasCreatorUpdater(): void
    {
        static::creating(function ($model) {
            $userId = auth()->id();
            
            if ($userId && $model->isFillable('created_by')) {
                $model->created_by = $userId;
            }
            
            if ($userId && $model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = auth()->id();
            
            if ($userId && $model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        });
    }

    /**
     * Get the user who created this model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this model.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
