<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'max_users',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_users' => 'integer',
        'features' => 'array',
    ];

    /**
     * Relacionamento com finanças
     */
    public function financeAccounts()
    {
        return $this->hasMany(FinanceAccount::class);
    }

    /**
     * Verifica se o plano tem uma feature específica
     */
    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]) && $this->features[$feature] === true;
    }
}
