<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'plan_code',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'provider',
        'provider_subscription_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $subscription): void {
            $status = $subscription->status;
            $subscription->running_slot = $status instanceof SubscriptionStatus && $status->isRunning()
                ? 'running'
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'immutable_datetime',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('running_slot', 'running');
    }

    public function allowsMutation(): bool
    {
        if (! $this->status->allowsMutation()) {
            return false;
        }

        if ($this->status === SubscriptionStatus::TRIALING) {
            return $this->trial_ends_at !== null && now()->lt($this->trial_ends_at);
        }

        return $this->ends_at === null || now()->lt($this->ends_at);
    }

    public function isRunning(): bool
    {
        return $this->status->isRunning();
    }
}
