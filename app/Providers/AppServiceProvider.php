<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
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
        $this->forceRequestRootUrl();

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

    private function forceRequestRootUrl(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = $this->app->make(Request::class);

        if ($request->getHost() === '') {
            return;
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());
    }
}
