<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'part_number',
        'sku',
        'description',
        'unit_cost',
        'stock_quantity',
        'stock_qty',
        'min_stock_level',
        'is_active',
        'location',
        'fob_price_usd',
        'markup_percent',
        'sale_price_usd',
        'equipment_model_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'stock_qty' => 'integer',
            'stock_quantity' => 'integer',
            'min_stock_level' => 'integer',
            'is_active' => 'boolean',
            'fob_price_usd' => 'decimal:2',
            'markup_percent' => 'decimal:2',
            'sale_price_usd' => 'decimal:2',
        ];
    }

    public function equipmentModel()
    {
        return $this->belongsTo(EquipmentModel::class);
    }

    public function movements()
    {
        return $this->hasMany(PartsMovement::class);
    }

    public function workOrdersUsed()
    {
        return $this->hasMany(WoPartUsed::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'min_stock_level');
    }

    public function isLowStock()
    {
        return $this->stock_qty <= $this->min_stock_level;
    }

    public function calculateTotalValue()
    {
        return $this->stock_qty * $this->unit_cost;
    }
}
