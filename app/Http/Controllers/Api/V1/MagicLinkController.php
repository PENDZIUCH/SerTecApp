<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        // Generar token que expira en 30 días
        $token = $user->createToken('magic-link', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'url' => "https://pro.pendziuch.com/l?t={$token}",
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        // Sanctum ya valida el token automáticamente con el middleware
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $request->token
        ]);
    }
}
