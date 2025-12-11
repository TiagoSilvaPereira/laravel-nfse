<?php

namespace App\Services\Nfse;

use App\Models\Company;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Storage;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SignatureService
{
    /**
     * Assina o XML da DPS.
     *
     * @param string $xmlContent O conteúdo XML a ser assinado.
     * @param Company $company A empresa emissora (contém o certificado).
     * @return string O XML assinado.
     * @throws Exception
     */
    public function sign(string $xmlContent, Company $company): string
    {
        if (!Storage::exists($company->cert_path)) {
            throw new Exception("Certificado não encontrado em: {$company->cert_path}");
        }

        $pfxContent = Storage::get($company->cert_path);
        $password = $company->cert_password;

        if (!$pfxContent) {
            throw new Exception("Conteúdo do certificado vazio.");
        }

        $certs = [];
        
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new Exception("Falha ao ler o arquivo PKCS#12. Verifique a senha.");
        }

        $dom = new DOMDocument();
        $dom->loadXML($xmlContent);

        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        
        $objDSig->addReference(
            $dom,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'],
            ['force_uri' => true]
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($certs['pkey']);

        $objDSig->sign($objKey);

        $objDSig->add509Cert($certs['cert']);

        $objDSig->appendSignature($dom->documentElement);

        return $dom->saveXML();
    }
}
