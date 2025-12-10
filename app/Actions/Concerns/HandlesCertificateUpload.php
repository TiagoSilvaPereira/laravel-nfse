<?php

namespace App\Actions\Concerns;

use App\Services\CertificateService;
use Illuminate\Http\UploadedFile;

trait HandlesCertificateUpload
{
    protected function shouldProcessCertificate(array $data, ?UploadedFile $certificate): bool
    {
        return $certificate && !empty($data['cert_password']);
    }

    protected function processCertificateUpload(array $data, UploadedFile $certificate, CertificateService $certificateService): array
    {
        $certInfo = $certificateService->extractCertInfo(
            $certificate->get(),
            $data['cert_password']
        );

        $data['cert_path'] = $certificate->store('certificates');
        $data['cert_expires_at'] = $certInfo['expires_at'];

        return $data;
    }
}
