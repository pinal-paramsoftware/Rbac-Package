<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('rbac-suite::layouts.title-meta')
    @include('rbac-suite::layouts.head')
</head>

@section('body')

    <body class="authentication-bg">
    @show
    @yield('content')
    @include('rbac-suite::layouts.vendor-scripts')
</body>

</html>
