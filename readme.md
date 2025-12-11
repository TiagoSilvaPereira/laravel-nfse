# Passo à passo para emissão de Nota Fiscal de Serviço Eletrônica (padrão nacional) com Laravel

## Introdução

## Ciclo de vida da NFS-e (DPS, NFS-e, eventos, etc)

## Obtenção do certificado digital

## Corrigindo problemas com certificado com algoritmo legado

Criar também um utilitário para corrigir certificados com algoritmos legados.

## Criar projeto Laravel + API (com autenticação)

## Criar migrations

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

## Utilitário para corrigir certificado

## Autenticação na API

## Lista de cidades