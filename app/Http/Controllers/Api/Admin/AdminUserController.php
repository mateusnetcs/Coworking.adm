<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar', 'google_id', 'created_at']);

        return response()->json($users);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->save();

        $roleId = Role::query()->where('name', $request->validated('role'))->value('id');

        if ($roleId !== null) {
            $user->roles()->sync([$roleId]);
        }

        return response()->json($user->load('roles'));
    }
}
