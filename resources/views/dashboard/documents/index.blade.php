@extends('dashboard.layouts.app') @section('content')
<!-- Custom styles for this page -->
@push('css')
<link
    href="{{ asset('/') }}vendor/datatables/dataTables.bootstrap4.min.css"
    rel="stylesheet"
/>
@endpush

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a class="text-decoration-none" href="{{ route('dashboard') }}"
                >Dashboard</a
            >
            /
            <span>Documents</span>
        </h1>
        @php
            $disabledRoles = ['director', 'manager', 'ktt', 'sr-staff', 'sr-staff-haul'];
            $isDisabled = auth()->user() && auth()->user()->hasAnyRole($disabledRoles);
        @endphp

        <a href="{{ $isDisabled ? '#' : route('dashboard.documents.create') }}"
        class="btn btn-primary btn-icon-split {{ $isDisabled ? 'disabled' : '' }}"
        {{ $isDisabled ? 'aria-disabled=true tabindex=-1' : '' }}>
            <span class="icon text-white-50">
                <i class="fas fa-plus fa-sm text-white-50"></i>
            </span>
            <span class="text">Tambah Dokumen</span>
        </a>

    </div>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session("success") }}
    </div>
    @endif

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div
            class="card-header py-3 d-flex justify-content-between align-items-center"
        >
            <h6 class="m-0 font-weight-bold text-primary">Daftar Dokumen</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th>Dicek Oleh</th>
                            <th>Sign By</th>
                            <th>##</th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th>Dicek Oleh</th>
                            <th>Sign By</th>
                            <th>##</th>
                        </tr>
                    </tfoot>

                    <tbody>
                        @forelse ($documents as $document)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $document->title }}</td>

                            <td>{{ $document->category->name }}</td>

                            <td>
                                @php 
                                    $colors = [
                                        'uploaded' => 'secondary',
                                        'needs_revision' => 'danger',
                                        'ready_to_sign' => 'info',
                                        'signed' => 'success',
                                        'archived' => 'primary',
                                    ];
                                @endphp

                                <span class="badge bg-{{ $colors[$document->status] ?? 'secondary' }} text-white">
                                    {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                </span>
                            </td>

                            <td>{{ $document->creator->name ?? '-' }}</td>

                            <!-- Dicek oleh (many to many) -->
                            <td>
                                @if ($document->checkedBy->count())
                                    @foreach ($document->checkedBy as $user)
                                        <span class="badge bg-info text-white">{{ $user->name }}</span><br>
                                    @endforeach
                                @else
                                    <span class="text-muted">Belum dicek</span>
                                @endif
                            </td>

                            <!-- Ditandatangani oleh (many to many) -->
                            <td>
                                @if ($document->signedBy->count())
                                    @foreach ($document->signedBy as $user)
                                        <span class="badge bg-success text-white">{{ $user->name }}</span><br>
                                    @endforeach
                                @else
                                    <span class="text-muted">Belum disign</span>
                                @endif
                            </td>

                            <td>
                                <!-- Tombol lihat file signed -->
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <!-- Tombol stampel -->
                                @role(['super-admin', 'staff'])
                                <a href="{{ route('dashboard.documents.stamp', $document->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-stamp"></i>
                                </a>
                                @endrole

                                <!-- Tombol Sign -->
                                @role(['super-admin', 'manager', 'ktt', 'sr-staff', 'sr-staff-haul'])
                                    <a href="{{ route('dashboard.documents.sign', $document->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endrole

                                <!-- Tombol edit -->
                                @role(['super-admin', 'staff', 'staff-haul'])
                                    <a href="{{ route('dashboard.documents.edit', $document->id) }}" 
                                    class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endrole

                                <!-- Tombol delete -->
                                @role('super-admin')
                                    <form action="{{ route('dashboard.documents.destroy', $document->id) }}" 
                                        method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endrole
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada dokumen</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Custom JS -->
@push('js')
<script src="{{
        asset('/')
    }}vendor/datatables/jquery.dataTables.min.js"></script>
<script src="{{
        asset('/')
    }}vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        $("#dataTable").DataTable();

        // SweetAlert konfirmasi hapus
        $(".btn-delete").click(function (e) {
            e.preventDefault();
            const form = $(this).closest("form");

            Swal.fire({
                title: "Yakin ingin menghapus?",
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
