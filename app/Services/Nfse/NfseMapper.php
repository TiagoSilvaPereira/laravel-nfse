<?php

namespace App\Services\Nfse;

use App\Helpers\Tools;

class NfseMapper
{
    /**
     * Converte o payload amigável da API para o formato interno da estrutura da NFS-e.
     *
     * @param array $data
     * @return array
     */
    public function toInternal(array $data): array
    {
        // Dados do Payload amigável
        $customer = $data['customer'];
        $service = $data['service'];
        $address = $customer['address'] ?? null;

        // Mapeamento para estrutura interna da NFS-e
        $tomador = [
            'xNome' => $customer['name'],
            'cpfCnpj' => $customer['cpfCnpj'] ?? null,
            'nif' => $customer['nif'] ?? null,
        ];

        if(isset($address)) {
            $tomador['endereco'] = [
                'xLgr' => $address['street'] ?? null,
                'nro' => $address['number'] ?? null,
                'xCpl' => $address['complement'] ?? null,
                'xBairro' => $address['district'] ?? null,
                'cMun' => $address['city_code'] ?? null,
                'cPais' => $address['country_code'] ?? '1058',
                'CEP' => $address['zip_code'] ?? null,
            ];

            $tomador['endereco'] = Tools::removeNullValues($tomador['endereco']);
        }


        $servico = [
            'cTribNac' => $service['code'],
            'xDescServ' => $service['description'],
            'cLocPrestacao' => $service['location_code'] ?? null,
            'cNBS' => $service['nbs_code'],
        ];

        $valores = [
            'vServ' => $service['amount'],
            'pAliq' => $service['tax_rate'] ?? 0,
        ];

        return [
            'tomador' => $tomador,
            'valores' => $valores,
            'servico' => Tools::removeNullValues($servico),
        ];
    }
}
