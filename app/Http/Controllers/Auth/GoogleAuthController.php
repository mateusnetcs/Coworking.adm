<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|Response
    {
        if (! $this->isGoogleOAuthConfigured()) {
            return $this->redirectToLoginWithError(
                'Login com Google ainda não foi configurado neste servidor. '
                .'Peça ao administrador para preencher GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no arquivo .env.'
            );
        }

        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->isGoogleOAuthConfigured()) {
            return $this->redirectToLoginWithError('Credenciais do Google ausentes no servidor.');
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $exception) {
            report($exception);

            $message = $exception->getMessage();

            if (str_contains($message, 'SSL certificate') || str_contains($message, 'cURL error 60')) {
                return $this->redirectToLoginWithError(
                    'Erro de certificado SSL no PHP (comum no Windows). Reinicie o servidor com '
                    .'"npm run serve:api" — o projeto já inclui .local/cacert.pem configurado.'
                );
            }

            $hint = 'Verifique: (1) URI no Google = '.config('services.google.redirect')
                .'; (2) seu e-mail em Usuários de teste; (3) reinicie o servidor após mudar o .env.';

            return $this->redirectToLoginWithError(
                'Não foi possível concluir o login com Google. '.$hint
            );
        }
        $email = $googleUser->getEmail();

        if (! is_string($email) || $email === '') {
            abort(Response::HTTP_BAD_REQUEST, 'Google account has no email.');
        }

        $user = DB::transaction(function () use ($googleUser, $email): User {
            $user = User::query()->firstOrNew(['email' => $email]);

            if ($user->exists === false) {
                $user->name = $googleUser->getName() ?: (string) Str::before($email, '@');
                $user->password = null;
            }

            $user->google_id = $googleUser->getId();
            $avatar = $googleUser->getAvatar();
            $user->avatar = is_string($avatar) && $avatar !== '' ? $avatar : null;
            $user->save();

            $studentRoleId = Role::query()->where('name', Role::STUDENT)->value('id');

            if ($studentRoleId !== null && ! $user->roles()->where('roles.id', $studentRoleId)->exists()) {
                $user->roles()->attach($studentRoleId);
            }

            $adminEmail = config('coworking.admin_email');

            if (is_string($adminEmail) && strcasecmp($user->email, $adminEmail) === 0) {
                $adminRoleId = Role::query()->where('name', Role::ADMIN)->value('id');

                if ($adminRoleId !== null && ! $user->roles()->where('roles.id', $adminRoleId)->exists()) {
                    $user->roles()->attach($adminRoleId);
                }
            }

            return $user->fresh(['roles']);
        });

        $plainTextToken = $user->createToken('google-oauth')->plainTextToken;
        $frontend = config('coworking.frontend_url');
        $fragment = 'token='.rawurlencode($plainTextToken);

        return redirect()->away($frontend.'/#/auth/callback?'.$fragment);
    }

    private function isGoogleOAuthConfigured(): bool
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        return is_string($clientId) && $clientId !== ''
            && is_string($clientSecret) && $clientSecret !== '';
    }

    private function redirectToLoginWithError(string $message): RedirectResponse
    {
        $frontend = rtrim((string) config('coworking.frontend_url'), '/');

        return redirect()->away($frontend.'/#/login?error='.rawurlencode($message));
    }
}
