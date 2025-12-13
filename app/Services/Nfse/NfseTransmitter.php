<?php

namespace App\Services\Nfse;

use App\Exceptions\NfseApiException;
use App\Models\Company;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Exception;
use GuzzleHttp\Exception\RequestException;

class NfseTransmitter
{
    public function transmit(string $signedXml, Company $company): array
    {
        $baseUrl = $this->calculateBaseUrl($company);
        $finalUrl = rtrim($baseUrl, '/') . '/nfse';

        $compressedBase64Xml = $this->compressAndEncodeXml($signedXml);

        $payload = [
            'dpsXmlGZipB64' => $compressedBase64Xml
        ];

        $certPath = $this->getCertificatePemPath($company);

        $client = new Client([
            'cert' => $certPath,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'curl' => [
                CURLOPT_SSLVERSION => 6, // TLS 1.2
            ],
        ]);

        try {
            $response = $client->post($finalUrl, [
                'json' => $payload
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            
            return $body;

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Sem resposta do servidor';
            
            \Illuminate\Support\Facades\Log::error("Erro no envio da DPS: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("Conteúdo da resposta de erro: " . $responseBody);

            if ($e->hasResponse()) {
                throw new NfseApiException($responseBody, $e->getCode(), $e);
            }

            throw $e;
        } catch (\Exception $e) {
            throw new Exception("Erro na transmissão da NFS-e: " . $e->getMessage());
        } finally {
            if (file_exists($certPath) && str_contains($certPath, 'temp')) {
                unlink($certPath);
            }
        }
    }

    protected function compressAndEncodeXml(string $xml): string
    {
        $gzipped = gzencode($xml);
        return base64_encode($gzipped);
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
