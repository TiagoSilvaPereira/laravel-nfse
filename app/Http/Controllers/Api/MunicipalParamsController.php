<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Nfse\NfseClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        try {
            $params = $this->nfseClient->getMunicipalConventionParams($cityCode);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar convênio municipal',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
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

        try {
            $params = $this->nfseClient->getMunicipalAliquota($cityCode, $serviceCode, $competencia);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar alíquota municipal',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
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

        try {
            $params = $this->nfseClient->getMunicipalHistoricoAliquotas($cityCode, $serviceCode);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar histórico de alíquotas',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
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

        try {
            $params = $this->nfseClient->getMunicipalBeneficio($cityCode, $numeroBeneficio, $competencia);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar benefício municipal',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
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

        try {
            $params = $this->nfseClient->getMunicipalRegimesEspeciais($cityCode, $serviceCode, $competencia);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar regimes especiais',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
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

        try {
            $params = $this->nfseClient->getMunicipalRetencoes($cityCode, $competencia);
            return response()->json($params);
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Erro interno do servidor';

            return response()->json([
                'error' => 'Erro na API ADN',
                'message' => 'Falha ao consultar retenções municipais',
                'details' => json_decode($errorBody, true) ?: $errorBody,
                'status_code' => $statusCode
            ], $statusCode);
        }
    }
}
