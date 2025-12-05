<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkshopItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'equipment_id',
        'customer_id',
        'work_order_id',
        'status',
        'entry_date',
        'estimated_completion_date',
        'exit_date',
        'assigned_tech_id',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'estimated_completion_date' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function assignedTech()
    {
        return $this->belongsTo(User::class, 'assigned_tech_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('estimated_completion_date', '<', now())
            ->where('status', '!=', 'delivered');
    }

    public function getDaysInWorkshopAttribute()
    {
        $endDate = $this->exit_date ?? now();
        return $this->entry_date->diffInDays($endDate);
    }

    public function isOverdue()
    {
        return $this->estimated_completion_date
            && $this->estimated_completion_date < now()
            && $this->status !== 'delivered';
    }
}
