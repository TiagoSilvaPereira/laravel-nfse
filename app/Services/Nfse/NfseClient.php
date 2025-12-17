<?php

namespace App\Services\Nfse;

use App\Services\Nfse\Concerns\HasCompany;
use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Storage;

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
