<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentHistory extends Model
{
    use HasFactory;

    // La migracion crea 'equipment_history' (singular) - sin esto, Eloquent
    // busca 'equipment_histories' por convencion (plural de "History") y
    // rompe con "no such table" en cualquier cambio de estado de un equipo.
    // Encontrado 2026-09-16 corriendo EquipmentTest.
    protected $table = 'equipment_history';

    protected $fillable = [
        'equipment_id',
        'event_type',
        'description',
        'previous_status',
        'new_status',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
