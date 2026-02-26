<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Loggable
{
    /**
     * Registra uma ação no log
     *
     * @param string $action Ação realizada (created, updated, deleted)
     * @param array|null $oldValues Valores antigos (para updated)
     * @param array|null $newValues Valores novos (para created/updated)
     * @param string|null $description Descrição opcional
     * @return ActivityLog
     */
    public function logActivity(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): ActivityLog {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Usuário não autenticado. Não é possível registrar log.');
        }

        $financeAccount = $user->currentFinanceAccount();
        
        if (!$financeAccount) {
            throw new \Exception('Usuário não possui uma finança ativa. Não é possível registrar log.');
        }

        // Prepara os dados de mudanças
        $changes = null;
        if ($action === 'updated' && $oldValues !== null && $newValues !== null) {
            $changes = [
                'old' => $oldValues,
                'new' => $newValues,
            ];
        } elseif ($action === 'created' && $newValues !== null) {
            $changes = [
                'new' => $newValues,
            ];
        } elseif ($action === 'deleted' && $oldValues !== null) {
            $changes = [
                'old' => $oldValues,
            ];
        }

        // Obtém o IP do usuário
        $ipAddress = Request::ip();

        return ActivityLog::create([
            'user_id' => $user->id,
            'finance_account_id' => $financeAccount->id,
            'loggable_type' => get_class($this),
            'loggable_id' => $this->id,
            'action' => $action,
            'ip_address' => $ipAddress,
            'changes' => $changes,
            'description' => $description,
        ]);
    }

    /**
     * Relacionamento com os logs de atividade
     */
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }
}
