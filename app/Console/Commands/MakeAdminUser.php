<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MakeAdminUser extends Command
{
    protected $signature = 'coworking:make-admin
                            {email : E-mail do administrador}
                            {--password= : Senha (mín. 4 caracteres)}
                            {--name= : Nome exibido no sistema}
                            {--update : Atualiza senha e perfil se o e-mail já existir}';

    protected $description = 'Cria ou promove um usuário com perfil admin no Coworking';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('E-mail inválido.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Senha do administrador'));

        if (strlen($password) < 4) {
            $this->components->error('A senha deve ter pelo menos 4 caracteres.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->nameFromEmail($email)));

        (new RoleSeeder)->run();

        $adminRoleId = Role::query()->where('name', Role::ADMIN)->value('id');

        if ($adminRoleId === null) {
            $this->components->error('Perfil admin não encontrado. Execute: php artisan db:seed --class=RoleSeeder');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $update = (bool) $this->option('update');

        if ($user !== null && ! $update) {
            $this->components->error("Usuário já existe: {$email}. Use --update para alterar senha e garantir perfil admin.");

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($user, $email, $password, $name, $adminRoleId): User {
            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->fill([
                    'name' => $name,
                    'password' => $password,
                ]);
                $user->email_verified_at ??= now();
                $user->save();
            }

            $user->roles()->sync([$adminRoleId]);

            return $user->fresh(['roles']);
        });

        $this->components->info('Administrador configurado com sucesso.');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', (string) $user->id],
                ['Nome', $user->name],
                ['E-mail', $user->email],
                ['Perfis', $user->roles->pluck('name')->join(', ')],
            ],
        );

        return self::SUCCESS;
    }

    private function nameFromEmail(string $email): string
    {
        $local = Str::before($email, '@');
        $local = str_replace(['.', '_', '-'], ' ', $local);

        return Str::title(trim($local)) ?: 'Administrador';
    }
}
