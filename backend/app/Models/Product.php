<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'product_fee_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'product_fee_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesActivities(): HasMany
    {
        return $this->hasMany(SalesActivity::class);
    }

    public function commissionProductFees(): HasMany
    {
        return $this->hasMany(CommissionProductFee::class);
    }

    public function commissionProgressiveRules(): HasMany
    {
        return $this->hasMany(CommissionProgressiveRule::class);
    }
}
