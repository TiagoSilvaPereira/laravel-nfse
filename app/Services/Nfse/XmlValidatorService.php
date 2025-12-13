<?php

namespace App\Services\Nfse;

use Exception;
use DOMDocument;

class XmlValidatorService
{
    /**
     * Valida o XML gerado contra o Schema XSD (DPS_v1.00.xsd).
     *
     * @param string $xmlContent
     * @return void
     * @throws Exception
     */
    public function validate(string $xmlContent): void
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        
        $previous = libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $schemaPath = resource_path('schemas/DPS_v1.00.xsd');

        if (!file_exists($schemaPath)) {
            throw new Exception("Arquivo XSD não encontrado em: $schemaPath");
        }

        // Muda o diretório de trabalho para a pasta dos schemas para resolver includes relativos corretamente
        $originalDir = getcwd();
        chdir(dirname($schemaPath));

        try {
            libxml_use_internal_errors(true);

            if (!$dom->schemaValidate(basename($schemaPath))) {
                $errors = libxml_get_errors();
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = trim($error->message) . " (Linha: {$error->line})";
                }
                libxml_clear_errors();
                
                throw new Exception("Erro de validação do Schema XSD: " . implode(' | ', $messages));
            }
        } finally {
            // Restaura o diretório original
            if ($originalDir) {
                chdir($originalDir);
            }
            
            libxml_use_internal_errors($previous);
        }
    }
}
