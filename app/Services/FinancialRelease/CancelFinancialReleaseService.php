<?php

namespace App\Services\FinancialRelease;

use App\Models\FinancialRelease;
use App\Models\Installment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelFinancialReleaseService
{
    /**
     * Cancela uma parcela específica
     *
     * @param int $releaseId ID da parcela a cancelar
     * @param int $financeAccountId ID da finança (para validação de segurança)
     * @return FinancialRelease Parcela cancelada
     * @throws \Exception
     */
    public function cancelSingleRelease(int $releaseId, int $financeAccountId): FinancialRelease
    {
        // Busca a parcela (inclui deletadas para validação, mas não pode cancelar deletadas)
        $release = FinancialRelease::withTrashed()
            ->where('id', $releaseId)
            ->where('finance_account_id', $financeAccountId)
            ->first();

        if (!$release) {
            throw new \Exception('Parcela não encontrada ou não pertence à finança informada.');
        }

        // Verifica se a parcela está deletada
        if ($release->trashed()) {
            throw new \Exception('Não é possível cancelar uma parcela que já foi excluída.');
        }

        // Valida se pode ser cancelada
        if (!$release->canBeCancelled()) {
            if ($release->payment_date !== null) {
                throw new \Exception('Não é possível cancelar parcelas pagas. Esta parcela já foi paga.');
            }
            if ($release->status === 'cancelled') {
                throw new \Exception('Esta parcela já está cancelada.');
            }
            throw new \Exception('Esta parcela não pode ser cancelada. Verifique se ela está pendente.');
        }

        // Cancela a parcela em uma transação
        DB::beginTransaction();
        try {
            $release->status = 'cancelled';
            $release->save();

            DB::commit();

            Log::info('Parcela cancelada', [
                'id' => $release->id,
                'installment_id' => $release->installment_id,
                'portion' => $release->portion,
                'finance_account_id' => $financeAccountId
            ]);

            return $release;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao cancelar parcela', [
                'error' => $e->getMessage(),
                'id' => $releaseId,
                'finance_account_id' => $financeAccountId
            ]);
            throw $e;
        }
    }

    /**
     * Cancela uma ou múltiplas parcelas (método auxiliar para cancelamento em massa)
     *
     * @param array $releaseIds IDs das parcelas a cancelar
     * @param int $financeAccountId ID da finança (para validação de segurança)
     * @return Collection Parcelas canceladas
     * @throws \Exception
     */
    public function cancelReleases(array $releaseIds, int $financeAccountId): Collection
    {
        if (empty($releaseIds)) {
            throw new \Exception('Nenhuma parcela informada para cancelamento.');
        }

        // Busca as parcelas
        $releases = FinancialRelease::whereIn('id', $releaseIds)
            ->where('finance_account_id', $financeAccountId)
            ->get();

        if ($releases->isEmpty()) {
            throw new \Exception('Nenhuma parcela encontrada com os IDs informados.');
        }

        // Valida se todas podem ser canceladas
        $paidReleases = $releases->filter(function ($release) {
            return !$release->canBeCancelled();
        });

        if ($paidReleases->isNotEmpty()) {
            $paidIds = $paidReleases->pluck('id')->toArray();
            throw new \Exception(
                'Não é possível cancelar parcelas pagas. IDs: ' . implode(', ', $paidIds)
            );
        }

        // Cancela as parcelas em uma transação
        DB::beginTransaction();
        try {
            $cancelledReleases = new Collection();

            foreach ($releases as $release) {
                $release->status = 'cancelled';
                $release->save();
                $cancelledReleases->push($release);
            }

            DB::commit();

            Log::info('Parcelas canceladas', [
                'count' => $cancelledReleases->count(),
                'ids' => $cancelledReleases->pluck('id')->toArray(),
                'finance_account_id' => $financeAccountId
            ]);

            return $cancelledReleases;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao cancelar parcelas', [
                'error' => $e->getMessage(),
                'ids' => $releaseIds,
                'finance_account_id' => $financeAccountId
            ]);
            throw $e;
        }
    }

    /**
     * Cancela todas as parcelas futuras de um parcelamento
     * Baseado no installment_id de uma parcela específica
     *
     * @param int $releaseId ID da parcela de referência (para obter o installment_id)
     * @param int $financeAccountId ID da finança (para validação de segurança)
     * @return Collection Parcelas canceladas
     * @throws \Exception
     */
    public function cancelAllFutureReleases(int $releaseId, int $financeAccountId): Collection
    {
        // Busca a parcela de referência (não inclui deletadas)
        $referenceRelease = FinancialRelease::where('id', $releaseId)
            ->where('finance_account_id', $financeAccountId)
            ->first();

        if (!$referenceRelease) {
            throw new \Exception('Parcela não encontrada ou não pertence à finança informada.');
        }

        // Verifica se a parcela de referência tem installment_id
        if (!$referenceRelease->installment_id) {
            throw new \Exception('Esta parcela não faz parte de um parcelamento. Use cancel_all_future apenas para parcelas.');
        }

        // Verifica se o parcelamento existe e pertence à finança
        $installment = Installment::where('id', $referenceRelease->installment_id)
            ->whereHas('financialReleases', function ($query) use ($financeAccountId) {
                $query->where('finance_account_id', $financeAccountId);
            })
            ->first();

        if (!$installment) {
            throw new \Exception('Parcelamento não encontrado ou não pertence à finança informada.');
        }

        // Busca todas as parcelas futuras (não pagas e pendentes) do mesmo parcelamento
        // Parcelas futuras = data >= data da parcela de referência
        $futureReleases = FinancialRelease::where('installment_id', $referenceRelease->installment_id)
            ->where('finance_account_id', $financeAccountId)
            ->whereNull('payment_date')
            ->where('status', 'pending')
            ->where('date', '>=', $referenceRelease->date)
            ->get();

        if ($futureReleases->isEmpty()) {
            return new Collection([]);
        }

        // Valida se todas podem ser canceladas
        $paidReleases = $futureReleases->filter(function ($release) {
            return !$release->canBeCancelled();
        });

        if ($paidReleases->isNotEmpty()) {
            $paidIds = $paidReleases->pluck('id')->toArray();
            throw new \Exception(
                'Algumas parcelas futuras já foram pagas e não podem ser canceladas. IDs: ' . implode(', ', $paidIds)
            );
        }

        // Cancela as parcelas
        return $this->cancelReleases(
            $futureReleases->pluck('id')->toArray(),
            $financeAccountId
        );
    }

    /**
     * Cancela todas as parcelas futuras de uma recorrência (repetition = fixed)
     *
     * @param int $firstReleaseId ID do primeiro lançamento da recorrência
     * @param int $financeAccountId ID da finança (para validação de segurança)
     * @return Collection Parcelas canceladas
     * @throws \Exception
     */
    public function cancelRecurringFutureReleases(int $firstReleaseId, int $financeAccountId): Collection
    {
        // Busca o primeiro lançamento
        $firstRelease = FinancialRelease::where('id', $firstReleaseId)
            ->where('finance_account_id', $financeAccountId)
            ->where('repetition', 'fixed')
            ->first();

        if (!$firstRelease) {
            throw new \Exception('Lançamento recorrente não encontrado ou não pertence à finança informada.');
        }

        // Busca todas as parcelas futuras da mesma recorrência
        // Identifica pela mesma descrição, tipo, valor e categoria (ou outro critério)
        // Como não temos um campo de agrupamento, vamos usar uma combinação de campos
        $futureReleases = FinancialRelease::where('finance_account_id', $financeAccountId)
            ->where('repetition', 'fixed')
            ->where('type', $firstRelease->type)
            ->where('descrition', $firstRelease->descrition)
            ->where('category_id', $firstRelease->category_id)
            ->where('value', $firstRelease->value)
            ->whereNull('payment_date')
            ->where('status', 'pending')
            ->where('date', '>=', $firstRelease->date)
            ->get();

        if ($futureReleases->isEmpty()) {
            return new Collection([]);
        }

        // Cancela as parcelas
        return $this->cancelReleases(
            $futureReleases->pluck('id')->toArray(),
            $financeAccountId
        );
    }
}
