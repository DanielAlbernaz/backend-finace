<?php

namespace App\Policies;

use App\Models\FinancialRelease;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinancialReleasePolicy
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
        // Qualquer usuário autenticado pode ver seus lançamentos
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, FinancialRelease $financialRelease)
    {
        // Usuário deve pertencer à finança do lançamento
        return $user->financeAccounts()
            ->where('finance_accounts.id', $financialRelease->finance_account_id)
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
        // Apenas owner e editor podem criar lançamentos
        // A verificação da finança específica será feita no controller
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FinancialRelease $financialRelease)
    {
        // Usuário deve pertencer à finança e ter papel de owner ou editor
        $pivot = $user->financeAccounts()
            ->where('finance_accounts.id', $financialRelease->finance_account_id)
            ->first();

        if (!$pivot) {
            return false;
        }

        $role = $pivot->pivot->role;
        return in_array($role, ['owner', 'editor']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, FinancialRelease $financialRelease)
    {
        // Usuário deve pertencer à finança e ter papel de owner ou editor
        $pivot = $user->financeAccounts()
            ->where('finance_accounts.id', $financialRelease->finance_account_id)
            ->first();

        if (!$pivot) {
            return false;
        }

        $role = $pivot->pivot->role;
        return in_array($role, ['owner', 'editor']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, FinancialRelease $financialRelease)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FinancialRelease  $financialRelease
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, FinancialRelease $financialRelease)
    {
        //
    }

    /**
     * Determine whether the user can cancel financial releases.
     * Usado para cancelamento em massa (não requer instância específica)
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function cancel(User $user)
    {
        // Qualquer usuário autenticado pode tentar cancelar
        // A verificação específica de permissão (owner/editor) é feita no controller
        return true;
    }
}
