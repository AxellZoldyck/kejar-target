<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->company_id === $product->company_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === UserRole::SPV && $this->view($user, $product);
    }
}
