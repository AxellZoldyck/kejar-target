<?php

namespace App\Models;

use App\Enums\SalesActivityStatus;
use Database\Factories\SalesActivityStatusHistoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesActivityStatusHistory extends Model
{
    /** @use HasFactory<SalesActivityStatusHistoryFactory> */
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'sales_activity_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SalesActivityStatus::class,
            'to_status' => SalesActivityStatus::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesActivity(): BelongsTo
    {
        return $this->belongsTo(SalesActivity::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
