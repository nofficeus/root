<?php

namespace App\Providers;

use App\CoinAdapters\BitcoinAdapter;
use App\CoinAdapters\BitcoinCashAdapter;
use App\CoinAdapters\DashAdapter;
use App\CoinAdapters\EthereumAdapter;
use App\CoinAdapters\LitecoinAdapter;
use App\Helpers\InteractsWithStore;
use App\Helpers\Settings;
use App\Helpers\ValueStore;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use NeoScrypts\BitGo\BitGo;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(BitGo::class, function () {
            return new BitGo(
                config('services.bitgo.host'),
                config('services.bitgo.port'),
                config('services.bitgo.token')
            );
        });

        $this->app->singleton(ValueStore::class, function () {
            return ValueStore::make(storage_path('app/settings.json'));
        });

        $this->app->singleton(Settings::class, function () {
            return new Settings();
        });

        $this->app->singleton('coin.adapters', function () {
            return collect([
                BitcoinAdapter::class,
                BitcoinCashAdapter::class,
                DashAdapter::class,
                LitecoinAdapter::class,
                EthereumAdapter::class,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();
    }
}
