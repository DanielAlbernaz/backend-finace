<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialRelease\CancelFinancialReleaseRequest;
use App\Http\Requests\FinancialRelease\CreateFinancialReleaseRequest;
use App\Models\FinancialRelease;
use App\Models\Installment;
use App\Services\FinancialRelease\CancelFinancialReleaseService;
use App\Services\FinancialRelease\FinancialReleaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class FinancialReleaseController extends Controller
{
    protected $service;

    /**
     * Method __construct
     *
     * @param FinancialReleaseServiceInterface $service
     *
     * @return void
     */
    public function __construct(FinancialReleaseServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index(\App\Http\Requests\FinancialRelease\FilterFinancialReleaseRequest $request)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        // Captura todos os filtros validados (funciona tanto para GET quanto POST)
        $filters = $request->validated();

        // Mantém per_page e page para paginação (já validados no Request)
        // per_page padrão é 25 se não informado

        $financialReleases = $this->service->list($filters);

        // Retorna resposta paginada no formato padrão do Laravel
        return response()->json($financialReleases, 200);
    }

    public function store(CreateFinancialReleaseRequest $request, FinancialRelease $financialRelease)
    {
        $this->authorize('create', FinancialRelease::class);

        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            abort(400, 'Usuário não possui uma finança ativa.');
        }

        // Verifica se o usuário tem permissão para criar (owner ou editor)
        // Usa o relacionamento já carregado para evitar query extra
        $pivot = $user->financeAccounts()
            ->where('finance_accounts.id', $currentFinanceAccount->id)
            ->first();

        if (!$pivot) {
            abort(403, 'Você não tem permissão para criar lançamentos.');
        }

        $userRole = $pivot->pivot->role;
        if (!in_array($userRole, ['owner', 'editor'])) {
            abort(403, 'Você não tem permissão para criar lançamentos. Apenas owners e editors podem criar.');
        }

        try {
            $data = $request->validated();
            $financialRelease = $this->service->createFinancialRelease($data);

            // Se retornou array (parcelas ou recorrências), retorna os dados completos incluindo portion
            if (is_array($financialRelease)) {
                return response()->json([
                    'message' => 'Lançamento(s) criado(s) com sucesso',
                    'count' => count($financialRelease),
                    'data' => $financialRelease // Inclui todos os dados: id, portion (ex: "1/10", "2/10"), value, date, due_date, etc.
                ], 201);
            }

            // Retorna o lançamento criado
            return response()->json([
                'message' => 'Lançamento criado com sucesso',
                'data' => $financialRelease
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar lançamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(FinancialRelease $financialRelease)
    {
        $this->authorize('view', $financialRelease);
        $financialRelease = $this->service->find($financialRelease);

        return response()->json($financialRelease, 200);
    }

    public function update(\App\Http\Requests\FinancialRelease\UpdateFinancialReleaseRequest $request, FinancialRelease $financialRelease)
    {
        $this->authorize('update', $financialRelease);
        $data = $request->validated();

        $financialRelease = $this->service->update($data, $financialRelease);

        return response()->json($financialRelease, 200);
    }

    public function destroy(FinancialRelease $financialRelease)
    {
        $this->authorize('delete', $financialRelease);

        // Verifica se a parcela pode ser deletada (apenas bloqueia se já estiver soft deleted)
        if (!$financialRelease->canBeDeleted()) {
            return response()->json([
                'message' => 'Esta parcela já foi excluída.'
            ], 400);
        }

        // Soft delete - permite excluir qualquer lançamento (pendente, pago, etc.)
        $financialRelease->delete();

        return response()->json([
            'message' => 'Lançamento excluído com sucesso.',
            'data' => $financialRelease
        ], 200);
    }

    /**
     * Cancela uma parcela específica
     * POST /financial_release/{id}/cancel
     */
    public function cancelSingle(FinancialRelease $financialRelease, CancelFinancialReleaseService $cancelService)
    {
        $this->authorize('cancel', FinancialRelease::class);

        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se o lançamento pertence à finança do usuário
        if ($financialRelease->finance_account_id !== $currentFinanceAccount->id) {
            return response()->json([
                'message' => 'Esta parcela não pertence à sua finança ativa.'
            ], 403);
        }

        // Verifica se o usuário tem permissão para cancelar (owner ou editor)
        $pivot = $user->financeAccounts()
            ->where('finance_accounts.id', $currentFinanceAccount->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'message' => 'Você não tem permissão para cancelar lançamentos.'
            ], 403);
        }

        $userRole = $pivot->pivot->role;
        if (!in_array($userRole, ['owner', 'editor'])) {
            return response()->json([
                'message' => 'Você não tem permissão para cancelar lançamentos. Apenas owners e editors podem cancelar.'
            ], 403);
        }

        try {
            $cancelledRelease = $cancelService->cancelSingleRelease(
                $financialRelease->id,
                $currentFinanceAccount->id
            );

            return response()->json([
                'message' => 'Parcela cancelada com sucesso.',
                'data' => $cancelledRelease
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cancelar parcela',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Retorna totais de receitas e despesas do mês
     */
    public function getTotals(Request $request)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $revenue = $this->service->getTotalRevenue($month, $year);
        $expense = $this->service->getTotalExpense($month, $year);
        $balance = $this->service->getMonthBalance($month, $year);

        return response()->json([
            'revenue' => (float) $revenue,
            'expense' => (float) $expense,
            'balance' => (float) $balance,
            'month' => $month,
            'year' => $year,
        ], 200);
    }

    /**
     * Retorna contas próximas do vencimento
     */
    public function getUpcomingDue(Request $request)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        $days = $request->input('days', 7);

        $filters = [];
        if ($request->has('month')) {
            $filters['month'] = $request->input('month');
        }
        if ($request->has('year')) {
            $filters['year'] = $request->input('year');
        }

        $releases = $this->service->getUpcomingDueReleases($days, $filters);

        return response()->json($releases, 200);
    }

    /**
     * Retorna últimos lançamentos
     */
    public function getLatest(Request $request)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        $limit = $request->input('limit', 100);

        $filters = [];
        if ($request->has('month')) {
            $filters['month'] = $request->input('month');
        }
        if ($request->has('year')) {
            $filters['year'] = $request->input('year');
        }

        $releases = $this->service->getLatestReleases($limit, $filters);

        return response()->json($releases, 200);
    }

    /**
     * Lista todos os parcelamentos (installments) agrupados
     * GET /api/financial_release/installments?month=1&year=2026
     */
    public function listInstallments(Request $request)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        $filters = [];

        // Filtro por mês/ano (data de competência)
        if ($request->has('month') && $request->has('year')) {
            $filters['month'] = $request->input('month');
            $filters['year'] = $request->input('year');
        }

        // Filtro por método de pagamento
        if ($request->filled('payment_method_id')) {
            $filters['payment_method_id'] = $request->input('payment_method_id');
        }

        $installments = $this->service->listInstallments($filters);

        return response()->json($installments, 200);
    }

    /**
     * Retorna detalhes de um parcelamento específico com todas as suas parcelas
     * GET /api/financial_release/installments/{installment_id}
     */
    public function getInstallmentDetails(int $installmentId)
    {
        $this->authorize('viewAny', FinancialRelease::class);

        try {
            $details = $this->service->getInstallmentDetails($installmentId);

            return response()->json($details, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar detalhes do parcelamento',
                'error' => $e->getMessage()
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Cancela uma ou múltiplas parcelas
     */
    public function cancel(CancelFinancialReleaseRequest $request, CancelFinancialReleaseService $cancelService)
    {
        // Verifica se o usuário pode cancelar lançamentos
        $this->authorize('cancel', FinancialRelease::class);

        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se o usuário tem permissão para cancelar (owner ou editor)
        $pivot = $user->financeAccounts()
            ->where('finance_accounts.id', $currentFinanceAccount->id)
            ->first();

        if (!$pivot) {
            return response()->json([
                'message' => 'Você não tem permissão para cancelar lançamentos.'
            ], 403);
        }

        $userRole = $pivot->pivot->role;
        if (!in_array($userRole, ['owner', 'editor'])) {
            return response()->json([
                'message' => 'Você não tem permissão para cancelar lançamentos. Apenas owners e editors podem cancelar.'
            ], 403);
        }

        try {
            $data = $request->validated();
            $releaseId = (int) $data['release_id'];
            $cancelAllFuture = isset($data['cancel_all_future']) && $data['cancel_all_future'] === true;

            // Se cancel_all_future = true, cancela todas as parcelas futuras do parcelamento
            if ($cancelAllFuture) {
                $cancelledReleases = $cancelService->cancelAllFutureReleases(
                    $releaseId,
                    $currentFinanceAccount->id
                );

                if ($cancelledReleases->isEmpty()) {
                    return response()->json([
                        'message' => 'Nenhuma parcela futura encontrada para cancelamento.',
                        'count' => 0,
                        'data' => []
                    ], 200);
                }

                return response()->json([
                    'message' => 'Todas as parcelas futuras foram canceladas com sucesso.',
                    'count' => $cancelledReleases->count(),
                    'data' => $cancelledReleases
                ], 200);
            }

            // Caso contrário, cancela apenas a parcela específica
            $cancelledRelease = $cancelService->cancelSingleRelease(
                $releaseId,
                $currentFinanceAccount->id
            );

            return response()->json([
                'message' => 'Parcela cancelada com sucesso.',
                'count' => 1,
                'data' => [$cancelledRelease]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cancelar parcela(s)',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    function teste (){
        $response = Http::get('https://servicodados.ibge.gov.br/api/v1/localidades/estados/33/distritos');

        dd($response);

    }






















}
