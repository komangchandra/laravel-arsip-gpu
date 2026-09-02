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
                @if ($document->routing_started_at)
                    <div class="alert alert-info">
                        Routing sudah dimulai. Judul dan kategori masih dapat diperbaiki, tetapi file dan status dokumen dikunci.
                    </div>
                @endif
                @php
                    $revisionRequest = $document->signRoutes
                        ->where('status', \App\Enums\SignRouteStatus::RevisionRequested)
                        ->sortByDesc('revision_requested_at')
                        ->first();
                @endphp
                @if ($revisionRequest)
                    <div class="alert alert-danger">
                        <div class="font-weight-bold mb-1">Catatan revisi dari {{ $revisionRequest->signer?->name ?? 'User tidak ditemukan' }}</div>
                        <div>{{ $revisionRequest->notes }}</div>
                        @if ($revisionRequest->revision_requested_at)
                            <div class="small mt-2">Diminta pada {{ $revisionRequest->revision_requested_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </div>
                @elseif ($document->status === \App\Enums\DocumentStatus::NeedsRevision)
                    <div class="alert alert-warning">Dokumen ditandai perlu revisi tanpa catatan dari signer.</div>
                @endif
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

                {{-- Category Document --}}
                <div class="form-group mb-3">
                    <label for="category_id">
                        Kategori Dokumen <span class="text-danger">*</span>
                    </label>
                    <select
                        class="custom-select form-select @error('category_id') is-invalid @enderror"
                        id="category_id"
                        name="category_id"
                        required
                    >
                        <option disabled value="">
                            Pilih Kategori..
                        </option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                {{ old('category_id', $document->category_id ?? '') == $category->id ? 'selected' : '' }}
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if (! $document->routing_started_at)
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
                            href="{{ route('dashboard.documents.preview', $document) }}"
                            target="_blank"
                        >
                            Lihat File Saat Ini
                        </a>
                    </p>
                    @endif
                </div>
                @endif

                <div class="form-group mb-3">
                    <label for="status" class="form-label">
                        Status Dokumen
                        @if ($document->routing_started_at)
                            <span class="badge badge-secondary">Dikunci selama routing</span>
                        @endif
                    </label>
                    <select class="custom-select form-control @error('status') is-invalid @enderror" id="status" name="status" @disabled($document->routing_started_at)>
                        <option value="uploaded" {{ $document->status->value === 'uploaded' ? 'selected' : '' }}>
                            Uploaded
                        </option>
                        <option value="routing" {{ $document->status->value === 'routing' ? 'selected' : '' }}>
                            Routing
                        </option>
                        <option value="waiting_for_signatures" {{ $document->status->value === 'waiting_for_signatures' ? 'selected' : '' }}>
                            Waiting for Signatures
                        </option>
                        <option value="needs_revision" {{ $document->status->value === 'needs_revision' ? 'selected' : '' }}>
                            Need Revision
                        </option>
                        <option value="ready_to_sign" {{ $document->status->value === 'ready_to_sign' ? 'selected' : '' }}>
                            Ready to Sign
                        </option>
                        <option value="signed" {{ $document->status->value === 'signed' ? 'selected' : '' }}>
                            Signed
                        </option>
                        <option value="archived" {{ $document->status->value === 'archived' ? 'selected' : '' }}>
                            Archived
                        </option>
                        <option value="cancelled" {{ $document->status->value === 'cancelled' ? 'selected' : '' }}>
                            Cancelled
                        </option>
                    </select>
                    @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <a href="{{ route('dashboard.documents.index') }}" class="btn btn-secondary btn-icon-split">
                        <span class="icon text-white-50">
                            <i class="fas fa-arrow-left"></i>
                        </span>
                        <span class="text">Batal</span>
                    </a>
                    @if (in_array($document->status, [\App\Enums\DocumentStatus::Routing, \App\Enums\DocumentStatus::WaitingForSignatures], true))
                        <button type="submit" name="action" value="request_revision" class="btn btn-danger btn-icon-split mr-2" onclick="return confirm('Routing akan dihentikan dan dokumen ditandai perlu revisi. Lanjutkan?')">
                            <span class="icon text-white-50"><i class="fas fa-undo"></i></span>
                            <span class="text">Tandai Perlu Revisi</span>
                        </button>
                    @endif
                    <button type="submit" name="action" value="save" class="btn btn-primary btn-icon-split">
                        <span class="icon text-white-50">
                            <i class="fas fa-save"></i>
                        </span>
                        <span class="text">Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
