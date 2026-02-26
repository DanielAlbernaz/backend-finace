<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateFreePlanInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:enable-installments-free';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Habilita a feature installments no plano Free';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $freePlan = \App\Models\Plan::where('name', 'Free')->first();

        if (!$freePlan) {
            $this->error('Plano Free não encontrado!');
            return Command::FAILURE;
        }

        $features = $freePlan->features ?? [];
        $features['installments'] = true;
        $freePlan->features = $features;
        $freePlan->save();

        $this->info('Plano Free atualizado com sucesso! Feature installments habilitada.');
        return Command::SUCCESS;
    }
}
