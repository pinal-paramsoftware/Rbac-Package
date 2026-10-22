<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Stage Authentication Pipeline Defaults
    |--------------------------------------------------------------------------
    |
    | Default operational parameters for the 6-stage secondary authentication
    | pipeline. These defaults are loaded if database settings are not initialized.
    |
    */

    'stages' => [
        'captcha' => [
            'enabled' => true,
            'ttl_seconds' => 120,
            'width' => 120,
            'height' => 40,
        ],

        'email_otp' => [
            'enabled' => false,
            'max_attempts' => 5,
            'resend_interval_minutes' => 5,
            'expiry_minutes' => 10,
        ],

        'mobile_otp' => [
            'enabled' => false,
            'max_attempts' => 5,
            'resend_interval_minutes' => 5,
            'expiry_minutes' => 10,
        ],

        'whatsapp_otp' => [
            'enabled' => false,
            'max_attempts' => 5,
            'resend_interval_minutes' => 5,
            'expiry_minutes' => 10,
        ],

        'two_factor' => [
            'enabled' => false,
            'max_attempts' => 5,
            'expiry_minutes' => 10,
            'window' => 1,
        ],

        'mac_device' => [
            'enabled' => false,
            'header_device_id' => 'X-Device-Id',
            'header_device_token' => 'X-Device-Token',
            'header_device_mac' => 'X-Device-MAC',
        ],

        'self_registration' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limits for OTP challenge issuance and verification.
    |
    */
    'rate_limits' => [
        'resend_per_minute' => 1,
        'max_consecutive_failures' => 5,
    ],

];
