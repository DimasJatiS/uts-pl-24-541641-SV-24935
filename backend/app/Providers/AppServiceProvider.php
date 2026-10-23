<?php

namespace App\Providers;

use App\Services\PriceService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PriceService::class, fn () => new PriceService(
            baseUrl: (string) config('services.coingecko.base_url'),
            apiKey: config('services.coingecko.api_key') ?: null,
            timeout: (int) config('services.coingecko.timeout', 5),
            cacheTtl: (int) config('services.coingecko.cache_ttl', 60),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Saat development/test: error kalau ada lazy loading (N+1) atau atribut yang tidak ada.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Cegah perintah destruktif (migrate:fresh, db:wipe) di production.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        // Login/register: 5x per menit per kombinasi email+IP, 20x per menit per IP.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by('ip:'.$request->ip()),
        ]);

        // Endpoint lain: 60x per menit per user (atau per IP kalau belum login).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip()));
    }
}
