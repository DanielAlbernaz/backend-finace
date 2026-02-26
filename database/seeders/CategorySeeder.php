<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Categorias padrão de RECEITAS (user_id = null)
        $revenueCategories = [
            'Salário',
            'Freelance',
            'Investimentos',
            'Aluguel',
            'Vendas',
            'Bonificações',
            'Outras Receitas',
        ];

        foreach ($revenueCategories as $title) {
            Category::firstOrCreate(
                [
                    'title' => $title,
                    'type' => 'revenue',
                    'user_id' => null,
                ],
                [
                    'is_custom' => false,
                    'finance_account_id' => null,
                    'is_active' => true,
                ]
            );
        }

        // Categorias padrão de DESPESAS (user_id = null)
        $expenseCategories = [
            'Alimentação',
            'Transporte',
            'Moradia',
            'Saúde',
            'Educação',
            'Lazer',
            'Compras',
            'Contas e Serviços',
            'Impostos',
            'Outras Despesas',
        ];

        foreach ($expenseCategories as $title) {
            Category::firstOrCreate(
                [
                    'title' => $title,
                    'type' => 'expense',
                    'user_id' => null,
                ],
                [
                    'is_custom' => false,
                    'finance_account_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
