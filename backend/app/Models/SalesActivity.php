<?php

namespace App\Models;

use App\Enums\SalesActivityStatus;
use Database\Factories\SalesActivityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesActivity extends Model
{
    /** @use HasFactory<SalesActivityFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'team_id',
        'sales_id',
        'product_id',
        'activity_date',
        'customer_reference',
        'notes',
        'evidence_path',
        'status',
        'submitted_at',
        'validated_at',
        'validated_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'immutable_date',
            'status' => SalesActivityStatus::class,
            'submitted_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SalesActivityStatusHistory::class)->oldest('created_at');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
