<?php

namespace App\Actions\Company;

use App\Actions\Concerns\HandlesCertificateUpload;
use App\Models\Company;
use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;

class CreateCompany
{
    use HandlesCertificateUpload;

    public function __construct(protected CertificateService $certificateService) {}

    public function handle(array $data, ?UploadedFile $certificate = null): Company
    {
        if ($this->shouldProcessCertificate($data, $certificate)) {
            $data = $this->processCertificateUpload($data, $certificate, $this->certificateService);
        }

        return Company::create($data);
    }
}
