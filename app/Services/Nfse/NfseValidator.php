<?php

namespace App\Services\Nfse;

use Exception;

class NfseValidator
{
    /**
     * Valida os dados da emissão antes de gerar o XML.
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function validate(array $data): void
    {
        $this->validateTotals($data);
        $this->validateCustomer($data);
    }

    protected function validateTotals(array $data): void
    {
        if (($data['valores']['vServ'] ?? 0) <= 0) {
            throw new Exception("O valor do serviço (vServ) deve ser maior que zero.");
        }
    }

    protected function validateCustomer(array $data): void
    {
        $tomador = $data['tomador'] ?? [];
        
        if (empty($tomador)) {
            throw new Exception("Dados do tomador são obrigatórios.");
        }

        $isExterior = isset($tomador['endereco']['cPais']) && $tomador['endereco']['cPais'] != '1058'; // 1058 = Brasil

        if ($isExterior) {
            if (empty($tomador['nif'])) {
                // NIF pode ser opcional em alguns casos, mas geralmente é exigido ou deve ser informado "NAO INFORMADO" dependendo da regra
                // Aqui vamos exigir se for exterior, ou aceitar vazio se a regra permitir.
                // Para simplificar: se for exterior, CPF/CNPJ não deve ser enviado, e NIF é ideal.
            }

            if (!empty($tomador['cpfCnpj'])) {
                throw new Exception("Para tomador no exterior, não deve ser informado CPF/CNPJ.");
            }
        } else {
            // Tomador nacional
            if (empty($tomador['cpfCnpj'])) {
                throw new Exception("CPF ou CNPJ do tomador é obrigatório para tomadores nacionais.");
            }
        }
    }
}
