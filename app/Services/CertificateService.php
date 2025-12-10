<?php

namespace App\Services;

use Exception;

class CertificateService
{
    /**
     * Lê o arquivo/contâiner PFX, obtém e retorna informações sobre o certificado.
     *
     * @param string $pfxContent Conteúdo binário do arquivo .pfx
     * @param string $password Senha do certificado
     * @return array{expires_at: string, subject: array, issuer: array}
     * @throws Exception
     */
    public function extractCertInfo(string $pfxContent, string $password): array
    {
        $certs = [];
        
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new Exception('Não foi possível ler o certificado. Verifique se a senha está correta e se o arquivo é um PKCS#12 (.pfx/.p12) válido.');
        }

        $data = openssl_x509_parse($certs['cert']);
        
        if (!$data) {
             throw new Exception('Falha ao extrair dados do certificado X.509.');
        }

        return [
            'expires_at' => isset($data['validTo_time_t']) 
                ? date('Y-m-d', $data['validTo_time_t']) 
                : null,
            'subject' => $data['subject'] ?? [],
            'issuer' => $data['issuer'] ?? [],
        ];
    }
}
