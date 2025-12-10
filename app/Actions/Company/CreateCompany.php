<?php

namespace App\Actions\Company;

use App\Models\Company;
use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;

class CreateCompany
{
    public function __construct(protected CertificateService $certificateService) {}

    public function handle(array $data, ?UploadedFile $certificate = null): Company
    {
        if ($this->shouldProcessCertificate($data, $certificate)) {
            $data = $this->processCertificate($data, $certificate);
        }

        return Company::create($data);
    }

    protected function shouldProcessCertificate(array $data, ?UploadedFile $certificate): bool
    {
        return $certificate && !empty($data['cert_password']);
    }

    protected function processCertificate(array $data, UploadedFile $certificate): array
    {
        $certInfo = $this->certificateService->extractCertInfo(
            $certificate->get(),
            $data['cert_password']
        );

        $data['cert_path'] = $certificate->store('certificates');
        $data['cert_expires_at'] = $certInfo['expires_at'];

        return $data;
    }
}
