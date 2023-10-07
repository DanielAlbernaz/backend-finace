<?php

namespace App\Services\Installment;

use App\Services\BaseServiceInterface;

interface InstallmentServiceInterface extends BaseServiceInterface
{
    public function createInstallments(array $data): Array;
}
