<?php

namespace App\Exceptions;

use Exception;

class NfseApiException extends Exception
{
    protected $originalData;

    public function __construct($responseBody, $code = 0, ?Exception $previous = null)
    {
        $this->originalData = json_decode($responseBody, true);
        
        $message = $this->formatMessage($this->originalData, $responseBody);

        parent::__construct($message, $code, $previous);
    }

    public function getOriginalData()
    {
        return $this->originalData;
    }

    private function formatMessage($data, $rawBody)
    {
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return "Erro na API NFS-e (Resposta não-JSON): " . substr($rawBody, 0, 200) . (strlen($rawBody) > 200 ? '...' : '');
        }

        if (isset($data['erros']) && is_array($data['erros'])) {
            $messages = [];

            foreach ($data['erros'] as $erro) {
                $cod = $erro['Codigo'] ?? 'S/C';
                $desc = $erro['Descricao'] ?? 'Sem descrição';
                $comp = $erro['Complemento'] ?? '';
                
                $msg = "[$cod] $desc";

                if ($comp) {
                    $msg .= " ($comp)";
                }

                $messages[] = $msg;
            }

            return implode(" | ", $messages);
        }
        
        return "Erro na API NFS-e: " . json_encode($data);
    }
}
