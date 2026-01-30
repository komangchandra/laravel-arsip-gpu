@extends('dashboard.layouts.app')

@section('content')
<div class="container-fluid">

    <h3>Anotasi Dokumen: {{ $document->title }}</h3>

    <div class="alert alert-info">
        Gunakan fitur bawaan browser untuk coret-coret PDF.
        Setelah selesai, klik **Save** lalu upload hasilnya di bawah.
    </div>

    <!-- Viewer PDF -->
    <iframe src="{{ asset('storage/' . $document->file_path) }}" style="width:100%; height:80vh; border:1px solid #ccc;"></iframe>


    <hr>

    <!-- Upload hasil anotasi -->
    <form method="POST"
        action="{{ route('dashboard.documents.annotateUpload', $document->id) }}"
        enctype="multipart/form-data">
        @csrf
        
        <label>Upload PDF hasil coretan:</label>
        <input type="file" name="annotated_pdf" class="form-control" required>
        <button class="btn btn-primary mt-3">Simpan & Gantikan File</button>
    </form>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<script>
    const url = "{{ asset('storage/' . $document->file_path) }}";

    pdfjsLib.getDocument(url).promise.then(function(pdf) {

        for (let page = 1; page <= pdf.numPages; page++) {
            pdf.getPage(page).then(function(pdfPage) {

                const viewport = pdfPage.getViewport({ scale: 1.2 });

                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");

                canvas.width = viewport.width;
                canvas.height = viewport.height;
                canvas.style.marginBottom = "20px";

                document.getElementById("pdf-container").appendChild(canvas);

                pdfPage.render({
                    canvasContext: ctx,
                    viewport: viewport
                });
            });
        }

    });
</script>


@endsection
