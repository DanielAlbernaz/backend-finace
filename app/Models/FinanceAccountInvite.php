<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinanceAccountInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_account_id',
        'email',
        'role',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Relacionamento com a finança
     */
    public function financeAccount()
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    /**
     * Gera um token único para o convite
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Verifica se o convite está válido (não aceito e não expirado)
     */
    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
