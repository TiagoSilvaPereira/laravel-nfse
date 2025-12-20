<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Empresa emissora
            'company_id' => ['required', 'exists:companies,id'],

            // Integração (opcional) - Identificador da nota no sistema externo
            // Esse campo é muito importante para evitar duplicidade na emissão de notas,
            // por exemplo, quando há falha de envio e o sistema tenta reenviar a nota.
            'integration_id' => ['nullable', 'string', 'max:48'],
            
            // Tomador
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.cpfCnpj' => ['nullable', 'string', 'max:14'], // CPF or CNPJ
            'customer.nif' => ['nullable', 'string', 'max:40'], // NIF
            
            'customer.address' => ['nullable', 'array'],
            'customer.address.street' => ['nullable', 'string', 'max:255'],
            'customer.address.number' => ['nullable', 'string', 'max:60'],
            'customer.address.complement' => ['nullable', 'string', 'max:60'],
            'customer.address.district' => ['nullable', 'string', 'max:60'],
            'customer.address.city_code' => ['nullable', 'string', 'size:7'],
            'customer.address.zip_code' => ['nullable', 'string', 'max:8'],
            'customer.address.country_code' => ['nullable', 'string', 'max:4'],

            // Serviço
            'service' => ['required', 'array'],
            'service.code' => ['required', 'string', 'max:6'], // cTribNac
            'service.nbs_code' => ['required', 'string', 'max:9'], // cNBS
            'service.description' => ['required', 'string', 'max:2000'],
            'service.location_code' => ['nullable', 'string', 'size:7'], // cLocPrestacao
            'service.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
