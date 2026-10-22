<?php

namespace App\Http\Controllers\Rbac;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Parth\LaravelRbacSuite\Http\Controllers\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    /**
     * Display the RBAC administrative dashboard.
     *
     * Override this method to pass custom statistics, change the view,
     * or inject application-specific metrics.
     */
    public function index(Request $request): View
    {
        return parent::index($request);
    }
}
