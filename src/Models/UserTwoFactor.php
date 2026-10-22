<?php

namespace Parth\LaravelRbacSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTwoFactor extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'last_verified_window' => 'integer',
        'google2fa_enabled' => 'boolean',
        'is_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_setup_completed' => 'boolean',
    ];

    protected $hidden = [
        'secret_key',
        'google2fa_secret',
    ];

    public function getTable(): string
    {
        return config('rbac-suite.tables.user_two_factor', 'user_two_factor');
    }

    /**
     * Relationship with User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', 'App\\Models\\User'), 'user_id');
    }
}
