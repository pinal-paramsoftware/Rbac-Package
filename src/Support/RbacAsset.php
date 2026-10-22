<?php

namespace Parth\LaravelRbacSuite\Support;

class RbacAsset
{
    /**
     * Resolve the appropriate asset URL for package resources.
     */
    public static function url(string $path): string
    {
        $cleanPath = ltrim($path, '/');

        // 1. Check published package assets in public/vendor/rbac-suite/
        if (function_exists('public_path') && file_exists(public_path('vendor/rbac-suite/'.$cleanPath))) {
            return asset('vendor/rbac-suite/'.$cleanPath);
        }

        // 2. Check published package assets in public/vendor/rbac-suite/assets/ (subfolder layout)
        if (function_exists('public_path') && file_exists(public_path('vendor/rbac-suite/assets/'.$cleanPath))) {
            return asset('vendor/rbac-suite/assets/'.$cleanPath);
        }

        // 3. Fallback to existing host application assets in public/assets/
        if (function_exists('public_path') && file_exists(public_path('assets/'.$cleanPath))) {
            return asset('assets/'.$cleanPath);
        }

        // 4. Default to configured prefix
        $prefix = config('rbac-suite.assets_prefix', 'vendor/rbac-suite');

        return asset(rtrim($prefix, '/').'/'.$cleanPath);
    }
}
