<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'website',
    ];

    public function models()
    {
        return $this->hasMany(EquipmentModel::class, 'brand_id');
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class, 'brand_id');
    }
}
