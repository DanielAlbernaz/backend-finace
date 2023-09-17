<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialRelease\CreateFinancialReleaseRequest;
use App\Models\FinancialRelease;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FinancialReleaseController extends Controller
{
    public function index()
    {
        //
    }

    public function store(CreateFinancialReleaseRequest $request, FinancialRelease $financialRelease)
    {
        $data = $request->validated();

        if($data['repetition'] == 'installments'){
            $portion = 1;
            $totalPortion = count($data['installments']);
            $idInstallment = Installment::create(['type' => 'installment']);

            foreach($data['installments'] as $financialRelease){
                $data['date'] = $financialRelease['date'];
                $data['value'] = $financialRelease['value'];
                $data['portion'] = $portion . '/' . $totalPortion;
                $data['installment_id'] = $idInstallment->id;
                $portion++;

                $data = Arr::except($data, ['installments']);

                FinancialRelease::create($data);
            }
        } else {
            $financialRelease->create($data);
        }

        return response()->json('Lançamento criado com sucesso', 201);
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
