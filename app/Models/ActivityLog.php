<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_type',
        'actor_type',
        'actor_id',
        'target_type',
        'target_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the actor (user or entity) who performed the action.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function actor()
    {
        return $this->morphTo();
    }

    /**
     * Get the target (entity) that was affected.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function target()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to filter by event type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope a query to filter by actor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model|string $actor
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForActor($query, $actor)
    {
        if ($actor instanceof Model) {
            return $query->where('actor_type', get_class($actor))
                ->where('actor_id', $actor->getKey());
        }

        return $query;
    }

    /**
     * Scope a query to filter by target.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model|string $target
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTarget($query, $target)
    {
        if ($target instanceof Model) {
            return $query->where('target_type', get_class($target))
                ->where('target_id', $target->getKey());
        }

        return $query;
    }

    /**
     * Get a shortened version of the description.
     *
     * @param int $length
     * @return string
     */
    public function shortDescription(int $length = 80): string
    {
        if (strlen($this->description) <= $length) {
            return $this->description;
        }

        return substr($this->description, 0, $length - 3) . '...';
    }

    /**
     * Get a specific value from the metadata array.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function metadataValue(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }
}
