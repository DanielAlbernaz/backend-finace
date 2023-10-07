<?php

namespace App\Services\Installment;

use App\Helpers\DateParser;
use App\Models\Installment;
use App\Repositories\Installment\InstallmentRepository;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class InstallmentService extends BaseService implements InstallmentServiceInterface
{
    public function __construct(
        InstallmentRepository $repository
    ) {
        parent::__construct($repository);
    }

    public function createInstallments(array $data): Array
    {
        if ($data['repetition'] != 'only') {
            return $this->makeInstallments($data);
        }

        $data['created_at'] = Carbon::now()->toDateTimeString();
        return $data;
    }

    public function makeInstallments(array $data): Array
    {
        $portion = $data['number_repetition'];
        $idInstallment = Installment::create(['type' => $data['repetition']]);

        for($i = 0; $i < $portion; $i++){
            $dataFinancialRelease[$i]['date'] = $this->getDateInstallments($data, $i);
            $dataFinancialRelease[$i]['portion'] = $data['repetition'] == 'installments' ? $i+1 . '/' . $data['number_repetition'] : null;
            $dataFinancialRelease[$i]['payment_date'] = Arr::exists($data, 'payment_date') ? $data['payment_date'] : null;
            $dataFinancialRelease[$i]['value'] = $data['value'];
            $dataFinancialRelease[$i]['installment_id'] = $idInstallment->id;
            $dataFinancialRelease[$i]['type'] = $data['type'];
            $dataFinancialRelease[$i]['descrition'] = $data['descrition'];
            $dataFinancialRelease[$i]['observation'] = $data['observation'];
            $dataFinancialRelease[$i]['category_id'] = $data['category_id'];
            $dataFinancialRelease[$i]['user_id'] = $data['user_id'];
            $dataFinancialRelease[$i]['repetition'] = $data['repetition'];
            $dataFinancialRelease[$i]['created_at'] = Carbon::now()->toDateTimeString();
        }
        return $dataFinancialRelease;
    }

    public function getDateInstallments(array $data, int $portion): String
    {
        $data['portion'] = $portion;

        return DateParser::parseDateByPeriodicity($data);
    }

}
