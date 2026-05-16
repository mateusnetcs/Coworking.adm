<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? 'mth@gmail.com';
$password = $argv[2] ?? '202200';

$adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
Role::query()->firstOrCreate(['name' => Role::STUDENT]);

$user = User::query()->firstOrNew(['email' => $email]);

if (! $user->exists) {
    $user->name = 'Administrador';
}

$user->password = Hash::make($password);
$user->save();

$user->roles()->sync([$adminRole->id]);

echo "Usuario: {$user->email}\n";
echo 'Admin: '.($user->fresh(['roles'])->isAdministrator() ? 'sim' : 'nao')."\n";
