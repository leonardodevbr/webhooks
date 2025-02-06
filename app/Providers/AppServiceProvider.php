<?php

namespace App\Providers;

use App\Interfaces\IPaymentService;
use App\Services\EfiPayService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IPaymentService::class, function () {
            $provider = config('services.payment.provider');

            return match ($provider) {
                default => new EfiPayService()
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        VerifyCsrfToken::except([
            '*'
        ]);
    }
}
