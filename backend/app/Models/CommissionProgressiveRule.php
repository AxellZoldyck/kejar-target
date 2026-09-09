<?php

namespace App\Models;

use Database\Factories\CommissionProgressiveRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionProgressiveRule extends Model
{
    /** @use HasFactory<CommissionProgressiveRuleFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'commission_setting_id',
        'min_sa',
        'max_sa',
        'incentive_amount',
    ];

    protected function casts(): array
    {
        return [
            'min_sa' => 'integer',
            'max_sa' => 'integer',
            'incentive_amount' => 'integer',
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

    public function matches(int $sequenceNumber): bool
    {
        return $sequenceNumber >= $this->min_sa
            && ($this->max_sa === null || $sequenceNumber <= $this->max_sa);
    }
}
