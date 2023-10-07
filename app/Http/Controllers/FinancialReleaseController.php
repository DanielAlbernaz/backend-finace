<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialRelease\CreateFinancialReleaseRequest;
use App\Models\FinancialRelease;
use App\Models\Installment;
use App\Services\FinancialRelease\FinancialReleaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FinancialReleaseController extends Controller
{
    protected $service;

    /**
     * Method __construct
     *
     * @param FinancialReleaseServiceInterface $service
     *
     * @return void
     */
    public function __construct(FinancialReleaseServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        //
    }

    public function store(CreateFinancialReleaseRequest $request, FinancialRelease $financialRelease)
    {
        $data = $request->validated();
        $this->service->createFinancialRelease($data);
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
