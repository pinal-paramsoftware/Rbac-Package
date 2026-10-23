@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@section('title', 'Role Details')

@section('content')
@component(rbac_view_name('common-components.breadcrumb'))
    @slot('pagetitle') Masters @endslot
    @slot('title') Role Details @endslot
@endcomponent

<div class="card">
    <div class="card-header bg-transparent border-bottom">
        <h4 class="card-title mb-0">Role: {{ $role->name }}</h4>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <h5>Assigned Permissions</h5>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @forelse($role->permissions as $permission)
                    <span class="badge bg-info font-size-13">{{ $permission->name }}</span>
                @empty
                    <span class="badge bg-secondary font-size-13">No Permissions Assigned</span>
                @endforelse
            </div>
        </div>

        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                <i class="uil uil-arrow-left"></i> Back to Roles
            </a>
            @can('role-edit')
                <a href="{{ route('roles.edit', Crypt::encrypt($role->id)) }}" class="btn btn-warning">
                    <i class="uil uil-edit"></i> Edit Role
                </a>
            @endcan
        </div>
    </div>
</div>
@endsection
