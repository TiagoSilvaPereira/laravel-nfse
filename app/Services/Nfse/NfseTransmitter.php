<?php

namespace App\Services\Nfse;

use App\Exceptions\NfseApiException;
use App\Models\Company;
use App\Services\Nfse\Concerns\HasCompany;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class NfseTransmitter
{
    use HasCompany;

    protected NfseClient $client;

    public function __construct(NfseClient $client)
    {
        $this->client = $client;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        $this->client->setCompany($company);
        
        return $this;
    }

    public function transmit(string $signedXml): array
    {
        $compressedBase64Xml = $this->compressAndEncodeXml($signedXml);

        $payload = [
            'dpsXmlGZipB64' => $compressedBase64Xml
        ];

        try {
            return $this->client->postToSefin($payload);
        } catch (RequestException $e) {

            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'Sem resposta do servidor';
            
            Log::error("Erro no envio da DPS: " . $e->getMessage());
            Log::error("Conteúdo da resposta de erro: " . $responseBody);

            if ($e->hasResponse()) {
                throw new NfseApiException($responseBody, $e->getCode(), $e);
            }

            throw $e;

        } catch (\Exception $e) {
            throw new Exception("Erro na transmissão da NFS-e: " . $e->getMessage());
        }
    }

    protected function compressAndEncodeXml(string $xml): string
    {
        $gzipped = gzencode($xml);
        return base64_encode($gzipped);
    }
}
