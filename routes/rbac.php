<?php

use Illuminate\Support\Facades\Route;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\ConfirmPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\ForgotPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\LoginController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\RegisterController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\ResetPasswordController;
use Parth\LaravelRbacSuite\Http\Controllers\Auth\VerificationController;
use Parth\LaravelRbacSuite\Http\Controllers\AuthSettingController;
use Parth\LaravelRbacSuite\Http\Controllers\DashboardController;
use Parth\LaravelRbacSuite\Http\Controllers\PermissionController;
use Parth\LaravelRbacSuite\Http\Controllers\RoleController;
use Parth\LaravelRbacSuite\Http\Controllers\RootRedirectController;
use Parth\LaravelRbacSuite\Http\Controllers\UserController;
use Parth\LaravelRbacSuite\Support\RbacControllerResolver;

/*
|--------------------------------------------------------------------------
| RBAC Suite & Authentication Pipeline Routes
|--------------------------------------------------------------------------
|
| Controllers are resolved dynamically through RbacControllerResolver.
| If you publish controllers (tag: rbac-suite-controllers), your customized
| classes under App\Http\Controllers\Rbac will automatically take precedence.
|
*/

// =========================================================================
// 1. PUBLIC AUTHENTICATION & MULTI-STAGE PIPELINE ROUTES
// =========================================================================

// Application Root ('/') - Directs guest to /login or authenticated user to dashboard
if (config('rbac-suite.routes.redirect_root', true)) {
    Route::get('/', RbacControllerResolver::resolve(RootRedirectController::class))->name('rbac.root');
}

// Controller resolution
$loginController = RbacControllerResolver::resolve(LoginController::class);
$forgotPasswordController = RbacControllerResolver::resolve(ForgotPasswordController::class);
$resetPasswordController = RbacControllerResolver::resolve(ResetPasswordController::class);
$confirmPasswordController = RbacControllerResolver::resolve(ConfirmPasswordController::class);
$verificationController = RbacControllerResolver::resolve(VerificationController::class);
$registerController = RbacControllerResolver::resolve(RegisterController::class);

// Login & Logout
Route::get('login', [$loginController, 'showLoginForm'])->name('login');
Route::post('login', [$loginController, 'login']);
Route::post('logout', [$loginController, 'logout'])->name('logout');

// Captcha Challenge
Route::get('captcha', [$loginController, 'generateCaptcha'])->name('captcha');

// Stage 2: Email OTP Verification
Route::get('otp-verify', [$loginController, 'showOtpForm'])->name('otp.form');
Route::post('otp-verify', [$loginController, 'verifyOtp'])->name('otp.verify');
Route::post('otp-resend', [$loginController, 'resendOtp'])->name('otp.resend');

// Stage 2: Mobile OTP Verification
Route::get('mobile-otp-verify', [$loginController, 'showMobileOtpForm'])->name('mobile.otp.form');
Route::post('mobile-otp-verify', [$loginController, 'verifyMobileOtp'])->name('mobile.otp.verify');
Route::post('mobile-otp-resend', [$loginController, 'resendMobileOtp'])->name('mobile.otp.resend');

// Stage 2: WhatsApp OTP Verification
Route::get('whatsapp-otp-verify', [$loginController, 'showWhatsAppOtpForm'])->name('whatsapp.otp.form');
Route::post('whatsapp-otp-verify', [$loginController, 'verifyWhatsAppOtp'])->name('whatsapp.otp.verify');
Route::post('whatsapp-otp-resend', [$loginController, 'resendWhatsAppOtp'])->name('whatsapp.otp.resend');

// Stage 2: Google Authenticator Two-Factor TOTP
Route::get('two-factor', [$loginController, 'showTwoFactorForm'])->name('twofa.form');
Route::get('two-factor-verify', [$loginController, 'showTwoFactorVerify'])->name('twofa.verify.form');
Route::post('two-factor', [$loginController, 'verifyTwoFactor'])->name('twofa.verify');

