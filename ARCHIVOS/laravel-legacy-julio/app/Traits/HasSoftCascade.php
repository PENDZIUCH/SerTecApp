<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait HasSoftCascade
 * 
 * Automatically soft deletes related child models when parent is soft deleted.
 * Works with SoftDeletes trait and cascades through defined relationships.
 * 
 * @package App\Traits
 */
trait HasSoftCascade
{
    /**
     * Boot the trait.
     * 
     * @return void
     */
    protected static function bootHasSoftCascade(): void
    {
        static::deleting(function ($model) {
            // Only cascade if using soft deletes and not force deleting
            if (!$model->isForceDeleting()) {
                $model->cascadeSoftDelete();
            }
        });

        static::restoring(function ($model) {
            $model->cascadeRestore();
        });
    }

    /**
     * Cascade soft delete to child relationships.
     * 
     * @return void
     */
    protected function cascadeSoftDelete(): void
    {
        $relationships = $this->getCascadeRelationships();

        foreach ($relationships as $relationship) {
            if (method_exists($this, $relationship)) {
                $relation = $this->$relationship();
                
                // Only cascade on hasMany and morphMany relationships
                if (method_exists($relation, 'delete')) {
                    $related = $this->$relationship()->get();
                    
                    foreach ($related as $model) {
                        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                            $model->delete();
                        }
                    }
                }
            }
        }
    }

    /**
     * Cascade restore to child relationships.
     * 
     * @return void
     */
    protected function cascadeRestore(): void
    {
        $relationships = $this->getCascadeRelationships();

        foreach ($relationships as $relationship) {
            if (method_exists($this, $relationship)) {
                $related = $this->$relationship()->onlyTrashed()->get();
                
                foreach ($related as $model) {
                    if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                        $model->restore();
                    }
                }
            }
        }
    }

    /**
     * Get relationships to cascade.
     * Override this method in your model to specify which relationships to cascade.
     * 
     * @return array
     */
    protected function getCascadeRelationships(): array
    {
        return $this->cascadeDeletes ?? [];
    }
}
