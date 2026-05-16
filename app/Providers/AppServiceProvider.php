<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
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
}
