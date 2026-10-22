<?php

namespace Parth\LaravelRbacSuite\Support;

class WhatsAppOtpConfiguration
{
    /**
     * @return array{valid: bool, message: string}
     */
    public static function validate(): array
    {
        WhatsAppGatewaySettings::apply();

        $settings = WhatsAppGatewaySettings::resolve();
        $url = $settings['url'];
        if ($url === '') {
            return [
                'valid' => false,
                'message' => 'WhatsApp gateway URL is not configured.',
            ];
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'message' => 'WhatsApp gateway URL is invalid.',
            ];
        }

        $token = $settings['token'];
        $username = $settings['username'];
        $password = $settings['password'];

        if ($token === '' && ($username === '' || $password === '')) {
            return [
                'valid' => false,
                'message' => 'Provide either WhatsApp gateway token or username/password credentials.',
            ];
        }

        $senderId = $settings['sender_id'];
        if ($senderId === '') {
            return [
                'valid' => false,
                'message' => 'WhatsApp sender ID is not configured.',
            ];
        }

        $template = $settings['template'];
        if ($template === '') {
            return [
                'valid' => false,
                'message' => 'WhatsApp template is not configured.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'WhatsApp gateway configuration is valid.',
        ];
    }
}
