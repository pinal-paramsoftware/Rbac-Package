<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends BaseController
{
    /**
     * Display the RBAC suite dashboard.
     */
    public function index(Request $request): View
    {
        return view('rbac-suite::dashboard');
    }
}
