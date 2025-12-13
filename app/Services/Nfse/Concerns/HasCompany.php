<?php

namespace App\Services\Nfse\Concerns;

use App\Models\Company;

trait HasCompany
{
    protected Company $company;

    public function setCompany(Company $company): self
    {
        $this->company = $company;
        
        return $this;
    }
}