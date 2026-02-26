<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceAccountInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceAccountInviteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Cria um novo convite para uma finança
     */
    public function store(Request $request, FinanceAccount $financeAccount)
    {
        $this->authorize('invite', $financeAccount);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', Rule::in(['owner', 'editor', 'viewer'])],
        ]);

        // Verifica se a finança pode adicionar mais usuários (limite do plano)
        if (!$financeAccount->canAddMoreUsers()) {
            return response()->json([
                'message' => 'Limite de usuários do plano atingido. Atualize seu plano para adicionar mais usuários.',
                'current_users' => $financeAccount->users()->count(),
                'max_users' => $financeAccount->plan->max_users ?? 0,
            ], 403);
        }

        // Verifica se o usuário já está na finança
        $existingUser = \App\Models\User::where('email', $validated['email'])->first();
        if ($existingUser) {
            $isMember = $financeAccount->users()
                ->where('users.id', $existingUser->id)
                ->exists();
            
            if ($isMember) {
                return response()->json(['message' => 'Usuário já é membro desta finança.'], 400);
            }
        }

        // Verifica se já existe um convite pendente para este email
        $existingInvite = FinanceAccountInvite::where('finance_account_id', $financeAccount->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvite) {
            return response()->json(['message' => 'Já existe um convite pendente para este email.'], 400);
        }

        // Cria o convite
        $invite = FinanceAccountInvite::create([
            'finance_account_id' => $financeAccount->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => FinanceAccountInvite::generateToken(),
            'expires_at' => now()->addDays(7), // Convite expira em 7 dias
        ]);

        // Retorna o link do convite
        $inviteLink = url('/register?invite=' . $invite->token);

        return response()->json([
            'message' => 'Convite criado com sucesso.',
            'invite' => $invite,
            'link' => $inviteLink,
        ], 201);
    }

    /**
     * Lista os convites de uma finança
     */
    public function index(FinanceAccount $financeAccount)
    {
        $this->authorize('view', $financeAccount);

        $invites = FinanceAccountInvite::where('finance_account_id', $financeAccount->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invites);
    }

    /**
     * Remove um convite
     */
    public function destroy(FinanceAccount $financeAccount, FinanceAccountInvite $invite)
    {
        $this->authorize('invite', $financeAccount);

        // Verifica se o convite pertence à finança
        if ($invite->finance_account_id !== $financeAccount->id) {
            abort(404, 'Convite não encontrado.');
        }

        $invite->delete();

        return response()->json(['message' => 'Convite removido com sucesso.'], 200);
    }
}
