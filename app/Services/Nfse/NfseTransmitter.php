<?php

namespace App\Services\Nfse;

use App\Models\Company;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Exception;

class NfseTransmitter
{
    public function transmit(string $signedXml, Company $company): array
    {
        $baseUrl = $this->calculateBaseUrl($company);

        $gzipped = gzencode($signedXml);
        $base64 = base64_encode($gzipped);

        $payload = [
            'nfseXmlGZipB64' => $base64
        ];

        $certPath = $this->getCertificatePemPath($company);

        $client = new Client([
            'base_uri' => $baseUrl,
            'cert' => $certPath,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            $response = $client->post('nfse', [
                'json' => $payload
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body;

        } catch (\Exception $e) {
            throw new Exception("Erro na transmissão da NFS-e: " . $e->getMessage());
        } finally {
            if (file_exists($certPath) && str_contains($certPath, 'temp')) {
                unlink($certPath);
            }
        }
    }

    protected function calculateBaseUrl(Company $company): string
    {
        $productionUrl = config('services.nfse.sefin_url');
        $homologationUrl = config('services.nfse.sefin_url_homologation');

        return $company->isProduction()
            ? $productionUrl
            : $homologationUrl;
    }

    protected function getCertificatePemPath(Company $company): string
    {
        if (!Storage::exists($company->cert_path)) {
            throw new Exception("Certificado PFX não encontrado.");
        }

        $pfxContent = Storage::get($company->cert_path);
        $password = $company->cert_password;

        $certs = [];
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new Exception("Falha ao ler PFX para conversão.");
        }

        $pemContent = $certs['cert'] . "\n" . $certs['pkey'];

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cert_' . $company->id . '_' . uniqid() . '.pem';
        file_put_contents($tempPath, $pemContent);

        return $tempPath;
    }
}
