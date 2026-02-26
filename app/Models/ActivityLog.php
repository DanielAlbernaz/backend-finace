<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'finance_account_id',
        'loggable_type',
        'loggable_id',
        'action',
        'ip_address',
        'changes',
        'description',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário que realizou a ação
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com a finança
     */
    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    /**
     * Relacionamento polimórfico com o modelo que gerou o log
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para filtrar por ação
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para filtrar por finance account
     */
    public function scopeForFinanceAccount($query, int $financeAccountId)
    {
        return $query->where('finance_account_id', $financeAccountId);
    }

    /**
     * Scope para filtrar por registro específico
     */
    public function scopeForLoggable($query, string $type, int $id)
    {
        return $query->where('loggable_type', $type)
            ->where('loggable_id', $id);
    }
}
