@extends('dashboard.layouts.app') @section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a
                href="{{ route('dashboard.documents.index') }}"
                class="text-decoration-none"
                >Dokumen</a
            >
            / Edit Dokumen
        </h1>
    </div>

    <!-- Form Edit -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form
                action="{{ route('dashboard.documents.update', $document->id) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf @method('PUT')

                <div class="form-group mb-3">
                    <label for="title" class="form-label">Judul Dokumen</label>
                    <input
                        type="text"
                        class="form-control @error('title') is-invalid @enderror"
                        id="title"
                        name="title"
                        value="{{ old('title', $document->title) }}"
                        required
                    />
                    @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-3">
                    <label for="file_path" class="form-label"
                        >Ganti File (Opsional)</label
                    >
                    <input
                        type="file"
                        class="form-control @error('file_path') is-invalid @enderror"
                        id="file_path"
                        name="file_path"
                        accept=".pdf,.doc,.docx"
                    />
                    @error('file_path')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror @if ($document->file_path)
                    <p class="mt-2">
                        <a
                            href="{{ asset('storage/' . $document->file_path) }}"
                            target="_blank"
                        >
                            Lihat File Saat Ini
                        </a>
                    </p>
                    @endif
                </div>

                <div class="form-group mb-3">
                    <label for="status" class="form-label"
                        >Status Dokumen</label
                    >
                    <select
                        class="form-control @error('status') is-invalid @enderror"
                        id="status"
                        name="status"
                    >
                        <option value="uploaded" {{ $document->
                            status == 'uploaded' ? 'selected' : '' }}>Uploaded
                        </option>
                        <option value="checked" {{ $document->
                            status == 'checked' ? 'selected' : '' }}>Checked
                        </option>
                        <option value="in_approval" {{ $document->
                            status == 'in_approval' ? 'selected' : '' }}>In
                            Approval
                        </option>
                        <option value="signed" {{ $document->
                            status == 'signed' ? 'selected' : '' }}>Signed
                        </option>
                        <option value="archived" {{ $document->
                            status == 'archived' ? 'selected' : '' }}>Archived
                        </option>
                    </select>
                    @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <a
                        href="{{ route('dashboard.documents.index') }}"
                        class="btn btn-secondary"
                    >
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
