<?php

namespace App\Models;

use Database\Factories\CommissionMultiplierRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionMultiplierRule extends Model
{
    /** @use HasFactory<CommissionMultiplierRuleFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'commission_setting_id',
        'min_sa',
        'max_sa',
        'multiplier_value',
    ];

    protected function casts(): array
    {
        return [
            'min_sa' => 'integer',
            'max_sa' => 'integer',
            'multiplier_value' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function commissionSetting(): BelongsTo
    {
        return $this->belongsTo(CommissionSetting::class);
    }

    public function matches(int $validatedCount): bool
    {
        return $validatedCount >= $this->min_sa
            && ($this->max_sa === null || $validatedCount <= $this->max_sa);
    }
}
