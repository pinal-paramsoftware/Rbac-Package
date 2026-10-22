<?php

namespace Parth\LaravelRbacSuite\Support;

class CaptchaConfiguration
{
    /**
     * @return array{valid: bool, message: string}
     */
    public static function validate(): array
    {
        $settings = CaptchaSettings::resolve();

        if ($settings['url'] !== '') {
            if (! filter_var($settings['url'], FILTER_VALIDATE_URL)) {
                return [
                    'valid' => false,
                    'message' => 'Captcha provider URL is invalid.',
                ];
            }

            if ($settings['secret'] === '') {
                return [
                    'valid' => false,
                    'message' => 'Captcha provider credentials are incomplete.',
                ];
            }

            return [
                'valid' => true,
                'message' => 'Captcha provider configuration is valid.',
            ];
        }

        if (! extension_loaded('gd') || ! function_exists('imagecreate')) {
            return [
                'valid' => false,
                'message' => 'PHP GD extension is required for Captcha generation.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Captcha configuration is valid.',
        ];
    }
}
