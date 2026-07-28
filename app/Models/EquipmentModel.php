<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'model_code',
        'category',
        'description',
    ];

    public function brand()
    {
        return $this->belongsTo(EquipmentBrand::class, 'brand_id');
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class, 'model_id');
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
