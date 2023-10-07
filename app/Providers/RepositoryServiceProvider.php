<?php

namespace App\Providers;

use App\Repositories\FinancialRelease\FinancialReleaseRepository;
use App\Repositories\FinancialRelease\FinancialReleaseRepositoryEloquent;
use App\Repositories\Installment\InstallmentRepository;
use App\Repositories\Installment\InstallmentRepositoryEloquent;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(
            FinancialReleaseRepository::class,
            FinancialReleaseRepositoryEloquent::class
        );

        $this->app->bind(
            InstallmentRepository::class,
            InstallmentRepositoryEloquent::class
        );
    }
}
