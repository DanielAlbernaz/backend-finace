<?php

namespace App\Providers;

use App\Services\FinancialRelease\FinancialReleaseService;
use App\Services\FinancialRelease\FinancialReleaseServiceInterface;
use App\Services\Installment\InstallmentService;
use App\Services\Installment\InstallmentServiceInterface;
use Illuminate\Support\ServiceProvider;

class ServiceLayerServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->bind(
            FinancialReleaseServiceInterface::class,
            FinancialReleaseService::class
        );

        $this->app->bind(
            InstallmentServiceInterface::class,
            InstallmentService::class
        );
    }
}
