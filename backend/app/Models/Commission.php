<?php

namespace App\Models;

use Database\Factories\CommissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Commission extends Model
{
    /** @use HasFactory<CommissionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'team_id',
        'sales_id',
        'period',
        'product_fee_amount',
        'multiplier_value',
        'progressive_incentive_amount',
        'total_amount',
        'formula_snapshot',
        'calculation_version',
        'calculated_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'product_fee_amount' => 'integer',
            'multiplier_value' => 'decimal:4',
            'progressive_incentive_amount' => 'integer',
            'total_amount' => 'integer',
            'formula_snapshot' => 'array',
            'calculation_version' => 'integer',
            'calculated_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
