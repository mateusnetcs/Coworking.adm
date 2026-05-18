<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicAppUrl
{
    /**
     * URL pública do app (Coolify: SERVICE_URL_APP ou APP_URL).
     * Evita redirect_uri errado quando o proxy envia X-Forwarded-Proto diferente.
     */
    public static function base(?Request $request = null): string
    {
        foreach (['SERVICE_URL_APP', 'APP_URL'] as $key) {
            $value = env($key);

            if (is_string($value) && $value !== '') {
                return rtrim($value, '/');
            }
        }

        $request ??= request();

        if ($request !== null && $request->getHost() !== '') {
            return $request->getSchemeAndHttpHost();
        }

        return rtrim((string) config('app.url', 'http://localhost'), '/');
    }

    public static function googleCallback(?Request $request = null): string
    {
        return self::base($request).'/auth/google/callback';
    }
}
