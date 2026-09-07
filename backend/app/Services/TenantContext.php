<?php

namespace App\Services;

use App\Contracts\CurrentTenant;
use App\Models\Company;

class TenantContext implements CurrentTenant
{
    private ?Company $company = null;

    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function id(): ?string
    {
        return $this->company?->getKey();
    }
}
