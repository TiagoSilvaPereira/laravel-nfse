# Passo à passo para emissão de Nota Fiscal de Serviço Eletrônica (padrão nacional) com Laravel

## Introdução

## Obtenção do certificado digital

## Corrigindo problemas com certificado com algoritmo legado

## Criar projeto Laravel + API (com autenticação)

## Criar migrations

## Criar models
Senha do certificado deve ser encriptada no banco de dados, e por isso usamos o cast para isso. Veja o exemplo abaixo:

```php
protected $casts = [
    'cert_password' => 'encrypted',
];
```