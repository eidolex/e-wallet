<?php

namespace Eidolex\EWallet;

use Eidolex\EWallet\Contracts\TopUpDataTransformerContract;
use Eidolex\EWallet\Contracts\TransferDataTransformerContract;
use Eidolex\EWallet\Contracts\WithdrawDataTransformerContract;
use Eidolex\EWallet\Transformers\TopUpDataTransformer;
use Eidolex\EWallet\Transformers\TransferFromDataTransformer;
use Eidolex\EWallet\Transformers\WithdrawDataTransformer;
use Illuminate\Support\ServiceProvider;

class EWalletServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/e-wallet.php', 'e-wallet'
        );

        $this->app->bind(TopUpDataTransformerContract::class, TopUpDataTransformer::class);
        $this->app->bind(TransferDataTransformerContract::class, TransferFromDataTransformer::class);
        $this->app->bind(WithdrawDataTransformerContract::class, WithdrawDataTransformer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/e-wallet.php' => config_path('e-wallet.php'),
            ], 'e-wallet-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'e-wallet-migrations');
        }
    }
}
