<?php

namespace Parth\LaravelRbacSuite\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parth\LaravelRbacSuite\Models\UserDevice;
use Parth\LaravelRbacSuite\Models\UserTwoFactor;
use Spatie\Permission\Traits\HasRoles;

trait HasRbacUser
{
    use HasRoles, SoftDeletes;

    /**
     * Initialize HasRbacUser trait.
     */
    public function initializeHasRbacUser(): void
    {
        $this->mergeCasts([
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password_updated_at' => 'datetime',
            'is_active' => 'string',
        ]);
    }

    /**
     * Bootstrap model events for audit fields.
     */
    public static function bootHasRbacUser(): void
    {
        static::creating(function ($user) {
            if (auth()->check() && empty($user->created_id)) {
                $user->created_id = auth()->id();
            }
        });

        static::updating(function ($user) {
            if (auth()->check()) {
                $user->updated_id = auth()->id();
            }

            // Prevent overwriting created_id on update
            if ($user->isDirty('created_id') && ! empty($user->getOriginal('created_id'))) {
                $user->created_id = $user->getOriginal('created_id');
            }
        });

        static::deleting(function ($user) {
            if (auth()->check() && ! (method_exists($user, 'isForceDeleting') && $user->isForceDeleting())) {
                $user->deleted_id = auth()->id();
                $user->saveQuietly();
            }
        });

        static::restoring(function ($user) {
            $user->deleted_id = null;
            if (auth()->check()) {
                $user->updated_id = auth()->id();
            }
        });
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return (string) $this->is_active === '1';
    }

    /**
     * Check if user has an Admin role.
     */
    public function isAdmin(): bool
    {
        $adminName = config('rbac-suite.roles.admin_name', 'Admin');

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(function ($role) use ($adminName) {
                return strcasecmp($role->name, $adminName) === 0;
            });
        }

        return $this->roles()->whereRaw('LOWER(name) = ?', [strtolower($adminName)])->exists();
    }

    /**
     * Relationship with UserTwoFactor.
     */
    public function twoFactor(): HasOne
    {
        return $this->hasOne(UserTwoFactor::class, 'user_id');
    }

    /**
     * Relationship with UserDevice.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_id');
    }

    /**
     * Audit: User who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', static::class), 'created_id');
    }

    /**
     * Audit: User who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', static::class), 'updated_id');
    }

    /**
     * Audit: User who soft deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', static::class), 'deleted_id');
    }

    /**
     * Audit: User who last updated this user's password.
     */
    public function passwordUpdater(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', static::class), 'password_updated_id');
    }
}
