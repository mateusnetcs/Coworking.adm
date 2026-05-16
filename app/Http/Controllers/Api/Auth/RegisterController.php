<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $studentRoleId = Role::query()->where('name', Role::STUDENT)->value('id');

            if ($studentRoleId !== null) {
                $user->roles()->attach($studentRoleId);
            }

            $adminEmail = config('coworking.admin_email');

            if (is_string($adminEmail) && strcasecmp($user->email, $adminEmail) === 0) {
                $adminRoleId = Role::query()->where('name', Role::ADMIN)->value('id');

                if ($adminRoleId !== null) {
                    $user->roles()->attach($adminRoleId);
                }
            }

            return $user;
        });

        $user->load('roles');

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles'),
        ], JsonResponse::HTTP_CREATED);
    }
}
