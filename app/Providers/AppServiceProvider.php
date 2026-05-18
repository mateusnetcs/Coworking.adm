<?php

namespace App\Providers;

use App\Support\PublicAppUrl;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDynamicUrls();

        Vite::createAssetPathsUsing(function (string $path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return '/'.ltrim($path, '/');
        });

        $caBundle = base_path('.local/cacert.pem');

        if (! is_file($caBundle)) {
            return;
        }

        Http::globalOptions(['verify' => $caBundle]);

        $this->app->resolving(SocialiteFactory::class, function (SocialiteFactory $socialite) use ($caBundle): void {
            $httpClient = new Client(['verify' => $caBundle]);

            $socialite->driver('google')->setHttpClient($httpClient);
        });
    }

    private function configureDynamicUrls(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = $this->app->make(Request::class);
        $root = PublicAppUrl::base($request);

        if ($root === '') {
            return;
        }

        $scheme = parse_url($root, PHP_URL_SCHEME) ?: 'http';

        URL::forceRootUrl($root);
        URL::forceScheme($scheme);

        config([
            'app.url' => $root,
            'coworking.frontend_url' => $root,
            'services.google.redirect' => PublicAppUrl::googleCallback($request),
        ]);
    }
}
