<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialRelease\CreateFinancialReleaseRequest;
use App\Models\FinancialRelease;
use Illuminate\Http\Request;

class FinancialReleaseController extends Controller
{
    public function index()
    {
        //
    }

    public function store(CreateFinancialReleaseRequest $request, FinancialRelease $financialRelease)
    {
        // dd($request);
        $data = $request->validated();
        // dd($data);
        $financialRelease->create($data);
        // return $financialRelease;
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
