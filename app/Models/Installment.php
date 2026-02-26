<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    /**
     * Relacionamento com os lançamentos financeiros
     */
    public function financialReleases()
    {
        return $this->hasMany(FinancialRelease::class, 'installment_id');
    }
}
