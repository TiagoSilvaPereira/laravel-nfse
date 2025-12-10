<?php

namespace App\Http\Requests;

use App\Enums\NfseEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'size:14', 'unique:companies,cnpj'],
            'municipal_registration' => ['nullable', 'string', 'max:15'],
            'municipality_code' => ['required', 'string', 'size:7'],
            'environment' => ['required', Rule::enum(NfseEnvironment::class)],
            'certificate' => ['required', 'file', 'mimetypes:application/x-pkcs12,application/octet-stream', 'extensions:pfx,p12', 'max:1024'],
            'cert_password' => ['required', 'string'],
            'serie_dps' => ['nullable', 'string', 'max:5'],
            'last_dps_number' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
