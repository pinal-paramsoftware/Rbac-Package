<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;
use Parth\LaravelRbacSuite\Models\PasswordRule;

class ResetPasswordController extends BaseController
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     */
    public function redirectTo(): string
    {
        $homeRoute = config('rbac-suite.routes.home', 'home');
        if (Route::has($homeRoute)) {
            return route($homeRoute);
        }

        return '/';
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  string|null  $token
     * @return Factory|View
     */
    public function showResetForm(Request $request, $token = null)
    {
        $view = view()->exists('rbac-suite::auth.passwords.reset')
            ? 'rbac-suite::auth.passwords.reset'
            : 'auth.passwords.reset';

        return view($view)->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => PasswordRule::getValidationRules(true),
        ];
    }
}
