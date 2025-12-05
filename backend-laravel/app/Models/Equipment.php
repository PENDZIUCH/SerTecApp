<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'brand_id',
        'model_id',
        'serial_number',
        'equipment_code',
        'purchase_date',
        'installation_date',
        'warranty_expiration',
        'next_service_date',
        'last_service_date',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'installation_date' => 'date',
            'warranty_expiration' => 'date',
            'next_service_date' => 'date',
            'last_service_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function brand()
    {
        return $this->belongsTo(EquipmentBrand::class, 'brand_id');
    }

    public function model()
    {
        return $this->belongsTo(EquipmentModel::class, 'model_id');
    }

    public function history()
    {
        return $this->hasMany(EquipmentHistory::class);
    }

    public function files()
    {
        return $this->hasMany(EquipmentFile::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function workshopItems()
    {
        return $this->hasMany(WorkshopItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNeedsService($query)
    {
        return $query->where('next_service_date', '<=', now());
    }

    public function needsService()
    {
        if ($this->next_service_date && $this->next_service_date <= now()) {
            return true;
        }
        if ($this->last_service_date && $this->last_service_date->diffInMonths(now()) > 6) {
            return true;
        }
        return false;
    }

    public function isWarrantyValid()
    {
        return $this->warranty_expiration && $this->warranty_expiration >= now();
    }
}
