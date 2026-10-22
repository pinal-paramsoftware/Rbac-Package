<?php

namespace App\Http\Controllers\Rbac;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\RootRedirectController as BaseRootRedirectController;

class RootRedirectController extends BaseRootRedirectController
{
    /**
     * Handle incoming request at application root ('/').
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        return parent::__invoke($request);
    }
}
