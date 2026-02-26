<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethod\CreatePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista todas as formas de pagamento disponíveis para a conta financeira
     * Retorna métodos padrão (globais) + métodos personalizados da conta
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Busca métodos disponíveis para a conta (padrão + personalizados)
        $query = PaymentMethod::availableForAccount($currentFinanceAccount->id);

        $paymentMethods = $query->orderBy('is_custom', 'asc') // Métodos padrão primeiro (is_custom = false)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($paymentMethods, 200);
    }

    /**
     * Exibe uma forma de pagamento específica
     */
    public function show(PaymentMethod $paymentMethod)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se o método está disponível para a conta
        // Se for personalizado (is_custom = true), deve pertencer à conta atual
        if ($paymentMethod->is_custom && $paymentMethod->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Forma de pagamento não encontrada ou sem permissão de acesso.');
        }

        return response()->json($paymentMethod, 200);
    }

    /**
     * Cria uma nova forma de pagamento personalizada para a conta financeira
     */
    public function store(CreatePaymentMethodRequest $request)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se o plano permite métodos de pagamento personalizados
        if (!$currentFinanceAccount->allowsCustomPaymentMethods()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        $validated = $request->validated();

        // Verifica se já existe um método com o mesmo nome para esta conta
        $existingMethod = PaymentMethod::where('finance_account_id', $currentFinanceAccount->id)
            ->where('name', $validated['name'])
            ->first();

        if ($existingMethod) {
            return response()->json([
                'message' => 'Você já possui uma forma de pagamento com este nome.'
            ], 422);
        }

        // Cria o método vinculado à conta financeira
        $paymentMethod = PaymentMethod::create([
            'name' => $validated['name'],
            'finance_account_id' => $currentFinanceAccount->id,
            'is_custom' => true, // Métodos criados pelo usuário são sempre customizados
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Forma de pagamento criada com sucesso',
            'data' => $paymentMethod
        ], 201);
    }

    /**
     * Atualiza uma forma de pagamento personalizada da conta
     */
    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite editar métodos padrão globais
        if ($paymentMethod->isDefault()) {
            abort(403, 'Não é possível editar métodos padrão do sistema.');
        }

        // Verifica se o plano permite métodos de pagamento personalizados
        if (!$currentFinanceAccount->allowsCustomPaymentMethods()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        // Verifica se o método pertence à conta atual
        if ($paymentMethod->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode editar formas de pagamento de outras contas.');
        }

        $validated = $request->validated();

        // Se está alterando o nome, verifica se não conflita com outro método da conta
        if (isset($validated['name']) && $validated['name'] !== $paymentMethod->name) {
            $existingMethod = PaymentMethod::where('finance_account_id', $currentFinanceAccount->id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $paymentMethod->id)
                ->first();

            if ($existingMethod) {
                return response()->json([
                    'message' => 'Você já possui uma forma de pagamento com este nome.'
                ], 422);
            }
        }

        $paymentMethod->update($validated);

        return response()->json([
            'message' => 'Forma de pagamento atualizada com sucesso',
            'data' => $paymentMethod
        ], 200);
    }

    /**
     * Desativa uma forma de pagamento (apenas altera is_active para false)
     */
    public function disable(PaymentMethod $paymentMethod)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite desativar métodos padrão globais
        if ($paymentMethod->isDefault()) {
            abort(403, 'Não é possível desativar métodos padrão do sistema.');
        }

        // Verifica se o método pertence à conta atual
        if ($paymentMethod->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode desativar formas de pagamento de outras contas.');
        }

        // Verifica se o método está sendo usado em algum lançamento
        if (!$paymentMethod->canBeDisabled()) {
            return response()->json([
                'message' => 'Não é possível desativar esta forma de pagamento pois ela está sendo usada em lançamentos financeiros.'
            ], 422);
        }

        $paymentMethod->update(['is_active' => false]);

        return response()->json([
            'message' => 'Forma de pagamento desativada com sucesso',
            'data' => $paymentMethod
        ], 200);
    }

    /**
     * Remove uma forma de pagamento personalizada (soft delete)
     * Só permite deletar se não houver lançamentos usando este método
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite deletar métodos padrão globais
        if ($paymentMethod->isDefault()) {
            abort(403, 'Não é possível deletar métodos padrão do sistema.');
        }

        // Verifica se o plano permite métodos de pagamento personalizados
        if (!$currentFinanceAccount->allowsCustomPaymentMethods()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        // Verifica se o método pertence à conta atual
        if ($paymentMethod->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode deletar formas de pagamento de outras contas.');
        }

        // Verifica se o método está sendo usado em algum lançamento
        if (!$paymentMethod->canBeDeleted()) {
            return response()->json([
                'message' => 'Não é possível deletar esta forma de pagamento pois ela está sendo usada em lançamentos financeiros. Use desativar (disable) em vez disso.'
            ], 422);
        }

        $paymentMethod->delete(); // Soft delete

        return response()->json([
            'message' => 'Forma de pagamento removida com sucesso'
        ], 200);
    }
}
