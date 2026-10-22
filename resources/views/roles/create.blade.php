@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@section('title', 'Create Role')

@section('content')
@component('rbac-suite::common-components.breadcrumb')
    @slot('pagetitle') Masters @endslot
    @slot('title') Create Role @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-4">Create New Role</h4>

        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            {{-- ROLE NAME --}}
            <div class="mb-3 row">
                <label class="col-md-3 col-form-label text-end">
                    Role Name <span class="text-danger">*</span>
                </label>
                <div class="col-md-9">
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="Enter role name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- PERMISSION TABLE --}}
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Module</th>
                            <th>
                                All <br>
                                <input type="checkbox" id="checkAll">
                            </th>
                            @foreach ($operations as $operation)
                                <th class="text-capitalize">{{ $operation }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($permissions as $module => $perms)
                            <tr>
                                <td class="text-start fw-bold text-capitalize">
                                    {{ $module }}
                                </td>

                                <td>
                                    <input type="checkbox"
                                        class="row-check"
                                        data-row="{{ $module }}">
                                </td>

                                @foreach ($operations as $action)
                                    <td>
                                        @php
                                            $permName = $module . '-' . $action;
                                            $permission = $perms->firstWhere('name', $permName);
                                        @endphp

                                        @if ($permission)
                                            <input type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permName }}"
                                                class="perm-checkbox row-{{ $module }}">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- BUTTONS --}}
            <div class="mt-4">
                <button class="btn btn-primary">
                    <i class="uil uil-save"></i> Save Role
                </button>
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    Back
                </a>
            </div>

        </form>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                notificationToastCustom('error', @json($error));
            @endforeach
        @endif

        @if (session('error'))
            notificationToastCustom('error', @json(session('error')));
        @endif

        @if (session('success'))
            notificationToastCustom('success', @json(session('success')));
        @endif
    });

    // GLOBAL SELECT ALL
    document.getElementById('checkAll').addEventListener('change', function () {
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // ROW SELECT
    document.querySelectorAll('.row-check').forEach(rowCheck => {
        rowCheck.addEventListener('change', function () {
            let row = this.dataset.row;
            document.querySelectorAll('.row-' + row).forEach(cb => {
                cb.checked = this.checked;
            });
        });
    });
</script>
@endsection
