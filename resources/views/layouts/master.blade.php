<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include(rbac_view_name('layouts.title-meta'))
    @include(rbac_view_name('layouts.head'))
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
        @include(rbac_view_name('layouts.topbar'))
        @include(rbac_view_name('layouts.sidebar'))

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include(rbac_view_name('layouts.footer'))
        </div>
    </div>

    <!-- Right Sidebar -->
    @include(rbac_view_name('layouts.right-sidebar'))

    <!-- JAVASCRIPT -->
    @include(rbac_view_name('layouts.vendor-scripts'))
</body>

</html>
