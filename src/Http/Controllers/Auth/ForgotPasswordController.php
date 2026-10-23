<?php

namespace Parth\LaravelRbacSuite\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\BaseController;

class ForgotPasswordController extends BaseController
{
    use SendsPasswordResetEmails;

    /**
     * Display the form to request a password reset link.
     *
     * @return View
     */
    public function showLinkRequestForm()
    {
        return rbac_view('auth.passwords.email');
    }
}
