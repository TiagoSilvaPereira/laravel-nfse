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
            'company_id' => ['required', 'exists:companies,id'],
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

            'service' => ['required', 'array'],
            'service.code' => ['required', 'string'], // cTribNac
            'service.description' => ['required', 'string', 'max:2000'],
            'service.location_code' => ['nullable', 'string', 'size:7'], // cLocPrestacao
            'service.amount' => ['required', 'numeric', 'min:0.01'],
            'service.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
