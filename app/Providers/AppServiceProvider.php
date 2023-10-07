<?php

namespace App\Providers;

use App\Models\FinancialRelease;
use App\Models\Installment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'financial_release' => FinancialRelease::class
        ]);
        Relation::morphMap([
            'installment' => Installment::class
        ]);
    }
}
