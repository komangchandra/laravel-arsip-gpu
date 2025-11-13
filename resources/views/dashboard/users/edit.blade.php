@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a> /
            <a href="{{ route('dashboard.users.index') }}" class="text-decoration-none">Users</a> /
            <span>Edit User</span>
        </h1>
    </div>

    <!-- Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Edit User</h6>
            
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Terjadi kesalahan!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('dashboard.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label font-weight-bold">Nama</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label font-weight-bold">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="jabatan" class="form-label font-weight-bold">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $user->jabatan) }}" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label font-weight-bold">Password (kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="roles" class="form-label font-weight-bold">Role</label>
                        <select name="roles[]" id="roles" class="form-control" multiple>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" 
                                    {{ in_array($role, $userRoles) ? 'selected' : '' }}>
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Gunakan Ctrl (Windows) atau Cmd (Mac) untuk pilih lebih dari satu role.</small>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('dashboard.users.index') }}" class="btn btn-secondary btn-icon-split">
                        <span class="icon text-white-50">
                            <i class="fas fa-arrow-left"></i>
                        </span>
                        <span class="text">Batal</span>
                    </a>
                    <button type="submit" class="btn btn-primary btn-icon-split">
                        <span class="icon text-white-50">
                            <i class="fas fa-save"></i>
                        </span>
                        <span class="text">Update User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
