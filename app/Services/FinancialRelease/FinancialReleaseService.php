<?php

namespace App\Services\FinancialRelease;

use App\Models\FinancialRelease;
use App\Repositories\FinancialRelease\FinancialReleaseRepository;
use App\Services\BaseService;
use App\Services\Installment\InstallmentServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FinancialReleaseService extends BaseService implements FinancialReleaseServiceInterface
{
    private $installmentService;

    public function __construct(
        FinancialReleaseRepository $repository,
        InstallmentServiceInterface $installmentService
    ) {
        parent::__construct($repository);
        $this->installmentService = $installmentService;
    }

    public function createFinancialRelease(array $data)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        // Adiciona finance_account_id e created_by aos dados
        $data['finance_account_id'] = $currentFinanceAccount->id;
        $data['created_by'] = $user->id;

        $dataFinancialRelease = $this->installmentService->createInstallments($data);

        // Verifica se retornou um array associativo (lançamento único) ou array indexado (parcelas)
        $isIndexedArray = is_array($dataFinancialRelease) &&
            !empty($dataFinancialRelease) &&
            array_keys($dataFinancialRelease) === range(0, count($dataFinancialRelease) - 1);

        // Se não for array indexado (é associativo = lançamento único), normaliza
        if (is_array($dataFinancialRelease) && !$isIndexedArray) {
            $dataFinancialRelease = [$dataFinancialRelease];
        }

        // Se não for array, tenta converter
        if (!is_array($dataFinancialRelease)) {
            Log::error('InstallmentService retornou tipo inválido', [
                'type' => gettype($dataFinancialRelease),
                'value' => $dataFinancialRelease
            ]);
            abort(500, 'Erro ao processar dados do lançamento: tipo de dados inválido.');
        }

        // Se for apenas um lançamento (não parcelado)
        if (count($dataFinancialRelease) === 1) {
            $releaseData = $dataFinancialRelease[0];

            // Garante que é um array
            if (!is_array($releaseData)) {
                Log::error('Dados do lançamento não são um array', [
                    'type' => gettype($releaseData),
                    'value' => $releaseData
                ]);
                abort(500, 'Erro ao processar dados do lançamento.');
            }

            // Garante que todos os campos necessários existam
            $requiredFields = ['type', 'value', 'date', 'due_date', 'repetition', 'category_id', 'finance_account_id', 'created_by'];
            foreach ($requiredFields as $field) {
                if (!isset($releaseData[$field])) {
                    Log::error('Campo obrigatório ausente', [
                        'field' => $field,
                        'data' => array_keys($releaseData)
                    ]);
                    abort(500, "Campo obrigatório ausente: {$field}");
                }
            }

            // Garante que campos opcionais existam
            if (!isset($releaseData['installment_id'])) {
                $releaseData['installment_id'] = null;
            }
            if (!isset($releaseData['portion'])) {
                $releaseData['portion'] = null;
            }
            if (!isset($releaseData['payment_date'])) {
                $releaseData['payment_date'] = null;
            }
            if (!isset($releaseData['observation'])) {
                $releaseData['observation'] = null;
            }
            if (!isset($releaseData['descrition'])) {
                $releaseData['descrition'] = null;
            }
            if (!isset($releaseData['updated_at'])) {
                $releaseData['updated_at'] = now();
            }

            // Cria o modelo temporário para calcular status
            try {
                $tempRelease = new FinancialRelease($releaseData);
                $tempRelease->updateStatus();
                $releaseData['status'] = $tempRelease->status;
            } catch (\Exception $e) {
                Log::error('Erro ao calcular status', [
                    'error' => $e->getMessage(),
                    'data' => $releaseData
                ]);
                // Define status padrão se houver erro
                $releaseData['status'] = 'pending';
            }

            // Cria o lançamento (dispara eventos do Observer)
            return FinancialRelease::create($releaseData);
        }

        // Para múltiplos lançamentos (parcelas), calcula status para cada um
        // Processa sem referências para evitar efeitos colaterais
        $processedReleases = [];
        foreach ($dataFinancialRelease as $key => $releaseData) {
            // Garante que é um array
            if (!is_array($releaseData)) {
                Log::error('Item do array de parcelas não é um array', [
                    'key' => $key,
                    'type' => gettype($releaseData),
                    'value' => $releaseData
                ]);
                continue;
            }

            // Cria uma cópia do array para evitar modificações indesejadas
            $releaseDataCopy = $releaseData;

            // Garante que campos opcionais existam
            if (!isset($releaseDataCopy['installment_id'])) {
                $releaseDataCopy['installment_id'] = null;
            }
            if (!isset($releaseDataCopy['portion'])) {
                $releaseDataCopy['portion'] = null;
            }
            if (!isset($releaseDataCopy['updated_at'])) {
                $releaseDataCopy['updated_at'] = now();
            }

            // Calcula status
            $tempRelease = new FinancialRelease($releaseDataCopy);
            $tempRelease->updateStatus();
            $releaseDataCopy['status'] = $tempRelease->status;

            $processedReleases[] = $releaseDataCopy;
        }

        // Substitui o array original pelo processado
        $dataFinancialRelease = $processedReleases;

        // Cria os registros um por um para obter os IDs e retornar dados completos
        $createdReleases = [];
        foreach ($dataFinancialRelease as $releaseData) {
            $createdRelease = FinancialRelease::create($releaseData);
            $createdReleases[] = $createdRelease;
        }

        // Retorna os lançamentos criados com todos os dados (incluindo IDs e portion)
        return $createdReleases;
    }

    /**
     * Implementa o index() da interface base (retorna Collection para compatibilidade)
     * Mas na prática, use list() para obter paginação
     */
    public function index(): EloquentCollection
    {
        // Delega para list() sem filtros e converte para Collection
        $paginated = $this->list([]);
        return new EloquentCollection($paginated->items());
    }

    /**
     * Lista lançamentos financeiros com filtros e paginação
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            // Retorna paginação vazia
            $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 100;
            $page = isset($filters['page']) ? (int) $filters['page'] : 1;
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Filtro padrão: sempre filtra por finance_account_id
        $query = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id);
        // SoftDeletes automaticamente ignora deletados (whereNull('deleted_at'))
        // Cancelados são retornados na listagem (mas não são somados nos cálculos)

        // Filtro opcional para excluir cancelados da listagem
        if (isset($filters['exclude_cancelled']) && $filters['exclude_cancelled'] === true) {
            $query->where('status', '!=', 'cancelled');
        }

        // Filtro por mês/ano (competência - campo date) - mantém compatibilidade
        if (isset($filters['month']) && isset($filters['year'])) {
            $month = (int) $filters['month'];
            $year = (int) $filters['year'];

            if ($month >= 1 && $month <= 12) {
                $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }

        // Filtro por range de data (competência)
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        // Filtro por range de due_date (vencimento)
        if (isset($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }
        if (isset($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        // Filtro por payment_date
        if (isset($filters['payment_date'])) {
            $query->where('payment_date', $filters['payment_date']);
        }
        if (isset($filters['payment_date_from'])) {
            $query->where('payment_date', '>=', $filters['payment_date_from']);
        }
        if (isset($filters['payment_date_to'])) {
            $query->where('payment_date', '<=', $filters['payment_date_to']);
        }

        // Filtro por tipo
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtro por status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por repetition
        if (isset($filters['repetition'])) {
            $query->where('repetition', $filters['repetition']);
        }

        // Filtro por range de valor
        if (isset($filters['value_min'])) {
            $query->where('value', '>=', $filters['value_min']);
        }
        if (isset($filters['value_max'])) {
            $query->where('value', '<=', $filters['value_max']);
        }

        // Filtro por category_id
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por created_by
        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Filtro por installment_id
        if (isset($filters['installment_id'])) {
            $query->where('installment_id', $filters['installment_id']);
        }

        // Filtro por descrition (busca parcial)
        if (isset($filters['descrition'])) {
            $query->where('descrition', 'like', '%' . $filters['descrition'] . '%');
        }

        // Filtro por observation (busca parcial)
        if (isset($filters['observation'])) {
            $query->where('observation', 'like', '%' . $filters['observation'] . '%');
        }

        // Filtro por portion
        if (isset($filters['portion'])) {
            $query->where('portion', $filters['portion']);
        }

        // Ordenação
        $orderBy = $filters['order_by'] ?? 'date';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 100;
        $page = isset($filters['page']) ? (int) $filters['page'] : 1;

        // Limita per_page entre 1 e 100
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);

        // Executa a query com paginação
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return $results;
    }

    public function find(Model $model): Model
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(404, 'Finança não encontrada.');
        }

        // Se já recebeu o model, verifica se pertence à finança
        if ($model->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Acesso negado.');
        }

        return $model;
    }

    /**
     * Sobrescreve o método update para evitar problemas com cache que depende de office_id
     */
    public function update(array $data, Model $model): Model
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        // Verifica se o lançamento pertence à finança do usuário
        if ($model->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Acesso negado.');
        }

        // Se o lançamento está pago ou cancelado, só permite alterar o campo status (e payment_date quando for reabrir)
        if (in_array($model->status, ['paid', 'cancelled'])) {
            // Verifica se está tentando alterar algum campo além do permitido
            $allowedFields = ['status'];
            $fieldsToUpdate = array_keys($data);
            $unauthorizedFields = array_diff($fieldsToUpdate, $allowedFields);

            if (!empty($unauthorizedFields)) {
                abort(403, 'Não é possível editar lançamentos com status "pago" ou "cancelado". Apenas o campo "status" pode ser alterado.');
            }

            // Ao mudar de "pago" para "pendente" ou "vencido", limpa payment_date para manter consistência
            if ($model->status === 'paid' && isset($data['status']) && in_array($data['status'], ['pending', 'overdue'])) {
                $data['payment_date'] = null;
            }
        }

        // Atualiza o modelo diretamente, sem cache
        $model->update($data);

        // Recarrega o modelo para garantir dados atualizados
        return $model->fresh();
    }

    /**
     * Sobrescreve o método delete para evitar problemas com cache que depende de office_id
     */
    public function delete(Model $model): void
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        // Verifica se o lançamento pertence à finança do usuário
        if ($model->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Acesso negado.');
        }

        // Deleta diretamente, sem cache
        $model->delete();
    }

    /**
     * Calcula o total de receitas para um mês/ano específico
     */
    public function getTotalRevenue(int $month, int $year): float
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return 0;
        }

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        return FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
            ->where('type', 'revenue')
            ->where('status', '!=', 'cancelled') // Ignora cancelados
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value') ?? 0; // SoftDeletes automaticamente ignora deletados
    }

    /**
     * Calcula o total de despesas para um mês/ano específico
     */
    public function getTotalExpense(int $month, int $year): float
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return 0;
        }

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        return FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
            ->where('type', 'expense')
            ->where('status', '!=', 'cancelled') // Ignora cancelados
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('value') ?? 0; // SoftDeletes automaticamente ignora deletados
    }

    /**
     * Calcula o saldo do mês (receitas - despesas)
     */
    public function getMonthBalance(int $month, int $year): float
    {
        $revenue = $this->getTotalRevenue($month, $year);
        $expense = $this->getTotalExpense($month, $year);

        return $revenue - $expense;
    }

    /**
     * Busca contas próximas do vencimento (baseado em due_date)
     */
    public function getUpcomingDueReleases(int $days = 7, array $filters = []): EloquentCollection
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return new EloquentCollection([]);
        }

        $today = \Carbon\Carbon::today();
        $endDate = $today->copy()->addDays($days);

        $query = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
            ->where('status', '!=', 'paid') // Apenas não pagas
            ->where('status', '!=', 'cancelled') // Ignora cancelados
            ->whereBetween('due_date', [$today, $endDate])
            ->orderBy('due_date', 'asc'); // SoftDeletes automaticamente ignora deletados

        // Filtro por mês/ano (competência)
        if (isset($filters['month']) && isset($filters['year'])) {
            $month = (int) $filters['month'];
            $year = (int) $filters['year'];

            if ($month >= 1 && $month <= 12) {
                $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                $endDateFilter = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

                $query->whereBetween('date', [$startDate, $endDateFilter]);
            }
        }

        return $query->get();
    }

    /**
     * Busca últimos lançamentos
     */
    public function getLatestReleases(int $limit = 100, array $filters = []): EloquentCollection
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return new EloquentCollection([]);
        }

        $query = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id);
        // SoftDeletes automaticamente ignora deletados (whereNull('deleted_at'))
        // Cancelados são retornados na listagem (mas não são somados nos cálculos)

        // Filtro por mês/ano (competência)
        if (isset($filters['month']) && isset($filters['year'])) {
            $month = (int) $filters['month'];
            $year = (int) $filters['year'];

            if ($month >= 1 && $month <= 12) {
                $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

                $query->whereBetween('date', [$startDate, $endDate]);
            }
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Lista todos os parcelamentos (installments) agrupados
     * Retorna apenas lançamentos do tipo installments com installment_id preenchido
     *
     * @param array $filters Filtros opcionais (month, year para filtrar por data de competência)
     * @return Collection
     */
    public function listInstallments(array $filters = []): Collection
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return new Collection([]);
        }

        // Query base para buscar parcelas
        $query = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
            ->where('repetition', 'installments')
            ->whereNotNull('installment_id')
            ->where('status', '!=', 'cancelled') // Ignora cancelados na listagem de parcelamentos
            ->with(['category', 'paymentMethod']); // Carrega categoria e método de pagamento

        // Filtro por mês/ano (data de competência)
        $hasDateFilter = false;
        $installmentIdsInMonth = [];

        if (isset($filters['month']) && isset($filters['year'])) {
            $month = (int) $filters['month'];
            $year = (int) $filters['year'];

            if ($month >= 1 && $month <= 12) {
                $hasDateFilter = true;
                $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

                // Primeiro, busca os installment_id que têm pelo menos uma parcela no mês/ano especificado
                $installmentIdsQuery = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
                    ->where('repetition', 'installments')
                    ->whereNotNull('installment_id')
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('date', [$startDate, $endDate]);

                if (isset($filters['payment_method_id'])) {
                    $installmentIdsQuery->where('payment_method_id', $filters['payment_method_id']);
                }

                $installmentIdsInMonth = $installmentIdsQuery->select('installment_id')
                    ->distinct()
                    ->pluck('installment_id')
                    ->filter(function ($value) {
                        return $value !== null && is_numeric($value);
                    })
                    ->unique()
                    ->values()
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->toArray();

                // Se não houver parcelamentos no mês, retorna vazio
                if (empty($installmentIdsInMonth)) {
                    return new Collection([]);
                }

                // Filtra apenas os installment_id que têm parcelas no mês
                $query->whereIn('installment_id', $installmentIdsInMonth);
            }
        }

        // Filtro por método de pagamento (quando não há filtro de mês/ano ou em conjunto)
        if (isset($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        // Busca TODAS as parcelas dos installment_id encontrados (para calcular totais corretos)
        // Se não houver filtro de data, busca todas as parcelas
        $installments = $query->orderBy('date', 'asc')
            ->get()
            ->groupBy('installment_id');

        // Formata os dados para retornar informações agregadas de cada parcelamento
        $formattedInstallments = $installments->map(function ($releases, $installmentId) {
            $firstRelease = $releases->first();
            // Calcula totais com TODAS as parcelas do installment_id (não apenas as filtradas)
            $totalValue = $releases->sum('value');
            $paidReleases = $releases->where('status', 'paid')->whereNotNull('payment_date');
            $paidValue = $paidReleases->sum('value');
            $totalCount = $releases->count();
            $paidCount = $paidReleases->count();
            $remainingValue = $totalValue - $paidValue;

            return [
                'installment_id' => (int) $installmentId,
                'descrition' => $firstRelease->descrition,
                'category_id' => $firstRelease->category_id,
                'category' => $firstRelease->category ? [
                    'id' => $firstRelease->category->id,
                    'title' => $firstRelease->category->title,
                ] : null,
                'payment_method_id' => $firstRelease->payment_method_id,
                'payment_method' => $firstRelease->paymentMethod ? [
                    'id' => $firstRelease->paymentMethod->id,
                    'name' => $firstRelease->paymentMethod->name,
                ] : null,
                'type' => $firstRelease->type,
                'total_value' => (float) $totalValue,
                'total_installments' => $totalCount,
                'paid_installments' => $paidCount,
                'paid_value' => (float) $paidValue,
                'remaining_value' => (float) $remainingValue,
                'first_date' => $firstRelease->date->format('Y-m-d'),
                'last_date' => $releases->last()->date->format('Y-m-d'),
                'created_at' => $firstRelease->created_at->toIso8601String(),
            ];
        })->values();

        return $formattedInstallments;
    }

    /**
     * Retorna detalhes de um parcelamento específico com todas as suas parcelas
     * Inclui informações agregadas e lista de parcelas
     *
     * @param int $installmentId
     * @return array
     */
    public function getInstallmentDetails(int $installmentId): array
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        // Busca todas as parcelas deste installment_id
        $releases = FinancialRelease::where('finance_account_id', $currentFinanceAccount->id)
            ->where('installment_id', $installmentId)
            ->where('repetition', 'installments')
            ->with(['category', 'paymentMethod']) // Carrega categoria e método de pagamento
            ->orderBy('date', 'asc')
            ->get();

        if ($releases->isEmpty()) {
            abort(404, 'Parcelamento não encontrado.');
        }

        $firstRelease = $releases->first();

        // Calcula estatísticas
        $totalValue = $releases->sum('value');
        $paidReleases = $releases->where('status', 'paid')->whereNotNull('payment_date');
        $paidValue = $paidReleases->sum('value');
        $totalCount = $releases->count();
        $paidCount = $paidReleases->count();
        $remainingValue = $totalValue - $paidValue;

        // Formata lista de parcelas
        $parcels = $releases->map(function ($release) {
            return [
                'id' => $release->id,
                'portion' => $release->portion, // Ex: "1/12"
                'value' => (float) $release->value,
                'date' => $release->date->format('Y-m-d'),
                'due_date' => $release->due_date->format('Y-m-d'),
                'payment_date' => $release->payment_date ? $release->payment_date->format('Y-m-d') : null,
                'payment_method_id' => $release->payment_method_id,
                'payment_method' => $release->paymentMethod ? [
                    'id' => $release->paymentMethod->id,
                    'name' => $release->paymentMethod->name,
                ] : null,
                'status' => $release->status,
                'created_at' => $release->created_at->toIso8601String(),
            ];
        });

        return [
            'installment_id' => (int) $installmentId,
            'descrition' => $firstRelease->descrition,
            'observation' => $firstRelease->observation,
            'category_id' => $firstRelease->category_id,
            'category' => $firstRelease->category ? [
                'id' => $firstRelease->category->id,
                'title' => $firstRelease->category->title,
            ] : null,
            'payment_method_id' => $firstRelease->payment_method_id,
            'payment_method' => $firstRelease->paymentMethod ? [
                'id' => $firstRelease->paymentMethod->id,
                'name' => $firstRelease->paymentMethod->name,
            ] : null,
            'type' => $firstRelease->type,
            'total_value' => (float) $totalValue,
            'total_installments' => $totalCount,
            'paid_installments' => $paidCount,
            'paid_value' => (float) $paidValue,
            'remaining_value' => (float) $remainingValue,
            'first_date' => $firstRelease->date->format('Y-m-d'),
            'last_date' => $releases->last()->date->format('Y-m-d'),
            'created_at' => $firstRelease->created_at->toIso8601String(),
            'parcels' => $parcels,
        ];
    }
}
