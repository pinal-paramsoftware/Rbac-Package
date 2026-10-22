<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('rbac-suite::layouts.title-meta')
    @include('rbac-suite::layouts.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('styles')

    <!-- DataTables -->
    <link href="{{ rbac_asset('libs/datatables/datatables.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ rbac_asset('libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
</head>

@section('body')
    <body>
@show

    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('rbac-suite::layouts.topbar')
        @include('rbac-suite::layouts.sidebar')

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include('rbac-suite::layouts.footer')
        </div>
    </div>

    <!-- Right Sidebar -->
    @include('rbac-suite::layouts.right-sidebar')

    <!-- JAVASCRIPT -->
    @include('rbac-suite::layouts.vendor-scripts')
</body>

</html>
