<?php

namespace App\Http\Controllers;

use App\Http\Requests\revenue\CreateRevenueRequest;
use App\Models\Revenue;
use Illuminate\Http\Request;

class RevenueController extends Controller
{

    public function index()
    {
        //
    }

    // public function store(CreateRevenueRequest $request, Revenue $revenue)
    // {
    //     $data = $request->validated();
    //     $revenue->create($data);
    //     return $revenue;
    //     // dd($data);
    // }

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
