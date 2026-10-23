<?php

namespace Parth\LaravelRbacSuite\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class RootRedirectController extends BaseController
{
    /**
     * Handle incoming request at application root ('/').
     *
     * - Guests are redirected to the login page ('/login').
     * - Authenticated users are redirected to the configured dashboard.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $guard = config('rbac-suite.guard', 'web');

        if (! Auth::guard($guard)->check()) {
            return Route::has('login') ? redirect()->route('login') : redirect('/login');
        }

        // 1. Check configured dashboard route
        $dashboardRoute = config('rbac-suite.routes.dashboard_route', 'dashboard');

        if (! empty($dashboardRoute) && Route::has($dashboardRoute)) {
            $dashboardUrl = route($dashboardRoute, [], false);
            // Prevent redirect loop if the dashboard route itself points to '/'
            if ($dashboardUrl !== '/' && $dashboardUrl !== '') {
                return redirect()->route($dashboardRoute);
            }
        }

        // 2. Check canonical dashboard route
        if (Route::has('dashboard')) {
            return redirect()->route('dashboard');
        }

        // 3. Fallback to resolved dashboard view if available
        $dashboardView = rbac_view_name('dashboard');
        if (view()->exists($dashboardView)) {
            return view($dashboardView);
        }

        // 4. Default safe fallback
        return Route::has('login') ? redirect()->route('login') : redirect('/login');
    }
}
