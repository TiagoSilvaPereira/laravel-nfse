<?php

namespace App\Services\Nfse;

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
        $customer = $data['customer'];
        $service = $data['service'];
        $address = $customer['address'] ?? null;

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
            
            $tomador['endereco'] = array_filter($tomador['endereco'], fn($v) => !is_null($v));
        }


        $servico = [
            'cTribNac' => $service['code'],
            'xDescServ' => $service['description'],
            'cLocPrestacao' => $service['location_code'] ?? null,
        ];

        $valores = [
            'vServ' => $service['amount'],
            'pAliq' => $service['tax_rate'] ?? 0,
        ];

        return [
            'tomador' => $tomador,
            'servico' => array_filter($servico, fn($v) => !is_null($v)),
            'valores' => $valores,
        ];
    }
}
