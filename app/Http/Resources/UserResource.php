<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $currentFinanceAccount = $this->currentFinanceAccount();
        $role = null;
        $financeAccountData = null;

        if ($currentFinanceAccount) {
            // Obtém o role do usuário na finança atual
            $pivot = $this->financeAccounts()
                ->where('finance_accounts.id', $currentFinanceAccount->id)
                ->first();
            
            if ($pivot) {
                $role = $pivot->pivot->role;
            }

            // Prepara os dados da finança
            $financeAccountData = [
                'id' => $currentFinanceAccount->id,
                'name' => $currentFinanceAccount->name,
                'plan' => $currentFinanceAccount->plan ? [
                    'id' => $currentFinanceAccount->plan->id,
                    'name' => $currentFinanceAccount->plan->name,
                    'price' => $currentFinanceAccount->plan->price,
                    'max_users' => $currentFinanceAccount->plan->max_users,
                    'features' => $currentFinanceAccount->plan->features,
                ] : null,
                'subscription_status' => $currentFinanceAccount->subscription_status,
                'subscription_ends_at' => $currentFinanceAccount->subscription_ends_at,
                'can_add_more_users' => $currentFinanceAccount->canAddMoreUsers(),
                'remaining_user_slots' => $currentFinanceAccount->remainingUserSlots(),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'role' => $role,
            'financeAccount' => $financeAccountData,
        ];
    }
}
