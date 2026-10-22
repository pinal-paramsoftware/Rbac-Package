<?php

namespace Parth\LaravelRbacSuite\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\UserDevice;

class DeviceAuthorizationService
{
    public const DENIED_MESSAGE = 'Login denied. The MAC address does not match an authorized device.';

    /**
     * Determine whether the registered-device / MAC restriction is enabled.
     */
    public function isRestrictionEnabled(): bool
    {
        return AuthSetting::query()
            ->where('AuthCode', AuthSetting::CODE_MAC_FUNCTIONALITY)
            ->where('IsEnabled', 1)
            ->where('IsActive', 1)
            ->exists();
    }

    /**
     * Verify whether the incoming request is from a trusted, registered device.
     *
     * @return array{authorized: bool, message: string, device: ?UserDevice}
     */
    public function verifyDevice(Request $request, Model $user): array
    {
        if (! $this->isRestrictionEnabled()) {
            return [
                'authorized' => true,
                'message' => 'Device restriction is disabled.',
                'device' => null,
            ];
        }

        $deviceId = $this->extractDeviceId($request);
        $deviceToken = $this->extractDeviceToken($request);
        $presentedMac = $this->extractDeviceMac($request);

        if ($deviceId === null || $deviceToken === null || $presentedMac === null) {
            return [
                'authorized' => false,
                'message' => self::DENIED_MESSAGE,
                'device' => null,
            ];
        }

        $device = UserDevice::query()
            ->where('user_id', $user->getKey())
            ->where('device_uuid', $deviceId)
            ->first();

        if ($device === null || ! $device->is_authorized || $device->device_mac === null) {
            return [
                'authorized' => false,
                'message' => self::DENIED_MESSAGE,
                'device' => $device,
            ];
        }

        $calculatedHash = hash('sha256', $deviceToken);
        if (! hash_equals($device->device_token_hash, $calculatedHash)) {
            return [
                'authorized' => false,
                'message' => self::DENIED_MESSAGE,
                'device' => $device,
            ];
        }

        $normalizedStoredMac = $this->normalizeMac($device->device_mac);
        $normalizedPresentedMac = $this->normalizeMac($presentedMac);

        if ($normalizedStoredMac === '' || $normalizedStoredMac !== $normalizedPresentedMac) {
            return [
                'authorized' => false,
                'message' => self::DENIED_MESSAGE,
                'device' => $device,
            ];
        }

        $device->update([
            'last_authenticated_at' => now(),
        ]);

        return [
            'authorized' => true,
            'message' => 'Device authorized successfully.',
            'device' => $device,
        ];
    }

    /**
     * Register a new trusted device for a user.
     *
     * @return array{device: UserDevice, plain_token: string}
     */
    public function registerDevice(Model $user, string $deviceName, ?string $deviceMac = null, bool $isAuthorized = true): array
    {
        $plainToken = Str::random(64);
        $deviceUuid = (string) Str::uuid();

        $device = UserDevice::query()->create([
            'user_id' => $user->getKey(),
            'device_uuid' => $deviceUuid,
            'device_name' => $deviceName,
            'device_mac' => $deviceMac !== null ? $this->normalizeMac($deviceMac) : null,
            'device_token_hash' => hash('sha256', $plainToken),
            'is_authorized' => $isAuthorized,
            'last_authenticated_at' => null,
        ]);

        return [
            'device' => $device,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * Revoke an authorized device.
     */
    public function revokeDevice(int $deviceId, ?int $userId = null): bool
    {
        $query = UserDevice::query()->where('id', $deviceId);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return (bool) $query->update(['is_authorized' => false]);
    }

    /**
     * Re-authorize a previously revoked device.
     */
    public function authorizeDevice(int $deviceId, ?int $userId = null): bool
    {
        $query = UserDevice::query()->where('id', $deviceId);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return (bool) $query->update(['is_authorized' => true]);
    }

    /**
     * Extract the device UUID from headers or request body.
     */
    private function extractDeviceId(Request $request): ?string
    {
        $deviceId = $request->header('X-Device-Id')
            ?? $request->header('X-Device-UUID')
            ?? $request->input('device_id')
            ?? $request->input('device_uuid');

        if ($deviceId === null || trim((string) $deviceId) === '') {
            return null;
        }

        return trim((string) $deviceId);
    }

    /**
     * Extract the device authentication token from headers or request body.
     */
    private function extractDeviceToken(Request $request): ?string
    {
        $deviceToken = $request->header('X-Device-Token')
            ?? $request->input('device_token');

        if ($deviceToken === null || trim((string) $deviceToken) === '') {
            return null;
        }

        return trim((string) $deviceToken);
    }

    /**
     * Extract an optional presented MAC address for hardware inventory audit matching.
     */
    private function extractDeviceMac(Request $request): ?string
    {
        $mac = $request->header('X-Device-MAC')
            ?? $request->header('X-MAC-Address')
            ?? $request->input('device_mac')
            ?? $request->input('mac_address');

        if ($mac === null || trim((string) $mac) === '') {
            return null;
        }

        return trim((string) $mac);
    }

    /**
     * Determine whether a string matches a valid MAC address format.
     */
    public function isValidMac(string $mac): bool
    {
        $trimmed = trim($mac);
        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $trimmed)) {
            return true;
        }

        if (preg_match('/^([0-9A-Fa-f]{4}\.){2}[0-9A-Fa-f]{4}$/', $trimmed)) {
            return true;
        }

        if (preg_match('/^[0-9A-Fa-f]{12}$/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a MAC address to uppercase hexadecimal with colon separators.
     */
    public function normalizeMac(string $mac): string
    {
        $clean = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        if (strlen($clean) === 12) {
            return implode(':', str_split($clean, 2));
        }

        return $clean;
    }
}
