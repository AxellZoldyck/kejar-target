<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'supervisor_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeTeamMembership(): HasOne
    {
        return $this->hasOne(TeamMember::class)
            ->whereNull('left_at')
            ->latest('joined_at');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['id', 'company_id', 'joined_at', 'left_at', 'active_slot'])
            ->withTimestamps();
    }

    public function salesActivities(): HasMany
    {
        return $this->hasMany(SalesActivity::class, 'sales_id');
    }

    public function validatedSalesActivities(): HasMany
    {
        return $this->hasMany(SalesActivity::class, 'validated_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class, 'sales_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'sales_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN;
    }

    public function isSpv(): bool
    {
        return $this->role === UserRole::SPV;
    }

    public function isSales(): bool
    {
        return $this->role === UserRole::SALES;
    }
}
