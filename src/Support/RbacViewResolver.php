<?php

namespace Parth\LaravelRbacSuite\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

class RbacViewResolver
{
    /**
     * Resolve the active view name, prioritizing direct host application views
     * in resources/views/ over internal package views and legacy vendor views.
     *
     * Resolution order:
     * 1. Direct host view in resources/views/{cleanView} (e.g. 'auth.login')
     * 2. Namespaced package view 'rbac-suite::{cleanView}' (checks resources/views/vendor/rbac-suite/ then package internal)
     * 3. Fallback to passed view string
     */
    public static function resolve(string $view): string
    {
        // Normalize view name: strip package namespace prefix if present
        $cleanView = str_starts_with($view, 'rbac-suite::')
            ? substr($view, 12)
            : $view;

        // 1. Direct host application view in resources/views
        if (ViewFacade::exists($cleanView)) {
            return $cleanView;
        }

        // 2. Package namespace fallback (also covers legacy resources/views/vendor/rbac-suite)
        $namespaced = 'rbac-suite::'.$cleanView;
        if (ViewFacade::exists($namespaced)) {
            return $namespaced;
        }

        // 3. Fallback to original requested view
        return $view;
    }

    /**
     * Resolve the active master layout view name.
     *
     * Resolution order:
     * 1. Configured layout if it exists (e.g. 'layouts.master', 'layouts.app', 'rbac-suite::layouts.master')
     * 2. Fallback bundled package layout 'rbac-suite::layouts.master'
     */
    public static function resolveLayout(): string
    {
        $configured = (string) config('rbac-suite.layout', 'rbac-suite::layouts.master');

        if (! empty($configured) && ViewFacade::exists($configured)) {
            return $configured;
        }

        return 'rbac-suite::layouts.master';
    }

    /**
     * Render the resolved view instance.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public static function make(string $view, array $data = [], array $mergeData = []): View
    {
        return ViewFacade::make(static::resolve($view), $data, $mergeData);
    }
}
