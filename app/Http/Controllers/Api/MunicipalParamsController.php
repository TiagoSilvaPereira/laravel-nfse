<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Nfse\NfseClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipalParamsController extends Controller
{
    public function __construct(
        protected NfseClient $nfseClient
    ) {}

    /**
     * Obtém os parâmetros do convênio de um município
     */
    public function getConvention(Request $request, string $cityCode): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalConventionParams($cityCode),
            'Falha ao consultar convênio municipal'
        );
    }

    /**
     * Obtém a alíquota de um serviço para uma competência específica
     */
    public function getAliquota(Request $request, string $cityCode, string $serviceCode, string $competencia): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalAliquota($cityCode, $serviceCode, $competencia),
            'Falha ao consultar alíquota municipal'
        );
    }

    /**
     * Obtém o histórico de alíquotas de um serviço
     */
    public function getHistoricoAliquotas(Request $request, string $cityCode, string $serviceCode): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalHistoricoAliquotas($cityCode, $serviceCode),
            'Falha ao consultar histórico de alíquotas'
        );
    }

    /**
     * Obtém um benefício municipal específico
     */
    public function getBeneficio(Request $request, string $cityCode, string $numeroBeneficio, string $competencia): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalBeneficio($cityCode, $numeroBeneficio, $competencia),
            'Falha ao consultar benefício municipal'
        );
    }

    /**
     * Obtém os regimes especiais de tributação de um serviço
     */
    public function getRegimesEspeciais(Request $request, string $cityCode, string $serviceCode, string $competencia): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalRegimesEspeciais($cityCode, $serviceCode, $competencia),
            'Falha ao consultar regimes especiais'
        );
    }

    /**
     * Obtém as retenções municipais para uma competência
     */
    public function getRetencoes(Request $request, string $cityCode, string $competencia): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        
        $this->nfseClient->setCompany($company);

        return $this->handleServiceCall(
            fn() => $this->nfseClient->getMunicipalRetencoes($cityCode, $competencia),
            'Falha ao consultar retenções municipais'
        );
    }

    /**
     * Helper para executar chamadas de serviço e tratar erros padronizados
     */
    protected function handleServiceCall(callable $callback, string $errorMessage): JsonResponse
    {
        $response = $callback();

        if (isset($response['_error'])) {
            $errorBody = $response['error_body'];
            $details = json_decode($errorBody, true);

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => $errorMessage,
                'details' => $details ?: $errorBody,
                'status_code' => $response['status_code']
            ], $response['status_code']);
        }

        return response()->json($response);
    }
}

