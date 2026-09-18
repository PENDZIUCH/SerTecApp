<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Motor de agenda/reservas GENERICO. Deliberadamente sin conocimiento de
 * "tecnico" ni "orden de trabajo": eso vive en quien lo consume (ver
 * WorkOrderService, que crea Bookings con resource=User, subject=WorkOrder).
 * Pensado para reusarse tal cual en otros dominios (reservar una mesa,
 * una clase, un repartidor) con otros resource/subject.
 */
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'resource_id',
        'subject_type',
        'subject_id',
        'starts_at',
        'ends_at',
        'status',
        'check_in',
        'check_out',
        'latitude',
        'longitude',
        'metadata',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
        ];
    }

    /** Lo que se reserva: un tecnico, una mesa, un repartidor, una sala... */
    public function resource()
    {
        return $this->morphTo();
    }

    /** Para que es la reserva: una orden de trabajo, un cliente, un pedido... */
    public function subject()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('starts_at', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('starts_at', '<', now())
            ->where('status', 'scheduled');
    }

    public function scopeForResource($query, string $resourceType, $resourceId)
    {
        return $query->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->where('starts_at', '>=', $from)
            ->where('starts_at', '<=', $to);
    }

    public function calculateDuration(): ?int
    {
        if ($this->check_in && $this->check_out) {
            return $this->check_in->diffInMinutes($this->check_out);
        }
        return null;
    }
}
