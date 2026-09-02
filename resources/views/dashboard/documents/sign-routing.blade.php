@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Routing Tanda Tangan: {{ $document->title }}</h1>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card shadow mb-4"><div class="card-body">
        <p>Pilih user sesuai urutan penandatanganan. Routing tidak dapat diubah setelah dimulai.</p>
        <form method="POST" action="{{ route('dashboard.documents.sign-routing.update', $document) }}" id="routing-form">
            @csrf @method('PUT')
            <div id="signer-list">
                @php($selected = old('signers', $document->signRoutes->pluck('user_id')->all()))
                @foreach ($selected as $userId)
                    <div class="input-group mb-2 signer-row">
                        <div class="input-group-prepend"><span class="input-group-text sequence">{{ $loop->iteration }}</span></div>
                        <select name="signers[]" class="form-control" required>
                            <option value="">Pilih user</option>
                            @foreach ($users as $user)<option value="{{ $user->id }}" @selected((int) $userId === $user->id)>{{ $user->name }} — {{ $user->jabatan }} ({{ $user->getRoleNames()->join(', ') }})</option>@endforeach
                        </select>
                        <div class="input-group-append"><button type="button" class="btn btn-outline-secondary move-up">↑</button><button type="button" class="btn btn-outline-secondary move-down">↓</button><button type="button" class="btn btn-outline-danger remove-signer">×</button></div>
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-signer" class="btn btn-secondary btn-icon-split">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Tambah Penandatangan</span>
            </button>
            <button type="submit" class="btn btn-primary btn-icon-split">
                <span class="icon text-white-50">
                    <i class="fas fa-save"></i>
                </span>
                <span class="text">Simpan Draft Routing</span>
            </button>
        </form>
        @if ($document->signRoutes->isNotEmpty())
            <form method="POST" action="{{ route('dashboard.documents.sign-routing.start', $document) }}" class="mt-3" onsubmit="return confirm('Mulai routing sekarang?')">
                @csrf
                <button class="btn btn-success btn-icon-split">
                    <span class="icon text-white-50">
                        <i class="fas fa-play"></i>
                    </span>
                    <span class="text">Mulai Routing</span>
                </button>
            </form>
        @endif
    </div></div>
</div>
@push('js')
<script>
(() => {
 const list=document.getElementById('signer-list');
 const options=@json($users->map(fn($u) => ['id'=>$u->id,'label'=>$u->name.' — '.$u->jabatan]));
 const renumber=()=>[...list.children].forEach((row,i)=>row.querySelector('.sequence').textContent=i+1);
 document.getElementById('add-signer').onclick=()=>{const row=document.createElement('div');row.className='input-group mb-2 signer-row';row.innerHTML='<div class="input-group-prepend"><span class="input-group-text sequence"></span></div><select name="signers[]" class="form-control" required><option value="">Pilih user</option>'+options.map(u=>`<option value="${u.id}">${u.label}</option>`).join('')+'</select><div class="input-group-append"><button type="button" class="btn btn-outline-secondary move-up">↑</button><button type="button" class="btn btn-outline-secondary move-down">↓</button><button type="button" class="btn btn-outline-danger remove-signer">×</button></div>';list.append(row);renumber();};
 list.onclick=e=>{const row=e.target.closest('.signer-row');if(!row)return;if(e.target.closest('.remove-signer'))row.remove();if(e.target.closest('.move-up')&&row.previousElementSibling)list.insertBefore(row,row.previousElementSibling);if(e.target.closest('.move-down')&&row.nextElementSibling)list.insertBefore(row.nextElementSibling,row);renumber();};
 if(!list.children.length)document.getElementById('add-signer').click();
})();
</script>
@endpush
@endsection