// Password Reset Routes
Route::get('password/reset', [$forgotPasswordController, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [$forgotPasswordController, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [$resetPasswordController, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [$resetPasswordController, 'reset'])->name('password.update');

// Password Confirmation Routes
Route::get('password/confirm', [$confirmPasswordController, 'showConfirmForm'])->name('password.confirm');
Route::post('password/confirm', [$confirmPasswordController, 'confirm']);

// Email Verification Routes
Route::get('email/verify', [$verificationController, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [$verificationController, 'verify'])->name('verification.verify');
Route::post('email/resend', [$verificationController, 'resend'])->name('verification.resend');

// Registration Routes
Route::get('register', [$registerController, 'showRegistrationForm'])->name('register');
Route::post('register', [$registerController, 'register']);

// =========================================================================
// 2. CORE RBAC & SETTINGS PROTECTED ROUTES (AUTH MIDDLEWARE & GATE CHECKS)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // Main Dashboard Route
    Route::get('dashboard', [RbacControllerResolver::resolve(DashboardController::class), 'index'])->name('dashboard');

    // Authentication & Password Settings (Admin Console)
    $authSettingController = RbacControllerResolver::resolve(AuthSettingController::class);
    Route::prefix('AuthPasswordSetting')->group(function () use ($authSettingController) {
        Route::get('/', [$authSettingController, 'index'])->name('auth.settings');

        // AJAX Settings Endpoints
        Route::get('/GetAuthenticationSettings', [$authSettingController, 'getAuthenticationSettings'])->name('getAuthenticationSettings');
        Route::post('/UpdateAuthenticationSettings', [$authSettingController, 'updateAuthenticationSettings'])->name('updateAuthenticationSettings');

        Route::get('/GetSmtpSettings', [$authSettingController, 'getSmtpSettings'])->name('getSmtpSettings');
        Route::post('/UpdateSmtpSettings', [$authSettingController, 'updateSmtpSettings'])->name('updateSmtpSettings');

        Route::get('/GetEmailSettings', [$authSettingController, 'getSmtpSettings'])->name('getEmailSettings');
        Route::post('/UpdateEmailSettings', [$authSettingController, 'updateEmailSettings'])->name('updateEmailSettings');

        Route::post('/TestEmail', [$authSettingController, 'sendTestEmail'])->name('testEmail');
        Route::post('/SendTestEmail', [$authSettingController, 'sendTestEmail'])->name('sendTestEmail');

        Route::get('/GetTwoFactorSettings', [$authSettingController, 'getTwoFactorSettings'])->name('getTwoFactorSettings');
        Route::post('/UpdateTwoFactorSettings', [$authSettingController, 'updateTwoFactorSettings'])->name('updateTwoFactorSettings');

        Route::get('/GetWhatsAppGatewaySettings', [$authSettingController, 'getWhatsAppGatewaySettings'])->name('getWhatsAppGatewaySettings');
        Route::post('/UpdateWhatsAppGatewaySettings', [$authSettingController, 'updateWhatsAppGatewaySettings'])->name('updateWhatsAppGatewaySettings');

        Route::get('/GetMobileOtpGatewaySettings', [$authSettingController, 'getMobileOtpGatewaySettings'])->name('getMobileOtpGatewaySettings');
        Route::post('/UpdateMobileOtpGatewaySettings', [$authSettingController, 'updateMobileOtpGatewaySettings'])->name('updateMobileOtpGatewaySettings');

        Route::get('/GetSmsGatewaySettings', [$authSettingController, 'getSmsGatewaySettings'])->name('getSmsGatewaySettings');
        Route::post('/UpdateSmsGatewaySettings', [$authSettingController, 'updateSmsGatewaySettings'])->name('updateSmsGatewaySettings');

        Route::get('/GetCaptchaSettings', [$authSettingController, 'getCaptchaSettings'])->name('getCaptchaSettings');
        Route::post('/UpdateCaptchaSettings', [$authSettingController, 'updateCaptchaSettings'])->name('updateCaptchaSettings');

        Route::get('/GetPasswordRules', [$authSettingController, 'getPasswordRules'])->name('getPasswordRules');
        Route::post('/UpdatePasswordRules', [$authSettingController, 'updatePasswordRules'])->name('updatePasswordRules');

        Route::get('/GetRegistrationSettings', [$authSettingController, 'getRegistrationSettings'])->name('getRegistrationSettings');
        Route::post('/UpdateRegistrationSettings', [$authSettingController, 'updateRegistrationSettings'])->name('updateRegistrationSettings');

        Route::get('/GetUserMacSettings', [$authSettingController, 'getUserMacSettings'])->name('getUserMacSettings');
        Route::post('/UpdateUserMacSetting', [$authSettingController, 'updateUserMacSetting'])->name('updateUserMacSetting');
        Route::match(['post', 'delete'], '/DeleteUserMacSetting', [$authSettingController, 'deleteUserMacSetting'])->name('deleteUserMacSetting');
    });

    // Role Management Routes
    $roleController = RbacControllerResolver::resolve(RoleController::class);
    Route::get('roles', [$roleController, 'index'])->middleware('can:role-view')->name('roles.index');
    Route::get('roles/create', [$roleController, 'create'])->middleware('can:role-create')->name('roles.create');
    Route::post('roles', [$roleController, 'store'])->middleware('can:role-create')->name('roles.store');
    Route::get('roles/{id}', [$roleController, 'show'])->middleware('can:role-view')->name('roles.show');
    Route::get('roles/{id}/edit', [$roleController, 'edit'])->middleware('can:role-edit')->name('roles.edit');
    Route::put('roles/{id}', [$roleController, 'update'])->middleware('can:role-edit')->name('roles.update');
    Route::delete('roles/{id}', [$roleController, 'destroy'])->middleware('can:role-delete')->name('roles.destroy');

    // Permission Management Routes
    $permissionController = RbacControllerResolver::resolve(PermissionController::class);
    Route::get('permissions', [$permissionController, 'index'])->middleware('can:permission-view')->name('permissions.index');
    Route::get('permissions/create', [$permissionController, 'create'])->middleware('can:permission-create')->name('permissions.create');
    Route::post('permissions', [$permissionController, 'store'])->middleware('can:permission-create')->name('permissions.store');
    Route::get('permissions/{id}', [$permissionController, 'show'])->middleware('can:permission-view')->name('permissions.show');
    Route::get('permissions/{id}/edit', [$permissionController, 'edit'])->middleware('can:permission-edit')->name('permissions.edit');
    Route::put('permissions/{id}', [$permissionController, 'update'])->middleware('can:permission-edit')->name('permissions.update');
    Route::delete('permissions/{id}', [$permissionController, 'destroy'])->middleware('can:permission-delete')->name('permissions.destroy');

    // User Master Routes
    $userController = RbacControllerResolver::resolve(UserController::class);
    Route::prefix('master/user')->name('master.user.')->group(function () use ($userController) {
        Route::get('/', [$userController, 'index'])->middleware('can:user-view')->name('index');
        Route::get('/create', [$userController, 'create'])->middleware('can:user-create')->name('create');
        Route::post('/', [$userController, 'store'])->middleware('can:user-create')->name('store');
        Route::get('/{user}', [$userController, 'show'])->middleware('can:user-view')->name('show');
        Route::get('/{user}/edit', [$userController, 'edit'])->middleware('can:user-edit')->name('edit');
        Route::match(['put', 'patch'], '/{user}', [$userController, 'update'])->middleware('can:user-edit')->name('update');
        Route::delete('/{user}', [$userController, 'destroy'])->middleware('can:user-delete')->name('destroy');
        Route::post('/{user}/restore', [$userController, 'restore'])->middleware('can:user-delete')->name('restore');
        Route::post('/{user}/change-password', [$userController, 'changePassword'])->middleware('can:user-password-change')->name('change-password');
        Route::match(['post', 'patch'], '/{user}/toggle-status', [$userController, 'toggleStatus'])->middleware('can:user-edit')->name('toggle-status');
    });

});
