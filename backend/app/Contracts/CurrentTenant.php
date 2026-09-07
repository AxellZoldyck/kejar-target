<?php

namespace App\Contracts;

use App\Models\Company;

interface CurrentTenant
{
    public function setCompany(Company $company): void;

    public function company(): ?Company;

    public function id(): ?string;
}
