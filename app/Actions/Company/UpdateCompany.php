<?php

namespace App\Actions\Company;

use App\Models\Company;
use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateCompany
{
    public function __construct(protected CertificateService $certificateService) {}

    public function handle(Company $company, array $data, ?UploadedFile $certificate = null): Company
    {
        if ($this->shouldUpdateCertificate($data, $certificate)) {
            $this->deleteExistingCertificate($company);
            $data = $this->processNewCertificate($data, $certificate);
        }

        $company->update($data);

        return $company->refresh();
    }

    protected function shouldUpdateCertificate(array $data, ?UploadedFile $certificate): bool
    {
        return $certificate && !empty($data['cert_password']);
    }

    protected function deleteExistingCertificate(Company $company): void
    {
        $hasExistingCertificate = $company->cert_path && Storage::exists($company->cert_path);

        if ($hasExistingCertificate) {
            Storage::delete($company->cert_path);
        }
    }

    protected function processNewCertificate(array $data, UploadedFile $certificate): array
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
