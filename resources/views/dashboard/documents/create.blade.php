@extends('dashboard.layouts.app') @section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a class="text-decoration-none" href="{{ route('dashboard') }}"
                >Dashboard</a
            >
            /
            <a
                class="text-decoration-none"
                href="{{ route('dashboard.documents.index') }}"
                >Documents</a
            >
            /
            <span>Buat Dokumen</span>
        </h1>
    </div>

    <!-- Form Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Tambah Dokumen Baru
            </h6>
        </div>
        <div class="card-body">
            <form
                action="{{ route('dashboard.documents.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                {{-- Judul Dokumen --}}
                <div class="form-group mb-3">
                    <label for="title"
                        >Judul Dokumen <span class="text-danger">*</span></label
                    >
                    <input
                        type="text"
                        class="form-control @error('title') is-invalid @enderror"
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="Masukkan judul dokumen"
                        required
                    />
                    @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Category Document --}}
                <div class="form-group mb-3">
                    <label for="category_id"
                        >Kategori Dokumen
                        <span class="text-danger">*</span></label
                    >
                    <select
                        class="custom-select form-control @error('category_id') is-invalid @enderror"
                        id="category_id"
                        name="category_id"
                        value="{{ old('category_id') }}"
                        placeholder="Masukkan judul dokumen"
                        required
                    >
                        <option selected disabled value="">
                            Pilih Category..
                        </option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- File Dokumen --}}
                <div class="form-group mb-3">
                    <label for="file_path"
                        >Unggah File <span class="text-danger">*</span></label
                    >
                    <input
                        type="file"
                        class="form-control-file @error('file_path') is-invalid @enderror"
                        id="file_path"
                        name="file_path"
                        accept=".pdf,.doc,.docx"
                        required
                    />
                    <small class="text-muted"
                        >Format yang diperbolehkan: PDF, DOC, DOCX</small
                    >
                    @error('file_path')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Tombol Aksi --}}
                <div>
                    <a
                        href="{{ route('dashboard.documents.index') }}"
                        class="btn btn-secondary btn-icon-split"
                    >
                        <span class="icon text-white-50">
                            <i class="fas fa-arrow-left"></i>
                        </span>
                        <span class="text">Kembali</span>
                    </a>
                    <button
                        type="submit"
                        class="btn btn-primary btn-icon-split"
                    >
                        <span class="icon text-white-50">
                            <i class="fas fa-save"></i>
                        </span>
                        <span class="text">Simpan Dokumen</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
