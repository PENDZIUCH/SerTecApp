<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Lista generica administrable por admin/supervisor (Filament:
// LookupValueResource). Una fila = una opcion dentro de una 'category'
// (ej: category='customer_type', value='gym', label='Gimnasio'). Agregar
// una lista administrable nueva a futuro (tipo de equipo, categoria de
// repuesto, lo que sea) es solo usar una category nueva - no hace falta
// tabla ni migracion ni Resource nuevos.
class LookupValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'value',
        'label',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /**
     * Opciones listas para un Select de Filament/API: ['value' => 'label'].
     */
    public static function optionsFor(string $category, bool $onlyActive = true): array
    {
        $query = static::forCategory($category)->ordered();

        if ($onlyActive) {
            $query->active();
        }

        return $query->pluck('label', 'value')->all();
    }
}
