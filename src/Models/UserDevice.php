<?php

namespace Parth\LaravelRbacSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_authorized' => 'boolean',
        'last_authenticated_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('rbac-suite.tables.user_devices', 'user_devices');
    }

    /**
     * The user that owns this authorized device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('rbac-suite.user_model', 'App\\Models\\User'), 'user_id');
    }
}
