<?php

namespace App\Services\Nfse;

use App\Services\Nfse\Concerns\HasCompany;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NfseClient
{
    use HasCompany;

    public function postToSefin(array $payload): array
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getSefinBaseUrl();
        $url = rtrim($baseUrl, '/') . '/nfse';
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->post($url, [
                'cert' => $certPath,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    public function getDanfsePdf(string $accessKey): string
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getAdnBaseUrl();
        $url = rtrim($baseUrl, '/') . '/danfse/' . $accessKey;
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->get($url, [
                'cert' => $certPath,
                'headers' => [
                    'Accept' => 'application/pdf',
                ],
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
            ]);

            return $response->getBody()->getContents();
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    public function checkDpsExists(string $dpsId): bool
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getSefinBaseUrl();
        $url = rtrim($baseUrl, '/') . '/dps/' . $dpsId;
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->head($url, [
                'cert' => $certPath,
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
                'http_errors' => false,
            ]);

            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    public function getDpsInfo(string $dpsId): array
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getSefinBaseUrl();
        $url = rtrim($baseUrl, '/') . '/dps/' . $dpsId;
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->get($url, [
                'cert' => $certPath,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    public function getNfseByAccessKey(string $accessKey): array
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getSefinBaseUrl();
        $url = rtrim($baseUrl, '/') . '/nfse/' . $accessKey;
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->get($url, [
                'cert' => $certPath,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    public function getMunicipalConventionParams(string $cityCode): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/convenio');
    }

    public function getMunicipalAliquota(string $cityCode, string $serviceCode, string $competencia): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/' . $serviceCode . '/' . $competencia . '/aliquota');
    }

    public function getMunicipalHistoricoAliquotas(string $cityCode, string $serviceCode): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/' . $serviceCode . '/historicoaliquotas');
    }

    public function getMunicipalBeneficio(string $cityCode, string $numeroBeneficio, string $competencia): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/' . $numeroBeneficio . '/' . $competencia . '/beneficio');
    }

    public function getMunicipalRetencoes(string $cityCode, string $competencia): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/' . $competencia . '/retencoes');
    }

    public function getMunicipalRegimesEspeciais(string $cityCode, string $serviceCode, string $competencia): array
    {
        return $this->sendAdnRequest('/parametrizacao/' . $cityCode . '/' . $serviceCode . '/' . $competencia . '/regimes_especiais');
    }

    protected function sendAdnRequest(string $endpoint): array
    {
        $this->ensureCompanyIsSet();
        
        $baseUrl = $this->getAdnBaseUrl();
        $url = rtrim($baseUrl, '/') . $endpoint;
        
        $certPath = $this->getCertificateFromCompany();

        try {
            $client = new Client();

            $response = $client->get($url, [
                'cert' => $certPath,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'curl' => [
                    CURLOPT_SSLVERSION => 6, // TLS 1.2
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            
            Log::error('Erro na API ADN - ' . $endpoint, [
                'url' => $url,
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'company_id' => $this->company->id ?? null,
            ]);

            return [
                '_error' => true,
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'message' => 'Erro na API ADN'
            ];
        } finally {
            $this->cleanupTempCert($certPath);
        }
    }

    protected function ensureCompanyIsSet(): void
    {
        if (!isset($this->company)) {
            throw new Exception("Company não foi definida. Use setCompany() antes de chamar este método.");
        }
    }

    protected function getSefinBaseUrl(): string
    {
        return $this->company->isProduction()
            ? config('services.nfse.sefin_url')
            : config('services.nfse.sefin_url_homologation');
    }

    protected function getAdnBaseUrl(): string
    {
        return $this->company->isProduction()
            ? config('services.nfse.adn_url')
            : config('services.nfse.adn_url_homologation');
    }

    protected function getCertificateFromCompany(): string
    {
        $this->ensureCompanyIsSet();

        if (!Storage::exists($this->company->cert_path)) {
            throw new Exception("Certificado PFX não encontrado.");
        }

        $pfxContent = Storage::get($this->company->cert_path);
        $password = $this->company->cert_password;

        return $this->convertPfxToPem($pfxContent, $password, $this->company->id);
    }

    public function convertPfxToPem(string $pfxContent, string $password, int|string $companyId): string
    {
        $certs = [];
        
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new Exception("Falha ao ler PFX para conversão.");
        }

        $pemContent = $certs['cert'] . "\n" . $certs['pkey'];

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cert_' . $companyId . '_' . uniqid() . '.pem';
        file_put_contents($tempPath, $pemContent);

        return $tempPath;
    }

    public function cleanupTempCert(string $certPath): void
    {
        if (file_exists($certPath) && str_contains($certPath, 'temp')) {
            unlink($certPath);
        }
    }
}
