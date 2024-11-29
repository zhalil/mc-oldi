<?php

namespace App\Src\Amo;

use App\Src\Amo\Account\AmoAccountService;
use Illuminate\Support\ServiceProvider;

class AmoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AmoAccountService::class, function ($app) {
           return  new AmoAccountService();
        });
    }

    public function boot(): void
    {
    }
}
