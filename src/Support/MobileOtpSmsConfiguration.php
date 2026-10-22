<?php

namespace Parth\LaravelRbacSuite\Support;

class MobileOtpSmsConfiguration
{
    /**
     * @return array{valid: bool, message: string}
     */
    public static function validate(): array
    {
        $settings = MobileOtpSmsSettings::resolve();
        $url = $settings['url'];
        if ($url === '') {
            return [
                'valid' => false,
                'message' => 'SMS gateway URL is not configured.',
            ];
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'message' => 'SMS gateway URL is invalid.',
            ];
        }

        $token = $settings['token'];
        $username = $settings['username'];
        $password = $settings['password'];

        if ($token === '' && ($username === '' || $password === '')) {
            return [
                'valid' => false,
                'message' => 'Provide either SMS gateway token or username/password credentials.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'SMS gateway configuration is valid.',
        ];
    }
}
