<?php

namespace App\Repositories\Installment;

use App\Models\Installment;
use Prettus\Repository\Eloquent\BaseRepository;

class InstallmentRepositoryEloquent extends BaseRepository implements InstallmentRepository
{
    public function model(): string
    {
        return Installment::class;
    }
}
