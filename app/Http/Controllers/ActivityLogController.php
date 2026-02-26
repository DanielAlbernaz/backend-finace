<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FinancialRelease;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    /**
     * Retorna os logs de atividade de um registro específico
     * GET /api/activity-logs/{type}/{id}
     * 
     * @param string $type Tipo do modelo (financial_release, category, etc)
     * @param int $id ID do registro
     * @return JsonResponse
     */
    public function getLogsByRecord(string $type, int $id): JsonResponse
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Mapeia o tipo para a classe do modelo
        $modelClass = $this->getModelClass($type);
        
        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo de registro inválido.'
            ], 400);
        }

        // Verifica se o registro existe e pertence à finança do usuário
        $record = $modelClass::find($id);
        
        if (!$record) {
            return response()->json([
                'message' => 'Registro não encontrado.'
            ], 404);
        }

        // Verifica se o registro pertence à finança do usuário
        if (isset($record->finance_account_id) && $record->finance_account_id !== $currentFinanceAccount->id) {
            return response()->json([
                'message' => 'Acesso negado.'
            ], 403);
        }

        // Busca os logs do registro
        $logs = ActivityLog::where('loggable_type', $modelClass)
            ->where('loggable_id', $id)
            ->where('finance_account_id', $currentFinanceAccount->id)
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Formata os logs para retornar
        $formattedLogs = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $this->getActionLabel($log->action),
                'user' => [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ],
                'ip_address' => $log->ip_address,
                'changes' => $log->changes,
                'description' => $log->description,
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_formatted' => $log->created_at->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json([
            'data' => $formattedLogs,
            'count' => $formattedLogs->count()
        ], 200);
    }

    /**
     * Retorna todos os logs de atividade da finança atual
     * GET /api/activity-logs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        $query = ActivityLog::where('finance_account_id', $currentFinanceAccount->id)
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc');

        // Filtro por ação
        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filtro por tipo de modelo
        if ($request->has('loggable_type')) {
            $modelClass = $this->getModelClass($request->input('loggable_type'));
            if ($modelClass) {
                $query->where('loggable_type', $modelClass);
            }
        }

        // Filtro por usuário
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filtro por data
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Paginação
        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));
        $logs = $query->paginate($perPage);

        // Formata os logs
        $formattedLogs = collect($logs->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $this->getActionLabel($log->action),
                'loggable_type' => $log->loggable_type,
                'loggable_id' => $log->loggable_id,
                'user' => [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ],
                'ip_address' => $log->ip_address,
                'changes' => $log->changes,
                'description' => $log->description,
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_formatted' => $log->created_at->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json([
            'data' => $formattedLogs,
            'current_page' => $logs->currentPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
            'last_page' => $logs->lastPage(),
        ], 200);
    }

    /**
     * Mapeia o tipo string para a classe do modelo
     * 
     * @param string $type
     * @return string|null
     */
    private function getModelClass(string $type): ?string
    {
        $mapping = [
            'financial_release' => FinancialRelease::class,
            // Adicione outros tipos aqui conforme necessário
            // 'category' => Category::class,
            // 'payment_method' => PaymentMethod::class,
        ];

        return $mapping[$type] ?? null;
    }

    /**
     * Retorna o label traduzido da ação
     * 
     * @param string $action
     * @return string
     */
    private function getActionLabel(string $action): string
    {
        $labels = [
            'created' => 'Cadastrado',
            'updated' => 'Alterado',
            'deleted' => 'Excluído',
        ];

        return $labels[$action] ?? $action;
    }
}
