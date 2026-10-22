<?php

namespace Parth\LaravelRbacSuite\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Parth\LaravelRbacSuite\Models\AuthSetting;
use Parth\LaravelRbacSuite\Models\UserDevice;
use Parth\LaravelRbacSuite\Services\DeviceAuthorizationService;
use Parth\LaravelRbacSuite\Tests\TestCase;

class DeviceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceAuthorizationService $deviceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deviceService = app(DeviceAuthorizationService::class);
        AuthSetting::ensureDefaultSettings();
    }

    public function test_authorized_device_verification_passes(): void
    {
        AuthSetting::where('AuthCode', AuthSetting::CODE_MAC_FUNCTIONALITY)->update(['IsEnabled' => 1]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Device User',
            'email' => 'device.user@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        $registered = $this->deviceService->registerDevice(
            $user,
            'MacBook Pro',
            'AA:BB:CC:DD:EE:FF',
            true
        );

        $device = $registered['device'];
        $plainToken = $registered['plain_token'];

        $request = Request::create('/login', 'POST', [], [], [], [
            'HTTP_X_DEVICE_ID' => $device->device_uuid,
            'HTTP_X_DEVICE_TOKEN' => $plainToken,
            'HTTP_X_DEVICE_MAC' => 'AA:BB:CC:DD:EE:FF',
        ]);

        $result = $this->deviceService->verifyDevice($request, $user);

        $this->assertTrue($result['authorized']);
        $this->assertNotNull($result['device']);
    }

    public function test_unregistered_or_invalid_device_rejected(): void
    {
        AuthSetting::where('AuthCode', AuthSetting::CODE_MAC_FUNCTIONALITY)->update(['IsEnabled' => 1]);

        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Device User 2',
            'email' => 'device.user2@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        // Request with missing or fake headers
        $request = Request::create('/login', 'POST', [], [], [], [
            'HTTP_X_DEVICE_ID' => 'fake-uuid',
            'HTTP_X_DEVICE_TOKEN' => 'fake-token',
            'HTTP_X_DEVICE_MAC' => '00:11:22:33:44:55',
        ]);

        $result = $this->deviceService->verifyDevice($request, $user);

        $this->assertFalse($result['authorized']);
        $this->assertEquals(DeviceAuthorizationService::DENIED_MESSAGE, $result['message']);
    }

    public function test_mac_address_normalization_and_validation(): void
    {
        $this->assertTrue($this->deviceService->isValidMac('00:1A:2B:3C:4D:5E'));
        $this->assertTrue($this->deviceService->isValidMac('00-1a-2b-3c-4d-5e'));
        $this->assertTrue($this->deviceService->isValidMac('001A2B3C4D5E'));
        $this->assertFalse($this->deviceService->isValidMac('invalid-mac-address'));

        $normalized1 = $this->deviceService->normalizeMac('00-1a-2b-3c-4d-5e');
        $normalized2 = $this->deviceService->normalizeMac('00:1A:2B:3C:4D:5E');
        $normalized3 = $this->deviceService->normalizeMac('001a2b3c4d5e');

        $this->assertEquals('00:1A:2B:3C:4D:5E', $normalized1);
        $this->assertEquals('00:1A:2B:3C:4D:5E', $normalized2);
        $this->assertEquals('00:1A:2B:3C:4D:5E', $normalized3);
    }

    public function test_device_revocation_and_reauthorization(): void
    {
        $userModel = config('rbac-suite.user_model', 'App\\Models\\User');
        $user = $userModel::create([
            'name' => 'Device User 3',
            'email' => 'device.user3@example.com',
            'password' => Hash::make('Password@123'),
            'is_active' => '1',
        ]);

        $registered = $this->deviceService->registerDevice($user, 'Work PC', '11:22:33:44:55:66', true);
        $deviceId = $registered['device']->id;

        $this->assertTrue($registered['device']->is_authorized);

        // Revoke
        $this->deviceService->revokeDevice($deviceId, $user->id);
        $this->assertFalse(UserDevice::find($deviceId)->is_authorized);

        // Re-authorize
        $this->deviceService->authorizeDevice($deviceId, $user->id);
        $this->assertTrue(UserDevice::find($deviceId)->is_authorized);
    }
}
