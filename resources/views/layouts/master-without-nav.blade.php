<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include(rbac_view_name('layouts.title-meta'))
    @include(rbac_view_name('layouts.head'))
</head>

@section('body')

    <body class="authentication-bg">
    @show
    @yield('content')
    @include(rbac_view_name('layouts.vendor-scripts'))
</body>

</html>
