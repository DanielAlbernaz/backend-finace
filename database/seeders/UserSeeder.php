<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FinanceAccount;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Garante que o plano Free existe (deve ser executado antes pelo PlanSeeder)
        $freePlan = Plan::where('name', 'Free')->first();

        if (!$freePlan) {
            throw new \Exception('Plano Free não encontrado. Execute o PlanSeeder primeiro.');
        }

        // Cria o usuário dentro de uma transação para garantir consistência
        DB::transaction(function () use ($freePlan) {
            // Cria o usuário
            $user = User::create([
                'name' => 'Daniel',
                'email' => 'daniel@daniel.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'), // password
                'remember_token' => Str::random(10),
            ]);

            // Cria a finança associada ao usuário (seguindo o padrão do AuthController)
            $financeAccount = FinanceAccount::create([
                'name' => 'Finança de ' . $user->name,
                'owner_id' => $user->id,
                'plan_id' => 2,
                'subscription_status' => 'active',
            ]);

            // Associa o usuário como owner da finança
            $financeAccount->users()->attach($user->id, [
                'role' => 'owner'
            ]);
        });
    }
}
