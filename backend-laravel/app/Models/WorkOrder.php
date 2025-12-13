<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'equipment_id',
        'wo_number',
        'title',
        'description',
        'priority',
        'status',
        'assigned_tech_id',
        'scheduled_date',
        'scheduled_time',
        'started_at',
        'completed_at',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'requires_signature',
        'signature_token',
        'signature_image_path',
        'signed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'signed_at' => 'datetime',
            'labor_cost' => 'decimal:2',
            'parts_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'requires_signature' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedTech()
    {
        return $this->belongsTo(User::class, 'assigned_tech_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(WorkOrderLog::class);
    }

    public function files()
    {
        return $this->hasMany(WorkOrderFile::class);
    }

    public function partsUsed()
    {
        return $this->hasMany(WoPartUsed::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function workshopItems()
    {
        return $this->hasMany(WorkshopItem::class);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $techId)
    {
        return $query->where('assigned_tech_id', $techId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function isOverdue()
    {
        return $this->scheduled_date 
            && $this->scheduled_date < now() 
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workOrder) {
            if (!$workOrder->wo_number) {
                $lastWO = static::orderBy('id', 'desc')->first();
                $nextNumber = $lastWO ? ((int)substr($lastWO->wo_number, 3) + 1) : 1;
                $workOrder->wo_number = 'WO-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function calculateTotalCost()
    {
        $this->total_cost = $this->labor_cost + $this->parts_cost;
        return $this->total_cost;
    }
}
