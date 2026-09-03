<?php

namespace App\Http\Controllers\Api\V1;

use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controller;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Role::all(['id', 'name'])->values(),
        ]);
    }
}
