@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <!-- Content Row -->
    <div class="row">

        <!-- 1. Total Dokumen Arsip -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Dokumen Arsip
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $documents }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Dokumen Full Sign Bulan Ini -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Full Sign Bulan Ini
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $fullThisMonth }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Dokumen Saya Upload -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Dokumen Yang Saya Upload
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $myDocuments }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-upload fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Baru diupload hari ini -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Baru diupload Hari Ini
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $recentActivities->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Activities Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Dokumen Baru Hari Ini</h6>
        </div>
        <div class="card-body">
            @if($recentActivities->isEmpty())
                <p class="text-muted">Tidak ada dokumen baru hari ini.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Status</th>
                                <th>Waktu Upload</th>
                                <th>##</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivities as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td>Baru diupload</td>
                                <td>{{ $doc->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('dashboard.documents.index') }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
