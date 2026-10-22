@extends(\Parth\LaravelRbacSuite\RbacSuite::getLayout())

@section('title')
    Create Permission
@endsection

@section('content')
    @component('rbac-suite::common-components.breadcrumb')
        @slot('pagetitle')
            Masters
        @endslot
        @slot('title')
            Create Permission
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Create New Permission</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('permissions.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Module/Form Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" placeholder="e.g., Dashboard, Users, Reports"
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Enter the name of the module or form (e.g., Dashboard)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permissions <span class="text-danger">*</span></label>
                            <div id="operations-container">
                                @if(old('operations'))
                                    @foreach(old('operations') as $index => $operation)
                                        <div class="input-group mb-2 operation-row">
                                            <input type="text" class="form-control @error('operations.' . $index) is-invalid @enderror"
                                                   name="operations[]" placeholder="e.g., view, create, edit, delete, export"
                                                   value="{{ $operation }}">
                                            <button type="button" class="btn btn-outline-danger remove-operation" title="Remove">
                                                <i class="uil uil-trash-alt"></i>
                                            </button>
                                            @error('operations.' . $index)
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 operation-row">
                                        <input type="text" class="form-control" name="operations[]"
                                               placeholder="e.g., view, create, edit, delete, export" value="">
                                        <button type="button" class="btn btn-outline-danger remove-operation d-none" title="Remove">
                                            <i class="uil uil-trash-alt"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            @error('operations')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-operation">
                                <i class="uil uil-plus"></i> Add Permission
                            </button>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="uil uil-check"></i> Create Permissions
                            </button>
                            <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                                <i class="uil uil-arrow-left"></i> Cancel
                            </a>
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
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    if (typeof notificationToastCustom === 'function') {
                        notificationToastCustom('error', @json($error));
                    }
                @endforeach
            @endif

            @if (session('error'))
                if (typeof notificationToastCustom === 'function') {
                    notificationToastCustom('error', @json(session('error')));
                }
            @endif

            @if (session('success'))
                if (typeof notificationToastCustom === 'function') {
                    notificationToastCustom('success', @json(session('success')));
                }
            @endif

            const container = document.getElementById('operations-container');
            const addBtn = document.getElementById('add-operation');

            if (addBtn && container) {
                addBtn.addEventListener('click', function() {
                    const newRow = document.createElement('div');
                    newRow.className = 'input-group mb-2 operation-row';
                    newRow.innerHTML = `
                        <input type="text" class="form-control" name="operations[]"
                               placeholder="e.g., view, create, edit, delete, export" value="">
                        <button type="button" class="btn btn-outline-danger remove-operation" title="Remove">
                            <i class="uil uil-trash-alt"></i>
                        </button>
                    `;
                    container.appendChild(newRow);
                    updateRemoveButtons();
                });

                function updateRemoveButtons() {
                    const rows = container.querySelectorAll('.operation-row');
                    rows.forEach((row, index) => {
                        const removeBtn = row.querySelector('.remove-operation');
                        if (rows.length === 1) {
                            removeBtn.classList.add('d-none');
                        } else {
                            removeBtn.classList.remove('d-none');
                        }
                    });
                }

                container.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-operation')) {
                        e.target.closest('.operation-row').remove();
                        updateRemoveButtons();
                    }
                });

                updateRemoveButtons();
            }
        });
    </script>
@endsection
