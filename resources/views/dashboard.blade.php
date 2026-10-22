@extends(config('rbac-suite.layout', 'rbac-suite::layouts.master'))

@section('title') Dashboard @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Dashboard</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <h1 class="display-5 fw-bold text-primary mb-3">Welcome to RBAC</h1>
                <p class="lead text-muted mb-4">Enterprise Role-Based Access Control and Authentication Pipeline</p>
                <div class="d-inline-block alert alert-light border px-4 py-2">
                    <i class="uil-user-check text-success me-2"></i> Logged in as: <strong>{{ auth()->user()->email ?? auth()->user()->name ?? 'User' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @can('user-view')
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <i class="uil-user text-primary" style="font-size: 2.2rem;"></i>
                </div>
                <div>
                    <h4 class="mb-1 mt-1"><span class="badge bg-primary">Users</span></h4>
                    <p class="text-muted mb-0">User Accounts</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('master.user.index') }}" class="btn btn-sm btn-outline-primary mt-1">Manage Users &rarr;</a>
                </p>
            </div>
        </div>
    </div>
    @endcan

    @can('role-view')
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <i class="uil-shield-check text-success" style="font-size: 2.2rem;"></i>
                </div>
                <div>
                    <h4 class="mb-1 mt-1"><span class="badge bg-success">Roles</span></h4>
                    <p class="text-muted mb-0">Access Roles</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-success mt-1">Manage Roles &rarr;</a>
                </p>
            </div>
        </div>
    </div>
    @endcan

    @can('permission-view')
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <i class="uil-key-skeleton text-warning" style="font-size: 2.2rem;"></i>
                </div>
                <div>
                    <h4 class="mb-1 mt-1"><span class="badge bg-warning">Permissions</span></h4>
                    <p class="text-muted mb-0">Permission Matrix</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-outline-warning mt-1">Manage Permissions &rarr;</a>
                </p>
            </div>
        </div>
    </div>
    @endcan

    @can('auth_setting-view')
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="float-end mt-2">
                    <i class="uil-cog text-info" style="font-size: 2.2rem;"></i>
                </div>
                <div>
                    <h4 class="mb-1 mt-1"><span class="badge bg-info">Security</span></h4>
                    <p class="text-muted mb-0">Auth & Password</p>
                </div>
                <p class="text-muted mt-3 mb-0">
                    <a href="{{ route('auth.settings') }}" class="btn btn-sm btn-outline-info mt-1">Security Settings &rarr;</a>
                </p>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection
