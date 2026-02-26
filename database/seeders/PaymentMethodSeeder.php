<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Métodos padrão do sistema (is_custom = false, finance_account_id = null)
        $defaultMethods = [
            'Dinheiro',
            'Pix',
            'Débito',
            'Crédito',
        ];

        foreach ($defaultMethods as $methodName) {
            PaymentMethod::firstOrCreate(
                [
                    'name' => $methodName,
                    'finance_account_id' => null,
                ],
                [
                    'is_custom' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
