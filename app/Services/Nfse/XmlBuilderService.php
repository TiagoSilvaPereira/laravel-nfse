<?php

namespace App\Services\Nfse;

use App\Helpers\Tools;
use App\Services\Nfse\Concerns\HasCompany;
use Carbon\Carbon;
use Spatie\ArrayToXml\ArrayToXml;
use Illuminate\Support\Facades\Log;

class XmlBuilderService
{
    use HasCompany;

    private MunicipalParamsService $paramsService;

    public function __construct()
    {
        $this->paramsService = new MunicipalParamsService();
    }

    public function buildDpsXml(array $data): string
    {
        $this->paramsService->setCompany($this->company);
        
        $dpsNumber = $data['nDPS'];
        $dpsSeries = $data['serie'];
        
        $issueDate = Carbon::now()->setTimezone('America/Sao_Paulo')->format('Y-m-d\TH:i:sP');
        $competencia = Carbon::now()->format('Y-m-d');

        $infDpsId = $this->generateDpsId($dpsNumber, $dpsSeries);
        
        $cityCode = $this->company->municipality_code;
        
        // # IMPORTANTE: Verifica se o município é aderente ao ambiente nacional
        // # utilizando o serviço de parâmetros municipais.
        if (!$this->paramsService->isCityAderent($cityCode)) {
            Log::warning('Município não aderente ao ambiente nacional', ['city' => $cityCode]);
        }

        $dpsArray = [
            'infDPS' => [
                '_attributes' => [
                    'Id' => $infDpsId,
                ],
                'tpAmb' => $this->company->environment->value,
                'dhEmi' => $issueDate,
                'verAplic' => 'LaravelNFSe_v1.0',
                'serie' => $dpsSeries,
                'nDPS' => $dpsNumber,
                'dCompet' => $competencia,
                'tpEmit' => '1',  // Emissão normal
                // 'cMotivoEmisTI' => '1',  // Motivo de emissão TI (1 = Normal; opcional mas listado, use 1 para testes) - só preenche se o emitente for diferente do prestador
                'cLocEmi' => $this->company->municipality_code,
                'prest' => [
                    'CNPJ' => preg_replace('/\D/', '', $this->company->cnpj),
                    'regTrib' => [
                        'opSimpNac' => 3, // 3 - Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)
                        'regApTribSN' => 1,
                        'regEspTrib' => 0,
                    ]
                ],
                'toma' => $this->buildCustomer($data['tomador']),
                'serv' => $this->buildService($data),
                'valores' => $this->buildValues($data['valores'], $data['servico'], $competencia),
            ]
        ];

        $xml = ArrayToXml::convert($dpsArray, [
            'rootElementName' => 'DPS',
            '_attributes' => [
                'versao' => config('services.nfse.version'),
                'xmlns' => 'http://www.sped.fazenda.gov.br/nfse',
            ],
        ], true, 'UTF-8');

        return $xml;
    }

    public function generateDpsId(int $dpsNumber, string $dpsSeries): string
    {
        // ID = "DPS" + CMun(7) + TipoInscr(1) + CNPJ(14) + Serie(5) + Numero(15)
        $tipoInscr = '2'; // CNPJ
        $cMun = $this->company->municipality_code;
        $cnpj = preg_replace('/\D/', '', $this->company->cnpj);
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
            $cPais = $end['cPais'] ?? '1058'; // Default Brasil

            $toma['end'] = [];

            if ($cPais === '1058') {
                $toma['end']['endNac'] = [
                    'cMun' => $end['cMun'] ?? null,
                    'CEP' => $end['CEP'] ?? null,
                ];
            } else {
                $toma['end']['endExt'] = [
                    'cPais' => $cPais,
                    'cEndPost' => $end['cEndPost'] ?? '00000000', // Ajustar conforme necessidade
                    'xCidade' => $end['xCidade'] ?? 'Cidade Exterior',
                    'xEstProvReg' => $end['xEstProvReg'] ?? 'Estado Exterior',
                ];
            }

            $toma['end']['xLgr'] = $end['xLgr'] ?? 'Rua Desconhecida';
            $toma['end']['nro'] = $end['nro'] ?? 'S/N';
            $toma['end']['xCpl'] = $end['xCpl'] ?? null;
            $toma['end']['xBairro'] = $end['xBairro'] ?? 'Centro';
            
            $toma['end'] = Tools::removeNullValues($toma['end']);
            
            if (isset($toma['end']['endNac'])) {
                $toma['end']['endNac'] = Tools::removeNullValues($toma['end']['endNac']);
            }

            if (isset($toma['end']['endExt'])) {
                $toma['end']['endExt'] = Tools::removeNullValues($toma['end']['endExt']);
            }
        }

        return $toma;
    }

    protected function buildService(array $data): array
    {
        $serv = [
            'locPrest' => [
                'cLocPrestacao' => $this->company->municipality_code,
            ],
            'cServ' => [
                'cTribNac' => preg_replace('/\D/', '', $data['servico']['cTribNac']), // Código de Tributação Nacional (apenas números)
                'xDescServ' => $data['servico']['xDescServ'],
                'cNBS' => $data['servico']['cNBS'],
            ],
        ];

        return array_filter($serv, fn($v) => !is_null($v));
    }

    protected function buildValues(array $values, array $serviceData, string $competencia): array
    {
        $this->paramsService->setCompany($this->company);

        $aliquota = null;
        $tpRetISSQN = 1; // Default: 1 - Não Retido
        
        // # IMPORTANTE: O código abaixo é apenas um exemplo de como os
        // # parâmetros municipais podem ser utilizados para obter a alíquota
        // # real do serviço. Consulte com seu contador as regras específicas
        // # e ajuste conforme necessário.
        // if (isset($serviceData['cTribNac'])) {
        //     $cityCode = $this->company->municipality_code;
        //     $serviceCode = $serviceData['cTribNac'];
            
        //     $aliquotaData = $this->paramsService->getServiceAliquota($cityCode, $serviceCode, $competencia);
            
        //     if ($aliquotaData && isset($aliquotaData['Aliq'])) {
        //         $aliquota = $aliquotaData['Aliq'];
        //     }
        // }
        
        $tributacao = [
            'tribISSQN' => 1, // Operação tributável
            'tpRetISSQN' => $tpRetISSQN,
        ];
        
        // # Adiciona alíquota se obtida dos parâmetros
        if ($aliquota !== null) {
            $tributacao['pAliq'] = number_format($aliquota, 2, '.', '');
        }
        
        return [
            'vServPrest' => [
                'vServ' => number_format($values['vServ'], 2, '.', ''),
            ],
            'trib' => [
                'tribMun' => $tributacao,
                'totTrib' => [
                    'pTotTribSN' => '0.00',
                ],
            ]
        ];
    }
}
