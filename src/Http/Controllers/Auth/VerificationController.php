<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;

class VerificationController extends BaseController
{
    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
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
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Show the email verification notice.
     *
     * @return RedirectResponse|View
     */
    public function show(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        return rbac_view('auth.verify');
    }
}
