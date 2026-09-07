<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'activity_label' => 'SA',
            'timezone' => 'Asia/Jakarta',
            'status' => CompanyStatus::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => CompanyStatus::INACTIVE]);
    }
}
