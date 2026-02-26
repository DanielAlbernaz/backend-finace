<?php

namespace App\Observers;

use App\Models\FinancialRelease;
use Illuminate\Support\Facades\Log;

class FinancialReleaseObserver
{
    /**
     * Handle the FinancialRelease "creating" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function creating(FinancialRelease $financialRelease)
    {
        // Calcula e define o status antes de criar
        $financialRelease->updateStatus();
    }

    /**
     * Handle the FinancialRelease "created" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function created(FinancialRelease $financialRelease)
    {
        try {
            // Registra log de criação
            $newValues = $financialRelease->getAttributes();
            // Remove campos que não são relevantes para o log
            unset($newValues['id'], $newValues['created_at'], $newValues['updated_at'], $newValues['deleted_at']);
            
            $financialRelease->logActivity(
                'created',
                null,
                $newValues,
                'Lançamento financeiro criado'
            );
        } catch (\Exception $e) {
            // Log do erro, mas não interrompe o fluxo
            Log::error('Erro ao registrar log de criação: ' . $e->getMessage());
        }
    }

    /**
     * Handle the FinancialRelease "updating" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function updating(FinancialRelease $financialRelease)
    {
        // Se o status está sendo alterado manualmente, respeita a alteração
        if ($financialRelease->isDirty('status')) {
            // Se estiver sendo cancelado manualmente, não recalcula
            if ($financialRelease->status === 'cancelled') {
                return;
            }

            // Se estava cancelado e agora não está, permite a mudança manual
            // Não recalcula automaticamente, respeita o status informado
            if ($financialRelease->getOriginal('status') === 'cancelled' && $financialRelease->status !== 'cancelled') {
                // Permite mudança manual de cancelled para outro status
                // Não recalcula automaticamente aqui
                return;
            }
        }

        // Se já estiver cancelado e o status não está sendo alterado, não recalcula
        if ($financialRelease->getOriginal('status') === 'cancelled') {
            return;
        }

        // Verifica se payment_date ou date foram alterados
        if ($financialRelease->isDirty(['payment_date', 'date'])) {
            // Recalcula o status
            $financialRelease->updateStatus();
        }
    }

    /**
     * Handle the FinancialRelease "updated" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function updated(FinancialRelease $financialRelease)
    {
        try {
            // Obtém apenas os campos que foram alterados
            $changedAttributes = $financialRelease->getChanges();
            
            // Remove campos de timestamp que não são relevantes
            unset($changedAttributes['updated_at']);
            
            if (!empty($changedAttributes)) {
                // Obtém os valores originais dos campos alterados
                $oldValues = [];
                $newValues = [];
                
                foreach ($changedAttributes as $key => $newValue) {
                    $oldValues[$key] = $financialRelease->getOriginal($key);
                    $newValues[$key] = $newValue;
                }
                
                $financialRelease->logActivity(
                    'updated',
                    $oldValues,
                    $newValues,
                    'Lançamento financeiro alterado'
                );
            }
        } catch (\Exception $e) {
            // Log do erro, mas não interrompe o fluxo
            Log::error('Erro ao registrar log de atualização: ' . $e->getMessage());
        }
    }

    /**
     * Handle the FinancialRelease "deleted" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function deleted(FinancialRelease $financialRelease)
    {
        try {
            // Registra log de exclusão (soft delete)
            $oldValues = $financialRelease->getAttributes();
            // Remove campos que não são relevantes para o log
            unset($oldValues['id'], $oldValues['created_at'], $oldValues['updated_at'], $oldValues['deleted_at']);
            
            $financialRelease->logActivity(
                'deleted',
                $oldValues,
                null,
                'Lançamento financeiro excluído'
            );
        } catch (\Exception $e) {
            // Log do erro, mas não interrompe o fluxo
            Log::error('Erro ao registrar log de exclusão: ' . $e->getMessage());
        }
    }

    /**
     * Handle the FinancialRelease "restored" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function restored(FinancialRelease $financialRelease)
    {
        //
    }

    /**
     * Handle the FinancialRelease "force deleted" event.
     *
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return void
     */
    public function forceDeleted(FinancialRelease $financialRelease)
    {
        //
    }
}
