<?php

namespace Parth\LaravelRbacSuite\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Parth\LaravelRbacSuite\Support\MobileOtpSmsSettings;

class MobileOtpSmsGatewayService
{
    /**
     * @return array{success: bool, error?: string}
     */
    public function sendOtp(string $mobileNumber, string $otp): array
    {
        $settings = MobileOtpSmsSettings::resolve();
        $gatewayUrl = $settings['url'];
        $username = $settings['username'];
        $password = $settings['password'];
        $token = $settings['token'];
        $senderId = $settings['sender_id'];
        $template = $settings['template'];

        $payload = array_filter([
            'to' => $mobileNumber,
            'message' => "Your OTP is {$otp}",
            'otp' => $otp,
            'template' => $template,
            'username' => $username,
            'password' => $password,
            'sender_id' => $senderId,
        ], static fn ($value) => $value !== null && $value !== '');

        $request = Http::connectTimeout($settings['timeout'])
            ->timeout($settings['timeout'])
            ->acceptJson()
            ->withOptions([
                'verify' => $settings['verify_ssl'],
            ]);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->asForm()->post($gatewayUrl, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('SMS gateway connection failed while sending OTP', [
                'mobile_number' => $this->maskPhone($mobileNumber),
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'network_error',
            ];
        }

        if (! $response->successful()) {
            Log::warning('SMS gateway request returned non-success status', [
                'mobile_number' => $this->maskPhone($mobileNumber),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'provider_error',
            ];
        }

        $body = $response->json();
        if (is_array($body)) {
            if (array_key_exists('success', $body) && ! ((bool) $body['success'])) {
                return [
                    'success' => false,
                    'error' => 'provider_error',
                ];
            }

            if (array_key_exists('status', $body) && strtolower((string) $body['status']) === 'failed') {
                return [
                    'success' => false,
                    'error' => 'provider_error',
                ];
            }
        }

        return [
            'success' => true,
        ];
    }

    private function maskPhone(string $mobileNumber): string
    {
        $digits = preg_replace('/\D+/', '', $mobileNumber) ?? '';
        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
