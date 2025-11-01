<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Order_product;
use App\Observers\OrderProductObserver;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        // Torna disponível a função SQL unaccent() quando o driver for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction(
                'unaccent',
                fn(?string $v) => Str::ascii($v ?? ''),
                1
            );
        }

        // Cria observer para order_products
        Order_product::observe(OrderProductObserver::class);
    }
}
