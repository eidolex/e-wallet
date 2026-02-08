<?php

namespace Eidolex\EWallet;

use Illuminate\Support\ServiceProvider;

class EWalletServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/e-wallet.php',
            'e-wallet'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/e-wallet.php' => config_path('e-wallet.php'),
            ], 'e-wallet-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'e-wallet-migrations');
        }
    }
}
