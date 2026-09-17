<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LookupValue;
use Illuminate\Http\JsonResponse;

// Solo lectura, para poblar selects dinamicos (ej: tipo de cliente) desde
// la PWA sin hardcodear opciones. Cualquier usuario autenticado puede leer
// listas activas - no es informacion sensible, es igual a lo que ya se ve
// en el desplegable de Filament. Administrar (crear/editar/desactivar) es
// solo desde Filament > Administracion > Listas configurables.
class LookupValueController extends Controller
{
    public function index(string $category): JsonResponse
    {
        $options = LookupValue::forCategory($category)->active()->ordered()
            ->get(['value', 'label']);

        return response()->json(['data' => $options]);
    }
}
