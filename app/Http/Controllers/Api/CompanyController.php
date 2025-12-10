<?php

namespace App\Http\Controllers\Api;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::all();

        return response()->json($companies);
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): JsonResponse
    {
        $company = $action->handle(
            $request->validated(),
            $request->file('certificate')
        );

        return response()->json($company, 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): JsonResponse
    {
        $company = $action->handle(
            $company,
            $request->validated(),
            $request->file('certificate')
        );

        return response()->json($company);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();
        
        return response()->json(null, 204);
    }
}
