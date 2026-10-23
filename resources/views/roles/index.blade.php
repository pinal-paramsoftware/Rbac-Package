@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@section('title', 'Roles')

@section('content')
    @component(rbac_view_name('common-components.breadcrumb'))
        @slot('pagetitle') Masters @endslot
        @slot('title') Roles @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center mb-3 p-3">
                    <h4 class="card-title mb-0">Roles List</h4>

                    @can('role-create')
                        <a href="{{ route('roles.create') }}" class="btn btn-primary">
                            <i class="uil uil-plus"></i> Create Role
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered" style="border-collapse: collapse; width: 100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th style="min-width: 400px;">Permissions</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $key => $role)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <span class="fw-bold">{{ $role->name }}</span>
                                        </td>

                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                @forelse($role->permissions as $permission)
                                                    <span class="badge bg-info">
                                                        {{ $permission->name }}
                                                    </span>
                                                @empty
                                                    <span class="badge bg-secondary">No Permissions</span>
                                                @endforelse
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            {{-- VIEW --}}
                                            @can('role-view')
                                                <button class="btn btn-sm btn-info me-1 btn-view-role" data-bs-toggle="modal"
                                                    data-bs-target="#roleViewModal" data-name="{{ $role->name }}"
                                                    data-permissions="{{ $role->permissions->pluck('name')->implode(', ') }}"
                                                    title="View Role">
                                                    <i class="uil uil-eye"></i>
                                                </button>
                                            @endcan

                                            {{-- EDIT --}}
                                            @can('role-edit')
                                                <a href="{{ route('roles.edit', Crypt::encrypt($role->id)) }}"
                                                    class="btn btn-sm btn-warning me-1" data-bs-toggle="tooltip"
                                                    title="Edit Role">
                                                    <i class="uil uil-edit"></i>
                                                </a>
                                            @endcan

                                            {{-- DELETE --}}
                                            @can('role-delete')
                                                @if (strtolower($role->name) === strtolower(config('rbac-suite.roles.admin_name', 'Admin')))
                                                    <button type="button" class="btn btn-sm btn-secondary" disabled
                                                        data-bs-toggle="tooltip" title="Admin role cannot be deleted">
                                                        <i class="uil uil-trash-alt"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete-role"
                                                        data-url="{{ route('roles.destroy', $role->id) }}"
                                                        data-name="{{ $role->name }}"
                                                        data-bs-toggle="tooltip"
                                                        title="Delete Role">
                                                        <i class="uil uil-trash-alt"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role View Modal -->
    <div class="modal fade" id="roleViewModal" tabindex="-1" aria-labelledby="roleViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Role Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Role Name:</th>
                            <td id="modalRoleName"></td>
                        </tr>
                        <tr>
                            <th>Permissions:</th>
                            <td id="modalPermissions"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
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

            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            $(document).on('click', '.btn-delete-role', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var deleteUrl = $btn.data('url');
                var roleName = $btn.data('name');
                var $row = $btn.closest('tr');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Delete role "' + roleName + '"? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#74788d',
                    confirmButtonText: 'Yes, delete it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            dataType: 'json',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    notificationToastCustom('success', response.message || 'Role deleted successfully.');
                                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#datatable')) {
                                        $('#datatable').DataTable().row($row).remove().draw(false);
                                    } else {
                                        $row.fadeOut(300, function() {
                                            $(this).remove();
                                        });
                                    }
                                } else {
                                    notificationToastCustom('error', response.message || 'Failed to delete role.');
                                }
                            },
                            error: function(xhr) {
                                var errorMsg = 'Failed to delete role.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                notificationToastCustom('error', errorMsg);
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.btn-view-role', function() {
                let name = this.dataset.name;
                let permissions = this.dataset.permissions || 'No Permissions';

                document.getElementById('modalRoleName').textContent = name;

                if (permissions === 'No Permissions') {
                    document.getElementById('modalPermissions').innerHTML =
                        '<span class="badge bg-secondary">No Permissions</span>';
                } else {
                    document.getElementById('modalPermissions').innerHTML =
                        renderPermissionsBadges(groupPermissions(permissions));
                }
            });
        });

        function groupPermissions(permissionString) {
            const grouped = {};

            permissionString.split(',').forEach(item => {
                item = item.trim();
                const parts = item.split('-');

                if (parts.length >= 2) {
                    const module = parts[0];
                    const action = parts.slice(1).join('-');

                    const moduleName = module.charAt(0).toUpperCase() + module.slice(1);

                    if (!grouped[moduleName]) {
                        grouped[moduleName] = [];
                    }

                    grouped[moduleName].push(action);
                }
            });

            return grouped;
        }

        function renderPermissionsBadges(groupedPermissions) {
            let html = '';

            Object.entries(groupedPermissions).forEach(([module, actions]) => {
                html += `<div class="mb-2">
                            <strong>${module}</strong><br>`;

                actions.forEach(action => {
                    html += `<span class="badge bg-info me-1 mb-1">${action}</span>`;
                });

                html += `</div>`;
            });

            return html;
        }
    </script>
@endsection
