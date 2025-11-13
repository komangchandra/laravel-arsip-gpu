@extends('dashboard.layouts.app')

@section('content')
@push('css')
<link href="{{ asset('/') }}vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" />
@endpush

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a class="text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a>
            / <span>Persetujuan Dokumen</span>
        </h1>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Persetujuan Dokumen</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Judul Dokumen</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Tanggal TTD</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Judul Dokumen</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Tanggal TTD</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @forelse ($documentApprovals as $approval)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $approval->document->title ?? '-' }}</td>
                            <td>{{ $approval->user->name ?? '-' }}</td>
                            <td>{{ $approval->role_name }}</td>
                            <td>
                                @if ($approval->status === 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> Pending</span>
                                @elseif ($approval->status === 'signed')
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Signed</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                                @endif
                            </td>
                            <td>{{ $approval->signed_at ? $approval->signed_at->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $approval->notes ?? '-' }}</td>
                            <td>
                                <a href="{{ route('dashboard.documents-approvals.edit', $approval->id) }}" 
                                   class="text-primary me-2" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form action="{{ route('dashboard.documents-approvals.destroy', $approval->id) }}" 
                                      method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn-delete text-danger border-0 bg-transparent p-0" 
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data persetujuan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<!-- Page level plugins -->
<script src="{{ asset('/') }}vendor/datatables/jquery.dataTables.min.js"></script>
<script src="{{ asset('/') }}vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Page level custom scripts -->
<script>
    $(document).ready(function () {
        $("#dataTable").DataTable();

        // SweetAlert untuk konfirmasi delete
        $('.btn-delete').on('click', function (e) {
            e.preventDefault();
            const form = $(this).closest('form');

            Swal.fire({
                title: 'Yakin ingin menghapus?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
