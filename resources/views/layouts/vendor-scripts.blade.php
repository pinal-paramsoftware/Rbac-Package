<!-- JAVASCRIPT -->
<script src="{{ rbac_asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ rbac_asset('libs/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ rbac_asset('libs/metismenu/metismenu.min.js') }}"></script>
<script src="{{ rbac_asset('libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ rbac_asset('libs/node-waves/node-waves.min.js') }}"></script>
<script src="{{ rbac_asset('libs/waypoints/waypoints.min.js') }}"></script>
<script src="{{ rbac_asset('libs/jquery-counterup/jquery-counterup.min.js') }}"></script>
<script src="{{ rbac_asset('js/custom/toastr.min.js') }}"></script>
<script src="{{ rbac_asset('js/custom/notificationToastCustom.js') }}"></script>

<script src="{{ rbac_asset('libs/datatables/datatables.min.js') }}"></script>
<script src="{{ rbac_asset('libs/jszip/jszip.min.js') }}"></script>
<script src="{{ rbac_asset('libs/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ rbac_asset('js/pages/datatables.init.js') }}"></script>
<script src="{{ rbac_asset('libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ rbac_asset('libs/bootstrap-switch/bootstrap-switch.min.js') }}"></script>

<script>
    /* only Number Allowed */
    $(".onlyNumber").on("keypress keyup blur", function(event) {
        $(this).val($(this).val().replace(/[^\d].+/, ""));
        if ((event.which < 48 || event.which > 57)) {
            event.preventDefault();
        }
    });
</script>

@yield('script')

<!-- App js -->
<script src="{{ rbac_asset('js/app.min.js') }}"></script>

@yield('script-bottom')
