<?php

namespace App\Actions\Company;

use App\Actions\Concerns\HandlesCertificateUpload;
use App\Models\Company;
use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateCompany
{
    use HandlesCertificateUpload;

    public function __construct(protected CertificateService $certificateService) {}

    public function handle(Company $company, array $data, ?UploadedFile $certificate = null): Company
    {
        if ($this->shouldProcessCertificate($data, $certificate)) {
            $this->deleteExistingCertificate($company);
            $data = $this->processCertificateUpload($data, $certificate, $this->certificateService);
        }

        $company->update($data);

        return $company->refresh();
    }

    protected function deleteExistingCertificate(Company $company): void
    {
        $hasExistingCertificate = $company->cert_path && Storage::exists($company->cert_path);

        if ($hasExistingCertificate) {
            Storage::delete($company->cert_path);
        }
    }
}
