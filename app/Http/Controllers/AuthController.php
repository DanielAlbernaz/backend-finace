<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\FinanceAccount;
use App\Models\FinanceAccountInvite;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum'], ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $emailUser = '';
        $user = User::where(['email' => $credentials['email']])->first();
        if(!isset($user->email)){
            abort(401, 'Email não cadastrado.');
        }

        if(!Hash::check($request->password, $user->password)){
            abort(401, 'Senha Inválida');
        }

        return response()->json(['token' => $user->createToken($user->email)->plainTextToken]);
    }

    public function register(UserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        $inviteToken = $request->input('invite_token');

        return DB::transaction(function () use ($data, $inviteToken, $user) {
            // Cria o usuário
            if (!$user = $user->create($data)) {
                abort(500, 'Erro ao criar novo usuário...');
            }

            if ($inviteToken) {
                // Fluxo: entrar em finança existente via convite
                $invite = FinanceAccountInvite::where('token', $inviteToken)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->first();

                if (!$invite) {
                    abort(400, 'Convite inválido ou expirado.');
                }

                // Verifica se o email do convite corresponde ao email do cadastro
                if ($invite->email !== $data['email']) {
                    abort(400, 'O email do convite não corresponde ao email informado.');
                }

                // Verifica se a finança ainda pode adicionar mais usuários
                if (!$invite->financeAccount->canAddMoreUsers()) {
                    abort(403, 'Limite de usuários do plano atingido. Entre em contato com o administrador da finança.');
                }

                // Associa o usuário à finança com o papel definido no convite
                $invite->financeAccount->users()->attach($user->id, [
                    'role' => $invite->role
                ]);

                // Marca o convite como aceito
                $invite->update(['accepted_at' => now()]);
            } else {
                // Fluxo: criar nova finança própria
                // Busca o plano Free
                $freePlan = Plan::where('name', 'Free')->first();

                if (!$freePlan) {
                    abort(500, 'Plano Free não encontrado. Execute o seeder de planos.');
                }

                $financeAccount = FinanceAccount::create([
                    'name' => 'Finança de ' . $user->name,
                    'owner_id' => $user->id,
                    'plan_id' => $freePlan->id,
                    'subscription_status' => 'active',
                ]);

                // Associa o usuário como owner
                $financeAccount->users()->attach($user->id, [
                    'role' => 'owner'
                ]);
            }

            return response()->json(['user' => $user], 201);
        });
    }

    public function me()
    {
        $user = auth()->user();

        // Carrega os relacionamentos necessários
        $user->load([
            'financeAccounts.plan',
            'financeAccounts' => function ($query) {
                $query->orderBy('created_at', 'asc'); // Primeira finança criada
            }
        ]);

        return response()->json(new UserResource($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json('Deslogado');
    }
}
