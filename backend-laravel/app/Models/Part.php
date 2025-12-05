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
        'sku',
        'description',
        'unit_cost',
        'stock_qty',
        'min_stock_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'stock_qty' => 'integer',
            'min_stock_level' => 'integer',
            'is_active' => 'boolean',
        ];
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
