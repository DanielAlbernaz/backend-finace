<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Plan::create([
            'name' => 'Free',
            'price' => 0.00,
            'max_users' => 1,
            'features' => [
                'installments' => true, // Habilitado no plano Free
                'reports' => false,
                'exports' => false,
                'advanced_charts' => false,
                'custom_payment_methods' => false,
                'custom_categories' => false,
            ],
        ]);

        \App\Models\Plan::create([
            'name' => 'Pro',
            'price' => 29.90,
            'max_users' => 10,
            'features' => [
                'installments' => true,
                'reports' => true,
                'exports' => true,
                'advanced_charts' => true,
                'custom_payment_methods' => true,
                'custom_categories' => true,
            ],
        ]);
    }
}
