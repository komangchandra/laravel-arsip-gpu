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
        <a
            href="{{ route('dashboard.documents.create') }}"
            class="btn btn-primary btn-sm shadow-sm"
        >
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Dokumen
        </a>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div
            class="card-header py-3 d-flex justify-content-between align-items-center"
        >
            <h6 class="m-0 font-weight-bold text-primary">Daftar Dokumen</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered"
                    id="dataTable"
                    width="100%"
                    cellspacing="0"
                >
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>File Asli</th>
                            <th>File TTD</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th>Diperiksa Oleh</th>
                            <th>##</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Judul</th>
                            <th>File Asli</th>
                            <th>File TTD</th>
                            <th>Status</th>
                            <th>Dibuat Oleh</th>
                            <th>Diperiksa Oleh</th>
                            <th>##</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @forelse ($documents as $document)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $document->title }}</td>
                            <td>
                                <a
                                    href="{{ asset('storage/' . $document->file_path) }}"
                                    target="_blank"
                                    class="text-decoration-none"
                                >
                                    Lihat
                                </a>
                            </td>
                            <td>
                                @if ($document->file_signed_path)
                                <a
                                    href="{{ asset('storage/' . $document->file_signed_path) }}"
                                    target="_blank"
                                    class="text-decoration-none"
                                >
                                    Lihat
                                </a>
                                @else
                                <span class="text-muted">Belum ada</span>
                                @endif
                            </td>
                            <td>
                                @php $colors = [ 'uploaded' => 'secondary',
                                'checked' => 'info', 'in_approval' => 'warning',
                                'signed' => 'success', 'archived' => 'dark', ];
                                @endphp
                                <span
                                    class="badge bg-{{ $colors[$document->status] ?? 'secondary' }}"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                                </span>
                            </td>
                            <td>{{ $document->creator->name ?? '-' }}</td>
                            <td>{{ $document->checker->name ?? '-' }}</td>
                            <td>
                                <a
                                    href="{{ route('dashboard.documents.edit', $document->id) }}"
                                    class="btn btn-sm btn-warning"
                                    >Edit</a
                                >

                                <form
                                    action="{{ route('dashboard.documents.destroy', $document->id) }}"
                                    method="POST"
                                    style="display: inline"
                                >
                                    @csrf @method('DELETE')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-delete"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                Belum ada dokumen
                            </td>
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
