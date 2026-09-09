<?php

namespace App\Models;

use App\Enums\ProgressiveOverflowBehavior;
use Database\Factories\CommissionSettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommissionSetting extends Model
{
    /** @use HasFactory<CommissionSettingFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'version',
        'multiplier_enabled',
        'progressive_enabled',
        'progressive_overflow_behavior',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            $setting->active_slot = $setting->is_active ? 'active' : null;
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'multiplier_enabled' => 'boolean',
            'progressive_enabled' => 'boolean',
            'progressive_overflow_behavior' => ProgressiveOverflowBehavior::class,
            'effective_from' => 'immutable_datetime',
            'effective_until' => 'immutable_datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productFees(): HasMany
    {
        return $this->hasMany(CommissionProductFee::class);
    }

    public function multiplierRules(): HasMany
    {
        return $this->hasMany(CommissionMultiplierRule::class)->orderBy('min_sa');
    }

    public function progressiveRules(): HasMany
    {
        return $this->hasMany(CommissionProgressiveRule::class)
            ->orderBy('min_sa');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
