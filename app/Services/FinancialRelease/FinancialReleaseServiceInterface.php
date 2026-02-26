<?php

namespace App\Services\FinancialRelease;

use App\Services\BaseServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FinancialReleaseServiceInterface extends BaseServiceInterface
{
    public function createFinancialRelease(array $data);

    /**
     * Lista lançamentos financeiros com filtros e paginação
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function list(array $filters = []): LengthAwarePaginator;

    public function getTotalRevenue(int $month, int $year): float;

    public function getTotalExpense(int $month, int $year): float;

    public function getMonthBalance(int $month, int $year): float;

    public function getUpcomingDueReleases(int $days = 7, array $filters = []): EloquentCollection;

    public function getLatestReleases(int $limit = 100, array $filters = []): EloquentCollection;

    /**
     * Lista todos os parcelamentos (installments) agrupados
     *
     * @param array $filters Filtros opcionais (month, year para filtrar por data de competência)
     * @return Collection Retorna Illuminate\Support\Collection (resultado de map()->values())
     */
    public function listInstallments(array $filters = []): Collection;

    /**
     * Retorna detalhes de um parcelamento específico com todas as suas parcelas
     *
     * @param int $installmentId
     * @return array
     */
    public function getInstallmentDetails(int $installmentId): array;
}
