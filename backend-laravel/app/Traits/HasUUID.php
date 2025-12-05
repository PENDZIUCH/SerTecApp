<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUUID
 * 
 * Automatically generates UUID for primary key on model creation.
 * 
 * @package App\Traits
 */
trait HasUUID
{
    /**
     * Boot the trait.
     * 
     * @return void
     */
    protected static function bootHasUUID(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the auto-incrementing key type.
     * 
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     * 
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}
