<?php

namespace App\Services\FinancialRelease;

use App\Services\BaseServiceInterface;

interface FinancialReleaseServiceInterface extends BaseServiceInterface
{
    public function createFinancialRelease(array $data);
}
