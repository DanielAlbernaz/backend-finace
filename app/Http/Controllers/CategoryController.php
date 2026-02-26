<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Lista todas as categorias disponíveis para a conta financeira
     * Retorna categorias padrão + categorias customizadas da conta
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
        
        $query = Category::availableForAccount($currentFinanceAccount->id);

        // Filtro opcional por tipo
        if ($request->has('type')) {
            $type = $request->input('type');
            // Normaliza valores do frontend
            if ($type === 'receita') {
                $type = 'revenue';
            } elseif ($type === 'despesa') {
                $type = 'expense';
            }
            $query->byType($type);
        }

        $categories = $query->orderBy('is_custom', 'asc') // Categorias padrão primeiro (is_custom = false)
            ->orderBy('title', 'asc')
            ->get();

        return response()->json($categories, 200);
    }

    /**
     * Exibe uma categoria específica
     */
    public function show(Category $category)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se a categoria está disponível para a conta
        // Se for personalizada (is_custom = true), deve pertencer à conta atual
        if ($category->is_custom && $category->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Categoria não encontrada ou sem permissão de acesso.');
        }

        return response()->json($category, 200);
    }

    /**
     * Cria uma nova categoria customizada para a conta financeira
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Verifica se o plano permite categorias personalizadas
        if (!$currentFinanceAccount->allowsCustomCategories()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['expense', 'revenue', 'despesa', 'receita'])],
        ]);

        // Normaliza valores do frontend
        if ($validated['type'] === 'receita') {
            $validated['type'] = 'revenue';
        } elseif ($validated['type'] === 'despesa') {
            $validated['type'] = 'expense';
        }

        // Verifica se já existe uma categoria com o mesmo nome e tipo para esta conta
        $existingCategory = Category::where('finance_account_id', $currentFinanceAccount->id)
            ->where('title', $validated['title'])
            ->where('type', $validated['type'])
            ->first();

        if ($existingCategory) {
            return response()->json([
                'message' => 'Você já possui uma categoria com este nome e tipo.'
            ], 422);
        }

        // Cria a categoria vinculada à conta financeira
        $category = Category::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'user_id' => $user->id,
            'finance_account_id' => $currentFinanceAccount->id,
            'is_custom' => true, // Categorias criadas pelo usuário são sempre customizadas
            'is_active' => true, // Por padrão, categorias são criadas como ativas
        ]);

        return response()->json([
            'message' => 'Categoria criada com sucesso',
            'data' => $category
        ], 201);
    }

    /**
     * Atualiza uma categoria customizada da conta
     */
    public function update(Request $request, Category $category)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite editar categorias padrão
        if ($category->isDefault()) {
            abort(403, 'Não é possível editar categorias padrão do sistema.');
        }

        // Verifica se o plano permite categorias personalizadas
        if (!$currentFinanceAccount->allowsCustomCategories()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        // Verifica se a categoria pertence à conta atual
        if ($category->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode editar categorias de outras contas.');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['expense', 'revenue', 'despesa', 'receita'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Normaliza valores do frontend
        if (isset($validated['type'])) {
            if ($validated['type'] === 'receita') {
                $validated['type'] = 'revenue';
            } elseif ($validated['type'] === 'despesa') {
                $validated['type'] = 'expense';
            }
        }

        // Se está alterando o nome ou tipo, verifica se não conflita com outra categoria da conta
        if ((isset($validated['title']) && $validated['title'] !== $category->title) ||
            (isset($validated['type']) && $validated['type'] !== $category->type)) {
            $existingCategory = Category::where('finance_account_id', $currentFinanceAccount->id)
                ->where('title', $validated['title'] ?? $category->title)
                ->where('type', $validated['type'] ?? $category->type)
                ->where('id', '!=', $category->id)
                ->first();

            if ($existingCategory) {
                return response()->json([
                    'message' => 'Você já possui uma categoria com este nome e tipo.'
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Categoria atualizada com sucesso',
            'data' => $category
        ], 200);
    }

    /**
     * Desativa uma categoria (apenas altera is_active para false)
     */
    public function disable(Category $category)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite desativar categorias padrão
        if ($category->isDefault()) {
            abort(403, 'Não é possível desativar categorias padrão do sistema.');
        }

        // Verifica se o plano permite categorias personalizadas
        if (!$currentFinanceAccount->allowsCustomCategories()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        // Verifica se a categoria pertence à conta atual
        if ($category->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode desativar categorias de outras contas.');
        }

        // Verifica se a categoria está sendo usada em algum lançamento
        if (!$category->canBeDisabled()) {
            return response()->json([
                'message' => 'Não é possível desativar esta categoria pois ela está sendo usada em lançamentos financeiros.'
            ], 422);
        }

        $category->update(['is_active' => false]);

        return response()->json([
            'message' => 'Categoria desativada com sucesso',
            'data' => $category
        ], 200);
    }

    /**
     * Remove uma categoria customizada da conta (soft delete)
     * Só permite deletar se não houver lançamentos usando esta categoria
     */
    public function destroy(Category $category)
    {
        $user = auth()->user();
        $currentFinanceAccount = $user->currentFinanceAccount();

        if (!$currentFinanceAccount) {
            return response()->json([
                'message' => 'Usuário não possui uma finança ativa.'
            ], 400);
        }

        // Não permite deletar categorias padrão
        if ($category->isDefault()) {
            abort(403, 'Não é possível deletar categorias padrão do sistema.');
        }

        // Verifica se o plano permite categorias personalizadas
        if (!$currentFinanceAccount->allowsCustomCategories()) {
            abort(403, 'Funcionalidade disponível apenas no plano Pro.');
        }

        // Verifica se a categoria pertence à conta atual
        if ($category->finance_account_id !== $currentFinanceAccount->id) {
            abort(403, 'Você não pode deletar categorias de outras contas.');
        }

        // Verifica se a categoria está sendo usada em algum lançamento
        if (!$category->canBeDeleted()) {
            return response()->json([
                'message' => 'Não é possível deletar esta categoria pois ela está sendo usada em lançamentos financeiros. Use desativar (disable) em vez disso.'
            ], 422);
        }

        $category->delete(); // Soft delete

        return response()->json([
            'message' => 'Categoria removida com sucesso'
        ], 200);
    }
}
