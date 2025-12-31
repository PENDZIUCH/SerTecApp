<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'technician_id',
        'diagnosis',
        'work_done',
        'parts_used',
        'signature',
        'photos',
        'status',
        'supervisor_notes',
        'approved_at',
    ];

    protected $casts = [
        'parts_used' => 'array',
        'photos' => 'array',
        'approved_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
