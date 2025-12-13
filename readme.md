# Passo à passo para emissão de Nota Fiscal de Serviço Eletrônica (padrão nacional) com Laravel

## Introdução

## Timezone do sistema
O sistema deve estar configurado para o timezone "America/Sao_Paulo" (UTC-3)?

## Ciclo de vida da NFS-e (DPS, NFS-e, eventos, etc)

Detalhes sobre a DPS (Documento Provisório de Serviço), que é o documento inicial, e depois a NFS-e que é o documento final.

O número da DPS documenta as tentativas de emissão, e a NFS-e é o documento oficial. Ou seja, o controle da DPS é feito totalmente pelo sistema emissor, e a NFS-e é o documento que tem validade fiscal.

## Obtenção do certificado digital

## Obtendo dados como URL das webservices, códigos de tributação, etc

## Corrigindo problemas com certificado com algoritmo legado

Criar também um utilitário para corrigir certificados com algoritmos legados.

## Criar projeto Laravel + API (com autenticação)

## Criar migrations

Explicar sobre idempotência de notas fiscais, e também a necessidade do id de integração com o sistema externo, para evitar duplicidade.

## Criar models
Senha do certificado deve ser encriptada no banco de dados, e por isso usamos o cast para isso. Veja o exemplo abaixo:

```php
protected $casts = [
    'cert_password' => 'encrypted',
];
```

## Criar rotas para cadastrar empresa emissora e enviar certificado

## Criar o serviço para lidar com certificados

## Criar o serviço de assinatura do XML

## Criar o serviço que monta o XML da DPS

Garantir que o XML seja gerado com encoding UTF-8 e assinado corretamente, além de comprimido e codificado em base64.

## Utilitário para corrigir certificado

## Emails

- Email de nota emitida para o tomador com link para a NFS-e
- Email diário ou semanal ou mensal com relatórios de notas com falha
- Email diário ou semanal ou mensal com relatórios de notas emitidas
- Email de notificação de vencimento do certificado (30, 15, 7, 3, 1 dias antes)

## Autenticação na API

## Lista de cidades (banco) e lista de países (config)

## Emissão sem o Mapper

```php
$dadosNota = [
    'tomador' => [
        'cpfCnpj' => '12345678900', // Ou nif => '...' se exterior
        'xNome' => 'Fulano de Tal',
        'endereco' => [
            'xLgr' => 'Rua Teste',
            'nro' => '123',
            'cPais' => '1058', // 1058 = Brasil
            'cMun' => '3550308', // Código IBGE (SP)
            // ...
        ]
    ],
    'servico' => [
        'cTribNac' => '010101', // Código de tributação
        'xDescServ' => 'Desenvolvimento de Software',
        'cLocPrestacao' => '3550308',
    ],
    'valores' => [
        'vServ' => 100.00,
        'pAliq' => 2.00,
    ]
];
```

## Emissão com o Mapper

```json
{
    "company_id": 1,
    "customer": {
        "name": "João da Silva",
        "document": "12345678900",
        "address": {
            "street": "Av. Paulista",
            "number": "1000",
            "district": "Bela Vista",
            "city_code": "3550308",
            "zip_code": "01310100"
        }
    },
    "service": {
        "code": "01.07.01",
        "description": "Consultoria em TI",
        "amount": 1500.00,
        "tax_rate": 2.0
    }
}
```

## Erros comuns e como resolver

### [RNG9999] Erro não catalogado

Verifique se está enviando a propriedade correta no payload. Por exemplo, se o serviço espera `dpsXmlGZipB64` e você está enviando `nfseXmlGZipB64`, ocorrerá esse erro.

### [RNG6110] Falha Schema Xml (Xml mal formado. System.NullReferenceException: Object reference not set to an instance of an object.\r\n   at Serpro.Sped.NFSe.Schemas.Controle.ControleValidarSchema.Validar(String xml, TipoDocumentoRequisicao tipoDocReq, ResultadoProcessamento resultado))

Verifique se o XML está formatado corretamente e utilizando UTF-8. Além disso, confira se o XML está sendo assinado corretamente antes do envio.

### [E6155] Xml declarado com prefixo de namespace.

Provavelmente o XML está sendo gerado com o prefixo "ds:" após a assinatura. Resolva passando um prefixo vazio para na classe XMLSecurityDSig, como no exemplo abaixo:

```php
new XMLSecurityDSig('')
```

### [E6154] Xml não está utilizando codificação UTF-8.

Verifique se o XML está sendo gerado com a codificação UTF-8 e com o header correto:

```xml
<?xml version="1.0" encoding="UTF-8"?>
```


