<?php

namespace App\Services\Nfse;

use Exception;
use DOMDocument;
use Illuminate\Support\Facades\File;

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

        $sourcePath = resource_path('schemas');
        $cachePath = storage_path('app/schemas_cache');
        $mainSchema = 'DPS_v1.00.xsd';

        $this->prepareSchemas($sourcePath, $cachePath);

        $schemaFile = $cachePath . DIRECTORY_SEPARATOR . $mainSchema;

        if (!file_exists($schemaFile)) {
            throw new Exception("Arquivo XSD não encontrado em: $schemaFile");
        }

        $originalDir = getcwd();
        chdir($cachePath);

        try {
            libxml_use_internal_errors(true);

            if (!$dom->schemaValidate($mainSchema)) {
                $errors = libxml_get_errors();
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = trim($error->message) . " (Linha: {$error->line})";
                }
                libxml_clear_errors();
                
                throw new Exception("Erro de validação do Schema XSD: " . implode(' | ', $messages));
            }
        } finally {
            if ($originalDir) {
                chdir($originalDir);
            }
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Esse método é necessário para corrigir problemas nos arquivos XSD originais. Por exemplo,
     * o arquivo tiposSimples_v1.00.xsd possui alguns padrões de regex que não 
     * são compatíveis com a validação do PHP. 
     * Além disso, atualiza a versão do schema conforme configuração, pois às vezes
     * a API da RFB modifica a versão mas os arquivos XSD não são atualizados.
     * @param string $source 
     * @param string $destination 
     * @return void 
     */
    private function prepareSchemas(string $source, string $destination): void
    {
        if (!File::exists($destination)) {
            File::ensureDirectoryExists($destination);
            File::copyDirectory($source, $destination);

            
            $fileToFix = $destination . DIRECTORY_SEPARATOR . 'tiposSimples_v1.00.xsd';

            if (File::exists($fileToFix)) {
                $content = File::get($fileToFix);
                
                $content = str_replace(
                    'pattern value="^(?!0{1,5}$)\d{1,5}$"',
                    'pattern value="[0-9]{1,5}"',
                    $content
                );

                // Essa correção é feita para atualizar a versão do schema conforme configuração,
                // caso a RFB modifique a versão mas não atualize os arquivos XSD.
                $content = str_replace(
                    '<xs:pattern value="1\.00"/>',
                    '<xs:pattern value="' . config('services.nfse.version') . '"/>',
                    $content
                );

                File::put($fileToFix, $content);
            }
        }
    }
}
