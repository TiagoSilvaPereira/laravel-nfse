<?php

namespace App\Services\Nfse;

use App\Services\Nfse\Concerns\HasCompany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class MunicipalParamsService
{
    use HasCompany;

    private NfseClient $client;

    public function __construct()
    {
        $this->client = new NfseClient();
    }

    /**
     * Formata código de serviço de 010101 para 01.01.01.000,
     * que é o formato exigido pela API do ADN.
     */
    public function formatServiceCode(string $code): string
    {
        $code = preg_replace('/\D/', '', $code);
        
        $code = str_pad($code, 6, '0', STR_PAD_RIGHT);
        
        $code = str_pad($code, 9, '0', STR_PAD_RIGHT);
        
        return substr($code, 0, 2) . '.' . 
               substr($code, 2, 2) . '.' . 
               substr($code, 4, 2) . '.' . 
               substr($code, 6, 3);
    }

    /**
     * Obtém alíquota do serviço com cache de 24h
     */
    public function getServiceAliquota(string $cityCode, string $serviceCode, ?string $competencia = null): ?array
    {
        $this->client->setCompany($this->company);
        
        $formattedCode = $this->formatServiceCode($serviceCode);
        $competencia = $competencia ?? now()->format('Y-m-d');
        
        $cacheKey = "nfse.aliquota.{$cityCode}.{$formattedCode}.{$competencia}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($cityCode, $formattedCode, $competencia) {
            try {
                $response = $this->client->getMunicipalAliquota($cityCode, $formattedCode, $competencia);

                if (isset($response['aliquotas']) && !is_null($response['aliquotas'])) {
                    $aliquotas = $response['aliquotas'];
                    $firstKey = array_key_first($aliquotas);
                    
                    if ($firstKey && isset($aliquotas[$firstKey][0])) {
                        return $aliquotas[$firstKey][0];
                    }
                }
                
                return null;
            } catch (Exception $e) {
                Log::warning('Erro ao consultar alíquota municipal', [
                    'city' => $cityCode,
                    'service' => $formattedCode,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Obtém parâmetros de retenção com cache de 24h
     */
    public function getRetentionParams(string $cityCode, ?string $competencia = null): ?array
    {
        $this->client->setCompany($this->company);
        
        $competencia = $competencia ?? now()->format('Y-m-d');
        $cacheKey = "nfse.retencoes.{$cityCode}.{$competencia}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($cityCode, $competencia) {
            try {
                $response = $this->client->getMunicipalRetencoes($cityCode, $competencia);
                
                if (isset($response['retencoes'])) {
                    return $response['retencoes'];
                }
                
                return null;
            } catch (Exception $e) {
                Log::warning('Erro ao consultar retenções municipais', [
                    'city' => $cityCode,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Obtém parâmetros do convênio municipal com cache de 24h
     */
    public function getConventionParams(string $cityCode): ?array
    {
        $this->client->setCompany($this->company);
        
        $cacheKey = "nfse.convenio.{$cityCode}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($cityCode) {
            try {
                $response = $this->client->getMunicipalConventionParams($cityCode);
                
                if (isset($response['parametrosConvenio'])) {
                    return $response['parametrosConvenio'];
                }
                
                return null;
            } catch (Exception $e) {
                Log::warning('Erro ao consultar convênio municipal', [
                    'city' => $cityCode,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Verifica se o município aderiu ao ambiente nacional
     */
    public function isCityAderent(string $cityCode): bool
    {
        $params = $this->getConventionParams($cityCode);
        return $params && ($params['aderenteAmbienteNacional'] ?? 0) === 1;
    }

    /**
     * Verifica se há retenção de ISSQN pelo artigo 6º
     */
    public function hasArticle6Retention(string $cityCode, ?string $competencia = null): bool
    {
        $retentions = $this->getRetentionParams($cityCode, $competencia);
        return $retentions && ($retentions['artigoSexto']['habilitado'] ?? false);
    }
}
