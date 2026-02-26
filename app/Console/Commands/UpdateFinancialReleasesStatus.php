<?php

namespace App\Console\Commands;

use App\Models\FinancialRelease;
use Illuminate\Console\Command;

class UpdateFinancialReleasesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial-releases:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o status de todos os lançamentos financeiros baseado em date e payment_date';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Atualizando status dos lançamentos financeiros...');

        $financialReleases = FinancialRelease::all();
        $updated = 0;

        foreach ($financialReleases as $release) {
            $oldStatus = $release->status;
            $release->updateStatus();
            
            if ($oldStatus !== $release->status) {
                $release->save();
                $updated++;
            }
        }

        $this->info("Status atualizado para {$updated} lançamento(s).");
        $this->info("Total de lançamentos processados: {$financialReleases->count()}");

        return Command::SUCCESS;
    }
}
