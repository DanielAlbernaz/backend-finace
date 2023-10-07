<?php

namespace App\Repositories\FinancialRelease;

use App\Models\FinancialRelease;
use Prettus\Repository\Eloquent\BaseRepository;

class FinancialReleaseRepositoryEloquent extends BaseRepository implements FinancialReleaseRepository
{
    public function model(): string
    {
        return FinancialRelease::class;
    }
}
