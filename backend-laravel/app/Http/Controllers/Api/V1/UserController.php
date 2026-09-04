<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * super_admin pasa siempre via Gate::before; administrador/supervisor
     * tienen estos permisos via SyncShieldPermissionsSeeder.
     */
    private function assertPermission(Request $request, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403, 'No autorizado');
    }

    public function index(Request $request)
    {
        $this->assertPermission($request, 'view_any_user');

        $query = User::with('roles');

        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }
        if (request('role')) {
            $query->role(request('role'));
        }

        $users = $query->paginate(request('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json(new UserResource($user->load('roles')), 201);
    }

    public function show(Request $request, User $user)
    {
        $this->assertPermission($request, 'view_user');

        return new UserResource($user->load('roles', 'permissions'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return response()->json(new UserResource($user->load('roles')));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->assertPermission($request, 'delete_user');
        abort_if($user->id === 1 || $user->hasAnyRole(['administrador', 'super_admin']), 403, 'No se puede eliminar esta cuenta');

        $this->userService->delete($user);

        return response()->json(null, 204);
    }
}
