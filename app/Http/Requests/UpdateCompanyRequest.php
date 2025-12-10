<?php

namespace App\Http\Requests;

use App\Enums\NfseEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'string', 'size:14', Rule::unique('companies')->ignore($this->company)],
            'municipal_registration' => ['nullable', 'string', 'max:15'],
            'municipality_code' => ['sometimes', 'string', 'size:7'],
            'environment' => ['sometimes', Rule::enum(NfseEnvironment::class)],
            'certificate' => ['nullable', 'file', 'mimetypes:application/x-pkcs12,application/octet-stream', 'extensions:pfx,p12', 'max:1024'],
            'cert_password' => ['nullable', 'required_with:certificate', 'string'],
            'serie_dps' => ['nullable', 'string', 'max:5'],
            'last_dps_number' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
