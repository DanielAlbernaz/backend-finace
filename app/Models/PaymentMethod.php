<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'finance_account_id',
        'name',
        'is_custom',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    /**
     * Relacionamento com a conta financeira (nullable para métodos padrão)
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
     * Verifica se é um método padrão do sistema (global)
     */
    public function isDefault(): bool
    {
        return !$this->is_custom;
    }

    /**
     * Scope para buscar métodos padrão (globais)
     */
    public function scopeDefault($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope para buscar métodos personalizados de uma conta
     */
    public function scopeCustom($query, $financeAccountId)
    {
        return $query->where('is_custom', true)
                     ->where('finance_account_id', $financeAccountId);
    }

    /**
     * Scope para buscar métodos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para buscar métodos disponíveis para uma conta financeira
     * Retorna métodos padrão (is_custom = false, apenas ativos) + métodos personalizados da conta (is_custom = true, ativos e inativos)
     * Exclui apenas métodos deletados (soft delete)
     */
    public function scopeAvailableForAccount($query, $financeAccountId)
    {
        return $query->where(function ($q) use ($financeAccountId) {
            // Métodos padrão: apenas ativos
            $q->where(function ($defaultQ) {
                $defaultQ->where('is_custom', false)
                         ->where('is_active', true);
            })
            // Métodos personalizados: ativos e inativos
            ->orWhere(function ($customQ) use ($financeAccountId) {
                $customQ->where('is_custom', true)
                        ->where('finance_account_id', $financeAccountId);
            });
        });
        // Soft delete é automaticamente aplicado pelo Eloquent quando SoftDeletes está habilitado
    }

    /**
     * Verifica se o método pode ser deletado
     * Não permite deletar se houver lançamentos usando este método
     */
    public function canBeDeleted(): bool
    {
        return $this->financialReleases()->count() === 0;
    }

    /**
     * Verifica se o método pode ser desativado
     * Não permite desativar se houver lançamentos usando este método
     */
    public function canBeDisabled(): bool
    {
        return $this->financialReleases()->count() === 0;
    }
}
