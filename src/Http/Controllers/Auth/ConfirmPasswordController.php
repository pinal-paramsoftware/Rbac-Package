<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ConfirmsPasswords;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;

class ConfirmPasswordController extends BaseController
{
    use ConfirmsPasswords;

    /**
     * Where to redirect users when the intended url fails.
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the password confirmation view.
     *
     * @return View
     */
    public function showConfirmForm()
    {
        return rbac_view('auth.passwords.confirm');
    }
}
