<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'plan_id',
        'subscription_status',
        'subscription_ends_at',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Relacionamento com usuários (many-to-many)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'finance_account_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relacionamento com o dono da finança
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relacionamento com lançamentos financeiros
     */
    public function financialReleases()
    {
        return $this->hasMany(FinancialRelease::class);
    }

    /**
     * Relacionamento com formas de pagamento personalizadas
     */
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Relacionamento com convites
     */
    public function invites()
    {
        return $this->hasMany(FinanceAccountInvite::class);
    }

    /**
     * Relacionamento com o plano
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Verifica se a finança tem uma feature específica
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false;
        }

        return $this->plan->hasFeature($feature);
    }

    /**
     * Verifica se a finança pode adicionar mais usuários
     */
    public function canAddMoreUsers(): bool
    {
        if (!$this->plan) {
            return false;
        }

        $currentUsersCount = $this->users()->count();
        return $currentUsersCount < $this->plan->max_users;
    }

    /**
     * Retorna quantos usuários ainda podem ser adicionados
     */
    public function remainingUserSlots(): int
    {
        if (!$this->plan) {
            return 0;
        }

        $currentUsersCount = $this->users()->count();
        return max(0, $this->plan->max_users - $currentUsersCount);
    }

    /**
     * Verifica se a assinatura está ativa
     */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === 'active'
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    /**
     * Verifica se a conta permite métodos de pagamento personalizados
     */
    public function allowsCustomPaymentMethods(): bool
    {
        return $this->hasFeature('custom_payment_methods');
    }

    /**
     * Verifica se a conta permite categorias personalizadas
     */
    public function allowsCustomCategories(): bool
    {
        return $this->hasFeature('custom_categories');
    }
}
