<?php

namespace Parth\LaravelRbacSuite\Support;

class EmailOtpMailConfiguration
{
    /**
     * @return array{valid: bool, message: string}
     */
    public static function validate(): array
    {
        EmailOtpSmtpSettings::apply();

        $defaultMailer = (string) config('mail.default');
        if ($defaultMailer === '') {
            return [
                'valid' => false,
                'message' => 'Default mailer is not configured. Please configure SMTP settings.',
            ];
        }

        $mailerConfig = config("mail.mailers.{$defaultMailer}");
        if (! is_array($mailerConfig)) {
            return [
                'valid' => false,
                'message' => "Mail configuration for [{$defaultMailer}] is missing.",
            ];
        }

        $transport = (string) ($mailerConfig['transport'] ?? '');
        if ($transport === '') {
            return [
                'valid' => false,
                'message' => 'Mail transport is not configured for the selected mailer.',
            ];
        }

        if (! app()->environment('testing') && in_array($transport, ['array', 'log'], true)) {
            return [
                'valid' => false,
                'message' => 'Email OTP requires a real mail transport (smtp, ses, mailgun, postmark, or sendmail).',
            ];
        }

        $fromAddress = (string) config('mail.from.address');
        if ($fromAddress === '') {
            return [
                'valid' => false,
                'message' => 'MAIL_FROM_ADDRESS must be configured before enabling Email OTP.',
            ];
        }

        if ($transport === 'smtp') {
            $host = (string) ($mailerConfig['host'] ?? '');
            $port = (int) ($mailerConfig['port'] ?? 0);

            if ($host === '' || $port <= 0) {
                return [
                    'valid' => false,
                    'message' => 'SMTP mailer requires MAIL_HOST and MAIL_PORT.',
                ];
            }
        }

        if ($transport === 'sendmail' && trim((string) ($mailerConfig['path'] ?? '')) === '') {
            return [
                'valid' => false,
                'message' => 'Sendmail transport requires a valid sendmail path.',
            ];
        }

        if ($transport === 'mailgun') {
            if ((string) config('services.mailgun.domain') === '' || (string) config('services.mailgun.secret') === '') {
                return [
                    'valid' => false,
                    'message' => 'Mailgun transport requires MAILGUN_DOMAIN and MAILGUN_SECRET.',
                ];
            }
        }

        if ($transport === 'postmark' && (string) config('services.postmark.token') === '') {
            return [
                'valid' => false,
                'message' => 'Postmark transport requires POSTMARK_TOKEN.',
            ];
        }

        if ($transport === 'ses') {
            if (
                (string) config('services.ses.key') === '' ||
                (string) config('services.ses.secret') === '' ||
                (string) config('services.ses.region') === ''
            ) {
                return [
                    'valid' => false,
                    'message' => 'SES transport requires AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_DEFAULT_REGION.',
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Email configuration is valid.',
        ];
    }
}
