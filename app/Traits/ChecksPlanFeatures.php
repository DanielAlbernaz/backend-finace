<?php

namespace App\Traits;

use App\Models\FinanceAccount;

trait ChecksPlanFeatures
{
    /**
     * Verifica se a finança atual do usuário tem uma feature específica
     */
    protected function checkFeature(string $feature): void
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        if (!$currentFinanceAccount->hasFeature($feature)) {
            abort(403, 'Esta funcionalidade não está disponível no seu plano atual.');
        }
    }

    /**
     * Verifica se a finança pode adicionar mais usuários
     */
    protected function checkUserLimit(): void
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        if (!$currentFinanceAccount->canAddMoreUsers()) {
            abort(403, 'Limite de usuários do plano atingido. Atualize seu plano para adicionar mais usuários.');
        }
    }
}
