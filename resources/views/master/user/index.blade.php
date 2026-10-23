@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@section('title', 'User Master')

@section('css')
    <link href="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/libs/datatables/datatables.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .user-status-toggle {
            cursor: pointer;
            width: 2.25rem !important;
            height: 1.25rem !important;
        }
        .user-status-toggle:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
@endsection

@section('content')
    @component(rbac_view_name('common-components.breadcrumb'))
        @slot('pagetitle')
            Masters
        @endslot
        @slot('title')
            User Master
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h4 class="card-title mb-0">Users List</h4>

                    @can('user-create')
                        <a href="{{ route('master.user.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="uil uil-plus me-1"></i> Create User
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="user-master-table" class="table table-bordered table-striped dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User View Modal -->
    <div class="modal fade" id="userViewModal" tabindex="-1" aria-labelledby="userViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userViewModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userViewModalBody">
                    <div class="text-center py-4" id="userViewLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="userViewContent" style="display: none;">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr>
                                    <th width="30%">Name</th>
                                    <td id="viewUserName"></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td id="viewUserEmail"></td>
                                </tr>
                                <tr>
                                    <th>Mobile Number</th>
                                    <td id="viewUserMobile"></td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td id="viewUserRole"></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="viewUserStatus"></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td id="viewUserAddress"></td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td id="viewUserCreatedAt"></td>
                                </tr>
                                <tr>
                                    <th>Created By</th>
                                    <td id="viewUserCreatedBy"></td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td id="viewUserUpdatedAt"></td>
                                </tr>
                                <tr>
                                    <th>Updated By</th>
                                    <td id="viewUserUpdatedBy"></td>
                                </tr>
                                <tr>
                                    <th>Password Updated At</th>
                                    <td id="viewUserPasswordUpdatedAt"></td>
                                </tr>
                                <tr>
                                    <th>Password Updated By</th>
                                    <td id="viewUserPasswordUpdatedBy"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Sidebar (Offcanvas) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="changePasswordSidebar" aria-labelledby="changePasswordSidebarLabel" style="width: 420px;">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title mb-1" id="changePasswordSidebarLabel">
                    <i class="uil uil-key-skeleton me-1 text-primary"></i> Change Password
                </h5>
                <small class="text-muted">Target User: <strong id="changePasswordTargetName" class="text-dark"></strong></small>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div id="changePasswordAlert" class="alert alert-danger d-none" role="alert"></div>

            <form id="changePasswordForm" autocomplete="off">
                @csrf
                <input type="hidden" id="changePasswordUserId" name="user_id" value="">
                <input type="hidden" id="changePasswordUrl" value="">

                {{-- New Password --}}
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="new_password"
                               name="password"
                               placeholder="Enter new password"
                               required
                               autocomplete="new-password">
                        <button class="btn btn-light border btn-toggle-pwd" type="button" data-target="new_password">
                            <i class="uil uil-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block d-none" id="error_password"></div>
                </div>

                {{-- Confirm Password --}}
                <div class="mb-4">
                    <label for="new_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="new_password_confirmation"
                               name="password_confirmation"
                               placeholder="Confirm new password"
                               required
                               autocomplete="new-password">
                        <button class="btn btn-light border btn-toggle-pwd" type="button" data-target="new_password_confirmation">
                            <i class="uil uil-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block d-none" id="error_password_confirmation"></div>
                </div>

                <div class="alert alert-info py-2 mb-4" style="font-size: 13px;">
                    <i class="uil uil-info-circle me-1"></i> Passwords must satisfy the active password complexity criteria.
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="offcanvas">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary waves-effect waves-light" id="btnSubmitChangePassword">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="changePasswordSpinner" role="status"></span>
                        <i class="uil uil-check me-1" id="changePasswordIcon"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/libs/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset(config('rbac-suite.assets_prefix', 'vendor/rbac-suite') . '/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            if (typeof notificationToastCustom === 'function') {
                @if (session('success'))
                    notificationToastCustom('success', @json(session('success')));
                @endif

                @if (session('error'))
                    notificationToastCustom('error', @json(session('error')));
                @endif

                @if (session('warning'))
                    notificationToastCustom('warning', @json(session('warning')));
                @endif

                @if (session('info'))
                    notificationToastCustom('info', @json(session('info')));
                @endif
            }

            var table = $('#user-master-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('master.user.index') }}",
                    type: "GET"
                },
                order: [[0, 'desc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'Sr. No.', orderable: false, searchable: false, width: '5%' },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'mobile_number', name: 'mobile_number' },
                    { data: 'role', name: 'roles.name', orderable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                drawCallback: function() {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // View User Modal
            $(document).on('click', '.btn-view-user', function() {
                var userId = $(this).data('id');
                var modal = new bootstrap.Modal(document.getElementById('userViewModal'));
                $('#userViewLoading').show();
                $('#userViewContent').hide();
                modal.show();

                $.ajax({
                    url: "{{ route('master.user.index') }}/" + userId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success && response.data) {
                            var d = response.data;
                            $('#viewUserName').text(d.name || '-');
                            $('#viewUserEmail').text(d.email || '-');
                            $('#viewUserMobile').text(d.mobile_number || '-');

                            var rolesHtml = '<span class="badge bg-secondary">No Role</span>';
                            if (d.roles && d.roles.length > 0) {
                                rolesHtml = d.roles.map(function(r) {
                                    return '<span class="badge bg-info me-1">' + r + '</span>';
                                }).join(' ');
                            }
                            $('#viewUserRole').html(rolesHtml);

                            var statusHtml = '<span class="badge bg-danger">Inactive</span>';
                            if (d.status === 'Active') {
                                statusHtml = '<span class="badge bg-success">Active</span>';
                            } else if (d.status === 'Deleted') {
                                statusHtml = '<span class="badge bg-secondary">Deleted</span>';
                            }
                            $('#viewUserStatus').html(statusHtml);

                            $('#viewUserAddress').text(d.address || '-');
                            $('#viewUserCreatedAt').text(d.created_at || '-');
                            $('#viewUserCreatedBy').text(d.created_by || '-');
                            $('#viewUserUpdatedAt').text(d.updated_at || '-');
                            $('#viewUserUpdatedBy').text(d.updated_by || '-');
                            $('#viewUserPasswordUpdatedAt').text(d.password_updated_at || '-');
                            $('#viewUserPasswordUpdatedBy').text(d.password_updated_by || '-');

                            $('#userViewLoading').hide();
                            $('#userViewContent').show();
                        }
                    },
                    error: function() {
                        $('#userViewLoading').hide();
                        $('#userViewContent').html('<div class="text-danger p-3 text-center">Failed to load user details.</div>').show();
                        if (typeof notificationToastCustom === 'function') {
                            notificationToastCustom('error', 'Failed to load user details.');
                        }
                    }
                });
            });

            // Change Password Sidebar Open
            $(document).on('click', '.btn-change-password', function() {
                var userId = $(this).data('id');
                var userName = $(this).data('name');
                var userEmail = $(this).data('email');
                var url = $(this).data('url');

                $('#changePasswordUserId').val(userId);
                $('#changePasswordUrl').val(url);
                $('#changePasswordTargetName').text(userName + ' (' + userEmail + ')');

                // Reset fields & errors
                $('#new_password').val('').removeClass('is-invalid').attr('type', 'password');
                $('#new_password_confirmation').val('').removeClass('is-invalid').attr('type', 'password');
                $('.btn-toggle-pwd i').removeClass('uil-eye-slash').addClass('uil-eye');
                $('#error_password').addClass('d-none').text('');
                $('#error_password_confirmation').addClass('d-none').text('');
                $('#changePasswordAlert').addClass('d-none').text('');

                var offcanvasEl = document.getElementById('changePasswordSidebar');
                var offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
                offcanvas.show();
            });

            // Reset Change Password fields when sidebar hidden
            $('#changePasswordSidebar').on('hidden.bs.offcanvas', function () {
                $('#new_password').val('').removeClass('is-invalid').attr('type', 'password');
                $('#new_password_confirmation').val('').removeClass('is-invalid').attr('type', 'password');
                $('.btn-toggle-pwd i').removeClass('uil-eye-slash').addClass('uil-eye');
                $('#error_password').addClass('d-none').text('');
                $('#error_password_confirmation').addClass('d-none').text('');
                $('#changePasswordAlert').addClass('d-none').text('');
                $('#changePasswordUserId').val('');
                $('#changePasswordUrl').val('');
            });

            // Show/Hide Password Toggle
            $(document).on('click', '.btn-toggle-pwd', function() {
                var targetId = $(this).data('target');
                var input = $('#' + targetId);
                var icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('uil-eye').addClass('uil-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('uil-eye-slash').addClass('uil-eye');
                }
            });

            // Submit Change Password Form via AJAX
            $('#changePasswordForm').on('submit', function(e) {
                e.preventDefault();

                var url = $('#changePasswordUrl').val();
                var password = $('#new_password').val();
                var confirmation = $('#new_password_confirmation').val();

                if (!password) {
                    $('#new_password').addClass('is-invalid');
                    $('#error_password').removeClass('d-none').text('Please enter a new password.');
                    return;
                }

                if (password !== confirmation) {
                    $('#new_password_confirmation').addClass('is-invalid');
                    $('#error_password_confirmation').removeClass('d-none').text('The password confirmation does not match.');
                    return;
                }

                var $btn = $('#btnSubmitChangePassword');
                $btn.prop('disabled', true);
                $('#changePasswordSpinner').removeClass('d-none');
                $('#changePasswordIcon').addClass('d-none');

                $('#new_password').removeClass('is-invalid');
                $('#new_password_confirmation').removeClass('is-invalid');
                $('#error_password').addClass('d-none').text('');
                $('#error_password_confirmation').addClass('d-none').text('');
                $('#changePasswordAlert').addClass('d-none').text('');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        password: password,
                        password_confirmation: confirmation
                    },
                    dataType: 'json',
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $('#changePasswordSpinner').addClass('d-none');
                        $('#changePasswordIcon').removeClass('d-none');

                        if (response.success) {
                            var offcanvasEl = document.getElementById('changePasswordSidebar');
                            var offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                            if (offcanvas) {
                                offcanvas.hide();
                            }

                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('success', response.message);
                            }
                        } else {
                            var errMsg = response.message || 'Failed to update password.';
                            $('#changePasswordAlert').removeClass('d-none').text(errMsg);
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('error', errMsg);
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false);
                        $('#changePasswordSpinner').addClass('d-none');
                        $('#changePasswordIcon').removeClass('d-none');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            var firstError = null;
                            if (errors.password) {
                                $('#new_password').addClass('is-invalid');
                                $('#error_password').removeClass('d-none').text(errors.password[0]);
                                if (!firstError) firstError = errors.password[0];
                            }
                            if (errors.password_confirmation) {
                                $('#new_password_confirmation').addClass('is-invalid');
                                $('#error_password_confirmation').removeClass('d-none').text(errors.password_confirmation[0]);
                                if (!firstError) firstError = errors.password_confirmation[0];
                            }
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('warning', firstError || 'Please correct the validation errors.');
                            }
                        } else {
                            var msg = 'Failed to update password.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            $('#changePasswordAlert').removeClass('d-none').text(msg);
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('error', msg);
                            }
                        }
                    }
                });
            });

            // Delete User Confirmation
            $(document).on('click', '.btn-delete-user', function() {
                var deleteUrl = $(this).data('url');
                var userName = $(this).data('name');

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete user "' + userName + '"? This will soft-delete the user record.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#74788d',
                        confirmButtonText: 'Yes, delete it!'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            executeDeleteUser(deleteUrl);
                        }
                    });
                } else if (confirm('Are you sure you want to delete user "' + userName + '"?')) {
                    executeDeleteUser(deleteUrl);
                }
            });

            function executeDeleteUser(deleteUrl) {
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('success', response.message || 'User deleted successfully.');
                            }
                            table.ajax.reload(null, false);
                        } else {
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('error', response.message || 'Failed to delete user.');
                            }
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Failed to delete user.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (typeof notificationToastCustom === 'function') {
                            notificationToastCustom('error', msg);
                        }
                    }
                });
            }

            // Active/Inactive Toggle Change Handler
            $(document).on('change', '.user-status-toggle', function(e) {
                var $toggle = $(this);
                var userId = $toggle.data('id');
                var userName = $toggle.data('name') || 'this user';
                var url = $toggle.data('url');
                var isChecked = $toggle.is(':checked');
                var targetStatus = isChecked ? '1' : '0';
                var $badge = $('.user-status-badge-' + userId);

                if (!isChecked) {
                    if (typeof Swal !== 'undefined') {
                        // Confirmation dialog before deactivation
                        Swal.fire({
                            title: 'Deactivate User?',
                            text: 'Are you sure you want to deactivate "' + userName + '"? They will not be able to log in while inactive.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#f46a6a',
                            cancelButtonColor: '#74788d',
                            confirmButtonText: 'Yes, deactivate',
                            cancelButtonText: 'Cancel'
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                sendToggleStatusRequest($toggle, userId, url, targetStatus, $badge);
                            } else {
                                // User cancelled: revert checkbox back to checked (ON)
                                $toggle.prop('checked', true);
                            }
                        });
                    } else if (confirm('Are you sure you want to deactivate "' + userName + '"?')) {
                        sendToggleStatusRequest($toggle, userId, url, targetStatus, $badge);
                    } else {
                        $toggle.prop('checked', true);
                    }
                } else {
                    // Activating: execute directly
                    sendToggleStatusRequest($toggle, userId, url, targetStatus, $badge);
                }
            });

            function sendToggleStatusRequest($toggle, userId, url, targetStatus, $badge) {
                // Prevent duplicate requests & indicate loading state
                $toggle.prop('disabled', true);
                $badge.addClass('opacity-50');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        is_active: targetStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        $toggle.prop('disabled', false);
                        $badge.removeClass('opacity-50');

                        if (response && response.success) {
                            var isActive = (String(response.is_active) === '1');
                            $toggle.prop('checked', isActive);

                            if (isActive) {
                                $badge.removeClass('bg-danger').addClass('bg-success').text('Active');
                            } else {
                                $badge.removeClass('bg-success').addClass('bg-danger').text('Inactive');
                            }

                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('success', response.message);
                            }
                        } else {
                            // Restore previous state
                            revertToggleState($toggle, targetStatus, $badge);
                            if (typeof notificationToastCustom === 'function') {
                                notificationToastCustom('error', (response && response.message) ? response.message : 'Failed to update user status.');
                            }
                        }
                    },
                    error: function(xhr) {
                        $toggle.prop('disabled', false);
                        $badge.removeClass('opacity-50');

                        // Restore previous state
                        revertToggleState($toggle, targetStatus, $badge);

                        var errorMsg = 'Failed to update user status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.is_active) {
                            errorMsg = xhr.responseJSON.errors.is_active[0];
                        }

                        if (typeof notificationToastCustom === 'function') {
                            notificationToastCustom('error', errorMsg);
                        }
                    }
                });
            }

            function revertToggleState($toggle, targetStatus, $badge) {
                var previousIsActive = (targetStatus !== '1');
                $toggle.prop('checked', previousIsActive);
                if (previousIsActive) {
                    $badge.removeClass('bg-danger').addClass('bg-success').text('Active');
                } else {
                    $badge.removeClass('bg-success').addClass('bg-danger').text('Inactive');
                }
            }
        });
    </script>
@endsection
