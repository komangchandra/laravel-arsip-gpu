@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a class="text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a> /
            <a class="text-decoration-none" href="{{ route('dashboard.categories.index') }}">Categories</a> /
            <span>Buat Kategori</span>
        </h1>
    </div>

    <!-- Form Create Category -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Tambah Kategori Baru</h6>
            <a href="{{ route('dashboard.categories.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('dashboard.categories.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label font-weight-bold">Nama Kategori</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Masukkan nama kategori"
                        value="{{ old('name') }}"
                        required
                    />
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label font-weight-bold">Deskripsi (opsional)</label>
                    <textarea
                        name="description"
                        id="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3"
                        placeholder="Tulis deskripsi kategori jika ada..."
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
