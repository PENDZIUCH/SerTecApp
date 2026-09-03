<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'item_type',
        'description',
        'quantity',
        'unit_price',
        'total',
        'part_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function scopeType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total = $model->quantity * $model->unit_price;
        });
    }
}
