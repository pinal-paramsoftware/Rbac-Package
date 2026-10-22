<?php

namespace Parth\LaravelRbacSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLoginOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_key',
        'emp_id',
        'email',
        'otp_type',
        'otp_hash',
        'attempt_count',
        'expiry_time',
        'resend_available_at',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'expiry_time' => 'datetime',
        'resend_available_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('rbac-suite.tables.email_login_otp', 'email_login_otp');
    }
}
