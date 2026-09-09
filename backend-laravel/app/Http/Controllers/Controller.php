<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

// Extiende la clase base real de Laravel (antes no heredaba de nada) para
// tener middleware()/getMiddleware() — sin esto, authorizeResource() en
// los controllers de la API (Equipment, WorkOrder, etc.) tira
// "Call to undefined method ::middleware()" en cualquier request
// autenticado. Encontrado 2026-09-09 al instalar Pest y correr por primera
// vez WorkOrderTest/EquipmentTest, que antes crasheaban el test runner
// entero antes de llegar a ejecutarse.
abstract class Controller extends BaseController
{
    protected function success($data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
