<?php

use Parth\LaravelRbacSuite\Support\RbacAsset;
use Parth\LaravelRbacSuite\Support\RbacControllerResolver;
use Parth\LaravelRbacSuite\Support\RbacModelResolver;

if (! function_exists('rbac_asset')) {
    /**
     * Resolve the asset URL for RBAC suite assets with smart fallback.
     */
    function rbac_asset(string $path): string
    {
        return RbacAsset::url($path);
    }
}

if (! function_exists('rbac_controller')) {
    /**
     * Resolve the active RBAC controller class.
     */
    function rbac_controller(string $class): string
    {
        return RbacControllerResolver::resolve($class);
    }
}

if (! function_exists('rbac_model')) {
    /**
     * Resolve the active RBAC model class.
     */
    function rbac_model(string $key): string
    {
        return RbacModelResolver::resolve($key);
    }
}
