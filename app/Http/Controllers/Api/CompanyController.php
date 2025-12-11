<?php

namespace App\Http\Controllers\Api;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(): JsonResource
    {
        $companies = Company::all();

        return CompanyResource::collection($companies);
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): JsonResource
    {
        $company = $action->handle(
            $request->validated(),
            $request->file('certificate')
        );

        return new CompanyResource($company->refresh());
    }

    public function show(Company $company): JsonResource
    {
        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): JsonResource
    {
        $company = $action->handle(
            $company,
            $request->validated(),
            $request->file('certificate')
        );

        return new CompanyResource($company);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(null, 204);
    }
}
