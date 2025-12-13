<?php

namespace App\Services\Nfse;

use App\Models\Company;
use Carbon\Carbon;
use Spatie\ArrayToXml\ArrayToXml;

class XmlBuilderService
{
    public function buildDpsXml(Company $company, array $data): string
    {
        $dpsNumber = $data['nDPS'];
        $dpsSeries = $data['serie'];
        $issueDate = Carbon::now()->setTimezone('America/Sao_Paulo')->  format('Y-m-d\TH:i:sP');

        $infDpsId = $this->generateDpsId($company, $dpsNumber, $dpsSeries);

        $dpsArray = [
            'infDPS' => [
                '_attributes' => [
                    'Id' => $infDpsId,
                ],
                'tpAmb' => $company->environment->value,
                'dhEmi' => $issueDate,
                'verAplic' => 'LaravelNFSe_v1.0',
                'dCompet' => Carbon::now()->format('Y-m-d'),
                'prest' => [
                    'CNPJ' => $company->cnpj,
                ],
                'toma' => $this->buildCustomer($data['tomador']),
                'serv' => $this->buildService($data),
                'valores' => $this->buildValues($data['valores']),
            ]
        ];

        $xml = ArrayToXml::convert($dpsArray, [
            'rootElementName' => 'DPS',
            '_attributes' => [
                'versao' => '1.00',
                'xmlns' => 'http://www.sped.fazenda.gov.br/nfse',
            ],
        ], true, 'UTF-8');

        return $xml;
    }

    public function generateDpsId(Company $company, int $dpsNumber, string $dpsSeries): string
    {
        // ID = "DPS" + CMun(7) + TipoInscr(1) + CNPJ(14) + Serie(5) + Numero(15)
        $tipoInscr = '2'; // CNPJ
        $cMun = $company->municipality_code;
        $cnpj = $company->cnpj;
        $serie = str_pad($dpsSeries, 5, '0', STR_PAD_LEFT);
        $nDps = str_pad($dpsNumber, 15, '0', STR_PAD_LEFT);

        return "DPS{$cMun}{$tipoInscr}{$cnpj}{$serie}{$nDps}";
    }

    protected function buildCustomer(array $customerData): array
    {
        $toma = [];

        if (isset($customerData['cpfCnpj'])) {
            $len = strlen($customerData['cpfCnpj']);
            if ($len === 11) {
                $toma['CPF'] = $customerData['cpfCnpj'];
            } else {
                $toma['CNPJ'] = $customerData['cpfCnpj'];
            }
        } elseif (isset($customerData['nif'])) {
            $toma['NIF'] = $customerData['nif'];
        }

        $toma['xNome'] = $customerData['xNome'];

        if (isset($customerData['endereco'])) {
            $end = $customerData['endereco'];
            $toma['end'] = [
                'xLgr' => $end['xLgr'] ?? 'Rua Desconhecida',
                'nro' => $end['nro'] ?? 'S/N',
                'xCpl' => $end['xCpl'] ?? null,
                'xBairro' => $end['xBairro'] ?? 'Centro',
                'cMun' => $end['cMun'] ?? null, // Obrigatório se Brasil
                'cPais' => $end['cPais'] ?? '1058', // Default Brasil
                'CEP' => $end['CEP'] ?? null,
            ];
            
            $toma['end'] = array_filter($toma['end'], fn($v) => !is_null($v));
        }

        return $toma;
    }

    protected function buildService(array $data): array
    {
        $serv = [
            'cLocPrestacao' => $data['servico']['cLocPrestacao'] ?? null, // Onde o serviço foi prestado
            'cServ' => [
                'cTribNac' => $data['servico']['cTribNac'], // Código de Tributação Nacional
                'xDescServ' => $data['servico']['xDescServ'],
            ],
        ];

        return array_filter($serv, fn($v) => !is_null($v));
    }

    protected function buildValues(array $values): array
    {
        return [
            'vServ' => number_format($values['vServ'], 2, '.', ''),
            'tributos' => [
                'trib' => [
                    'pAliq' => number_format($values['pAliq'] ?? 0, 2, '.', ''),
                    'tpRet' => 1, // 1 - Sem retenção (exemplo simplificado)
                ]
            ]
        ];
    }
}
