<?php

namespace App\Policies;

use App\Models\FinanceAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinanceAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Qualquer usuário autenticado pode ver suas finanças
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FinanceAccount $financeAccount)
    {
        // Usuário deve pertencer à finança
        return $user->financeAccounts()
            ->where('finance_accounts.id', $financeAccount->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Qualquer usuário pode criar uma finança (no registro)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FinanceAccount $financeAccount)
    {
        // Apenas owner pode atualizar
        return $user->isOwnerOfFinance($financeAccount);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FinanceAccount $financeAccount)
    {
        // Apenas owner pode deletar
        return $user->isOwnerOfFinance($financeAccount);
    }

    /**
     * Determine whether the user can invite users.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function invite(User $user, FinanceAccount $financeAccount)
    {
        // Apenas owner pode convidar
        return $user->isOwnerOfFinance($financeAccount);
    }

    /**
     * Determine whether the user can remove users.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function removeUser(User $user, FinanceAccount $financeAccount)
    {
        // Apenas owner pode remover usuários
        return $user->isOwnerOfFinance($financeAccount);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FinanceAccount $financeAccount)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinanceAccount  $financeAccount
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FinanceAccount $financeAccount)
    {
        //
    }
}
