<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialRelease extends Model
{
    use HasFactory, SoftDeletes, Loggable;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    // Ou use fillable em vez de guarded para ter mais controle
    // protected $fillable = ['type', 'value', 'date', 'payment_date', 'descrition', 'observation', 'repetition', 'portion', 'category_id', 'finance_account_id', 'created_by', 'installment_id', 'status'];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    /**
     * Accessor para identificar o tipo de lançamento
     * Útil para o frontend diferenciar parcelamento de recorrência
     */
    protected $appends = ['release_type'];

    /**
     * Retorna o tipo de lançamento de forma mais clara
     * 'installment' = parcelamento (tem installment_id)
     * 'recurring' = recorrência (repetition = fixed, sem installment_id)
     * 'single' = lançamento único (repetition = only)
     */
    public function getReleaseTypeAttribute(): string
    {
        if ($this->repetition === 'installments' && $this->installment_id !== null) {
            return 'installment'; // Parcelamento
        }

        if ($this->repetition === 'fixed') {
            return 'recurring'; // Recorrência
        }

        return 'single'; // Lançamento único
    }

    /**
     * Relacionamento com a finança
     */
    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    /**
     * Relacionamento com o usuário que criou (auditoria)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacionamento com a categoria
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relacionamento com a forma de pagamento
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Calcula o status do lançamento baseado em date e payment_date
     *
     * @return string
     */
    public function calculateStatus(): string
    {
        // Se payment_date estiver preenchido, está pago
        if ($this->payment_date !== null) {
            return 'paid';
        }

        // Se payment_date estiver vazio e date < hoje, está vencido
        if ($this->date < now()->startOfDay()) {
            return 'overdue';
        }

        // Se payment_date estiver vazio e date >= hoje, está pendente
        return 'pending';
    }

    /**
     * Atualiza o status automaticamente
     * NÃO recalcula se o status for 'cancelled' (preserva cancelamento manual)
     */
    public function updateStatus(): void
    {
        // Se já estiver cancelado, não recalcula automaticamente
        if ($this->status === 'cancelled') {
            return;
        }

        $this->status = $this->calculateStatus();
    }

    /**
     * Verifica se a parcela pode ser cancelada
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        // Não pode cancelar se já estiver paga
        if ($this->payment_date !== null) {
            return false;
        }

        // Não pode cancelar se já estiver cancelada
        if ($this->status === 'cancelled') {
            return false;
        }

        // Não pode cancelar se já estiver deletada
        if ($this->trashed()) {
            return false;
        }

        // Só pode cancelar se estiver pendente
        return $this->status === 'pending';
    }

    /**
     * Verifica se a parcela pode ser deletada (soft delete)
     * Permite exclusão de qualquer lançamento, incluindo pagos (soft delete mantém histórico)
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        // Não pode deletar se já estiver deletada (soft deleted)
        if ($this->trashed()) {
            return false;
        }

        // Permite deletar qualquer lançamento (pendente, pago, vencido ou cancelado)
        return true;
    }
}
