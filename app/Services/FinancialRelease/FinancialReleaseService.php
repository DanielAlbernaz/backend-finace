<?php

namespace App\Services\FinancialRelease;

use App\Models\FinancialRelease;
use App\Repositories\FinancialRelease\FinancialReleaseRepository;
use App\Services\BaseService;
use App\Services\Installment\InstallmentServiceInterface;

class FinancialReleaseService extends BaseService implements FinancialReleaseServiceInterface
{
    private $installmentService;

    public function __construct(
        FinancialReleaseRepository $repository,
        InstallmentServiceInterface $installmentService
    ) {
        parent::__construct($repository);
        $this->installmentService = $installmentService;
    }

    public function createFinancialRelease(array $data)
    {
        $dataFinancialRelease = $this->installmentService->createInstallments($data);
        return FinancialRelease::insert($dataFinancialRelease);
    }
}
