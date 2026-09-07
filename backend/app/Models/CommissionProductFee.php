<?php

namespace App\Models;

use Database\Factories\CommissionProductFeeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionProductFee extends Model
{
    /** @use HasFactory<CommissionProductFeeFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = ['company_id', 'commission_setting_id', 'product_id', 'fee_amount'];

    protected function casts(): array
    {
        return ['fee_amount' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function commissionSetting(): BelongsTo
    {
        return $this->belongsTo(CommissionSetting::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
