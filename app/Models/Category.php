<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'user_id',
        'finance_account_id',
        'is_custom',
        'is_active',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com o usuário (nullable para categorias padrão)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com a conta financeira (nullable para categorias padrão)
     */
    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    /**
     * Relacionamento com lançamentos financeiros
     */
    public function financialReleases()
    {
        return $this->hasMany(FinancialRelease::class);
    }

    /**
     * Verifica se é uma categoria padrão (global)
     */
    public function isDefault(): bool
    {
        return !$this->is_custom;
    }

    /**
     * Scope para buscar categorias padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope para buscar categorias customizadas de uma conta financeira
     */
    public function scopeCustom($query, $financeAccountId)
    {
        return $query->where('is_custom', true)
                     ->where('finance_account_id', $financeAccountId);
    }

    /**
     * Scope para buscar categorias por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para buscar categorias disponíveis para uma conta financeira
     * Retorna categorias padrão (is_custom = false, apenas ativas) + categorias personalizadas da conta (is_custom = true, ativas e inativas)
     * Exclui apenas categorias deletadas (soft delete)
     */
    public function scopeAvailableForAccount($query, $financeAccountId)
    {
        return $query->where(function ($q) use ($financeAccountId) {
            // Categorias padrão: apenas ativas
            $q->where(function ($defaultQ) {
                $defaultQ->where('is_custom', false)
                         ->where('is_active', true);
            })
            // Categorias personalizadas: ativas e inativas
            ->orWhere(function ($customQ) use ($financeAccountId) {
                $customQ->where('is_custom', true)
                        ->where('finance_account_id', $financeAccountId);
            });
        });
        // Soft delete é automaticamente aplicado pelo Eloquent quando SoftDeletes está habilitado
    }

    /**
     * Scope para buscar categorias disponíveis para um usuário (mantido para compatibilidade)
     * @deprecated Use scopeAvailableForAccount em vez disso
     */
    public function scopeAvailableForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_custom', false)
              ->orWhere(function ($subQ) use ($userId) {
                  $subQ->where('is_custom', true)
                       ->where('user_id', $userId);
              });
        });
    }

    /**
     * Scope para buscar categorias ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Verifica se a categoria pode ser deletada
     * Não permite deletar se houver lançamentos usando esta categoria
     */
    public function canBeDeleted(): bool
    {
        return $this->financialReleases()->count() === 0;
    }

    /**
     * Verifica se a categoria pode ser desativada
     * Não permite desativar se houver lançamentos usando esta categoria
     */
    public function canBeDisabled(): bool
    {
        return $this->financialReleases()->count() === 0;
    }
}
