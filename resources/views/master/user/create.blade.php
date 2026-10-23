@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@php
    $isEditMode = (isset($isEdit) && $isEdit) || (isset($user) && $user->exists);
    $isTargetAdmin = $isEditMode && isset($user) && $user->isAdmin();
    $selectedRole = old('role', $selectedRole ?? ($user->roles->first()?->name ?? ''));
@endphp

@section('title', $isEditMode ? 'Edit User' : 'Create User')

@section('content')
    @component(rbac_view_name('common-components.breadcrumb'))
        @slot('pagetitle')
            User Master
        @endslot
        @slot('title')
            {{ $isEditMode ? 'Edit User' : 'Create User' }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent border-bottom">
                    <h4 class="card-title mb-0">{{ $isEditMode ? 'Edit User: ' . $user->name : 'Create New User' }}</h4>
                </div>

                <div class="card-body">
                    <form action="{{ $isEditMode ? route('master.user.update', $user->id) : route('master.user.store') }}" method="POST" autocomplete="off">
                        @csrf
                        @if ($isEditMode)
                            @method('PUT')
                        @endif

                        <div class="row">
                            {{-- Name --}}
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $user->name ?? '') }}"
                                       placeholder="Enter user full name"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', $user->email ?? '') }}"
                                       placeholder="Enter user email address"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            {{-- Mobile Number --}}
                            <div class="col-md-4 mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="text"
                                       class="form-control onlyNumber @error('mobile_number') is-invalid @enderror"
                                       id="mobile_number"
                                       name="mobile_number"
                                       value="{{ old('mobile_number', $user->mobile_number ?? '') }}"
                                       placeholder="Enter 10-digit mobile number"
                                       maxlength="15">
                                @error('mobile_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Optional: 10 digits starting 6-9, with optional +91.</small>
                            </div>

                            {{-- Role --}}
                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror"
                                        id="role"
                                        name="role"
                                        {{ $isTargetAdmin ? 'disabled' : 'required' }}>
                                    <option value="" disabled {{ $selectedRole === '' ? 'selected' : '' }}>Select Role</option>
                                    @foreach ($roles as $roleOption)
                                        <option value="{{ $roleOption->name }}" {{ $selectedRole === $roleOption->name ? 'selected' : '' }}>
                                            {{ $roleOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($isTargetAdmin)
                                    <small class="form-text text-muted">The Admin role cannot be modified.</small>
                                @endif
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Active Status --}}
                            <div class="col-md-4 mb-3">
                                <label for="is_active" class="form-label">Active Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('is_active') is-invalid @enderror"
                                        id="is_active"
                                        name="is_active"
                                        {{ $isTargetAdmin ? 'disabled' : 'required' }}>
                                    <option value="1" {{ old('is_active', (string) ($user->is_active ?? '1')) === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', (string) ($user->is_active ?? '1')) === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @if ($isTargetAdmin)
                                    <small class="form-text text-muted">Admin status cannot be modified.</small>
                                @endif
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            {{-- Address --}}
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror"
                                          id="address"
                                          name="address"
                                          rows="2"
                                          placeholder="Enter address (optional)">{{ old('address', $user->address ?? '') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="row">
                            {{-- Password --}}
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    Password {!! $isEditMode ? '<span class="text-muted fw-normal">(Leave blank to keep existing)</span>' : '<span class="text-danger">*</span>' !!}
                                </label>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       name="password"
                                       placeholder="{{ $isEditMode ? 'Enter new password if changing' : 'Enter password' }}"
                                       {{ $isEditMode ? '' : 'required' }}
                                       autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password Confirmation --}}
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">
                                    Confirm Password {!! $isEditMode ? '<span class="text-muted fw-normal">(Only if changing password)</span>' : '<span class="text-danger">*</span>' !!}
                                </label>
                                <input type="password"
                                       class="form-control @error('password_confirmation') is-invalid @enderror"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       placeholder="{{ $isEditMode ? 'Confirm new password' : 'Confirm password' }}"
                                       {{ $isEditMode ? '' : 'required' }}
                                       autocomplete="new-password">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Form Buttons --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('master.user.index') }}" class="btn btn-secondary waves-effect">
                                <i class="uil uil-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i class="uil uil-save me-1"></i> {{ $isEditMode ? 'Update User' : 'Save User' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            if (typeof notificationToastCustom === 'function') {
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
            }
        });
    </script>
@endsection
