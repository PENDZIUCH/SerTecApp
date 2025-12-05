<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartsMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'movement_type',
        'quantity',
        'related_work_order_id',
        'created_by',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'related_work_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeType($query, $type)
    {
        return $query->where('movement_type', $type);
    }
}
