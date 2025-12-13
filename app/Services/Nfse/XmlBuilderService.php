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
        
        $issueDate = Carbon::now()->setTimezone('America/Sao_Paulo')->format('Y-m-d\TH:i:sP');

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
                    'CNPJ' => preg_replace('/\D/', '', $company->cnpj),
                    'regTrib' => [
                        'opSimpNac' => 3, // 3 - Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)
                        'regApTribSN' => 1,
                        'regEspTrib' => 0,
                    ]
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
        $cnpj = preg_replace('/\D/', '', $company->cnpj);
        $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
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
            'locPrest' => [
                'cLocPrestacao' => $data['servico']['cLocPrestacao'] ?? null,
            ],
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
            'vServPrest' => [
                'vServ' => number_format($values['vServ'], 2, '.', ''),
            ],
            'trib' => [
                'tribMun' => [
                    'tribISSQN' => 1, // Operação tributável
                    'tpRetISSQN' => 1, // 1 - Não Retido
                ],
                'totTrib' => [
                    'pTotTribSN' => '0.00',
                ],
            ]
        ];
    }
}
