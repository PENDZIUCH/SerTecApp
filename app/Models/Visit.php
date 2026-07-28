<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'assigned_tech_id',
        'subscription_id',
        'visit_date',
        'scheduled_time',
        'estimated_duration_minutes',
        'check_in',
        'check_out',
        'duration_minutes',
        'status',
        'notes',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'duration_minutes' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function assignedTech()
    {
        return $this->belongsTo(User::class, 'assigned_tech_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('visit_date', '<', now())
            ->where('status', 'scheduled');
    }

    public function calculateDuration()
    {
        if ($this->check_in && $this->check_out) {
            $this->duration_minutes = $this->check_in->diffInMinutes($this->check_out);
        }
        return $this->duration_minutes;
    }
}
