<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relacionamento com finanças (many-to-many)
     */
    public function financeAccounts()
    {
        return $this->belongsToMany(FinanceAccount::class, 'finance_account_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Finanças onde o usuário é owner
     */
    public function ownedFinanceAccounts()
    {
        return $this->hasMany(FinanceAccount::class, 'owner_id');
    }

    /**
     * Lançamentos financeiros criados pelo usuário (auditoria)
     */
    public function createdFinancialReleases()
    {
        return $this->hasMany(FinancialRelease::class, 'created_by');
    }

    /**
     * Obtém a finança atual do usuário (primeira finança ou a principal)
     * Por enquanto retorna a primeira, mas pode ser evoluído para ter uma finança "ativa"
     */
    public function currentFinanceAccount()
    {
        return $this->financeAccounts()->first();
    }

    /**
     * Verifica se o usuário tem um papel específico em uma finança
     */
    public function hasRoleInFinance(FinanceAccount $financeAccount, string $role): bool
    {
        $pivot = $this->financeAccounts()
            ->where('finance_accounts.id', $financeAccount->id)
            ->first();

        if (!$pivot) {
            return false;
        }

        return $pivot->pivot->role === $role;
    }

    /**
     * Verifica se o usuário é owner de uma finança
     */
    public function isOwnerOfFinance(FinanceAccount $financeAccount): bool
    {
        return $financeAccount->owner_id === $this->id;
    }
}
