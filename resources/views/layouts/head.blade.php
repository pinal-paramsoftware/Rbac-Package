@yield('css')
<!-- Bootstrap Css -->
<link href="{{ rbac_asset('css/bootstrap.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ rbac_asset('css/icons.css') }}" id="icons-style" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ rbac_asset('css/app.css') }}" id="app-style" rel="stylesheet" type="text/css" />
<link href="{{ rbac_asset('libs/toastr/toastr.min.css') }}" id="toastr-style" rel="stylesheet" type="text/css" />
<style>
    /* SUCCESS */
    .toast-success {
        background-color: #198754 !important; /* Bootstrap green */
        color: #ffffff !important;
    }

    /* ERROR */
    .toast-error {
        background-color: #dc3545 !important; /* Bootstrap red */
        color: #ffffff !important;
    }

    /* INFO */
    .toast-info {
        background-color: #0dcaf0 !important; /* Bootstrap info */
        color: #000000 !important;
    }

    /* WARNING */
    .toast-warning {
        background-color: #ffc107 !important; /* Bootstrap warning */
        color: #000000 !important;
    }

    /* Title text */
    .toast-title {
        font-weight: 600;
    }

    /* Message text */
    .toast-message {
        font-size: 14px;
    }

    /* Close button */
    .toast-close-button {
        color: #ffffff !important;
        opacity: 0.8;
    }
</style>
