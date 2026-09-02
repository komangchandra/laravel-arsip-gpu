@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Menunggu Tanda Tangan Saya</h1>
    <div class="card shadow"><div class="card-body"><div class="table-responsive">
        <table class="table table-bordered"><thead><tr><th>Dokumen</th><th>Pengunggah</th><th>Kategori</th><th>Urutan</th><th>Aktif Sejak</th><th>Aksi</th></tr></thead><tbody>
        @forelse ($routes as $route)
            <tr><td>{{ $route->document->title }}</td><td>{{ $route->document->creator->name }}</td><td>{{ $route->document->category?->name ?? '-' }}</td><td>{{ $route->sequence }} dari {{ $route->document->signRoutesCount() }}</td><td>{{ $route->activated_at?->format('d/m/Y H:i') }}</td><td><a class="btn btn-success btn-sm" href="{{ route('dashboard.documents.sign', $route->document) }}">Tanda tangani</a> @can('signTempel', $route->document)<a class="btn btn-info btn-sm" href="{{ route('dashboard.documents.sign-tempel', $route->document) }}">Sign Tempel</a>@endcan @can('delete', $route->document)<form action="{{ route('dashboard.documents.destroy', $route->document) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen ini secara permanen?')">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm" title="Hapus dokumen"><i class="fas fa-trash"></i></button></form>@endcan</td></tr>
        @empty<tr><td colspan="6" class="text-center">Tidak ada dokumen yang menunggu tanda tangan Anda.</td></tr>@endforelse
        </tbody></table>
    </div></div></div>
</div>
@endsection
