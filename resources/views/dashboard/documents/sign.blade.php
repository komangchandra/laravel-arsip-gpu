@extends('dashboard.layouts.app')

@section('content')

@push('css')
<style>
/* Container & canvas */
#pageContainer {
    position: relative;
    display: inline-block; /* agar ukuran canvas tidak stretch lebar container */
    text-align: left;
    margin-bottom: 20px;
}
#pdfCanvas, #drawCanvas {
    display: block;
    border: 1px solid #e0e0e0;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.02);
}
/* drawCanvas berada di atas pdfCanvas */
#drawCanvas {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
    cursor: crosshair;
}
/* toolbar */
.toolbar {
    margin-bottom: 15px;
    display:flex;
    gap:0.5rem;
    align-items:center;
    flex-wrap:wrap;
}
.toolbar .group {
    display:flex;
    gap:0.5rem;
    align-items:center;
}
@media (max-width: 768px) {
    #pageContainer { zoom: 0.9; } /* optional responsive tweak */
}
</style>
@endpush

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Sign Document: {{ $document->title }}</h1>

    <div class="toolbar mb-3">
        <div class="group">
            <button type="button" class="btn btn-primary" id="prevPage">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span class="mx-2">Page: <strong><span id="pageNum">1</span> / <span id="pageCount">0</span></strong></span>
            <button type="button" class="btn btn-primary" id="nextPage">
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>

        <div class="group">
            <button type="button" class="btn btn-secondary" id="zoomIn">
                <i class="fas fa-search-plus"></i>
            </button>
            <button type="button" class="btn btn-secondary" id="zoomOut">
                <i class="fas fa-search-minus"></i>
            </button>
        </div>

        <div class="group">
            <button type="button" class="btn btn-warning" id="undo">
                <i class="fas fa-undo"></i>
            </button>
            <button type="button" class="btn btn-danger" id="clear">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>

    <div id="pageContainer">
        <canvas id="pdfCanvas"></canvas>
        <canvas id="drawCanvas"></canvas>
    </div>

    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-6 d-flex align-items-center my-3">
            <a href="{{ route('dashboard.documents.index') }}" class="btn btn-secondary btn-icon-split my-3 ">
                <span class="icon text-white-50">
                    <i class="fas fa-arrow-left"></i>
                </span>
                <span class="text">Kembali</span>
            </a>
            <form id="saveForm" method="POST" action="{{ route('dashboard.documents.sign.store', $document->id) }}">
                @csrf
                <input type="hidden" name="signed_pages" id="signedPages">
                <button type="submit" name="action_type" value="signed" class="btn btn-success btn-icon-split my-3 mx-3">
                    <span class="icon text-white-50">
                        <i class="fas fa-save"></i>
                    </span>
                    <span class="text">Simpan Dokumen</span>
                </button>
                <button type="submit" name="action_type" value="needs_revision" class="btn btn-danger btn-icon-split my-3">
                    <span class="icon text-white-50">
                        <i class="fas fa-trash"></i>
                    </span>
                    <span class="text">Revisi</span>
                </button>
            </form>
            
        </div>
    </div>
    
</div>

@push('js')
<!-- PDF.js (stable version) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    // set worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
</script>

<script>
(() => {
    const url = "{{ asset('storage/' . $document->file_path) }}";

    let pdfDoc = null;
    let pageNum = 1;
    let pageCount = 0;
    let scale = 1.0;

    const pdfCanvas = document.getElementById('pdfCanvas');
    const pdfCtx = pdfCanvas.getContext('2d');

    const drawCanvas = document.getElementById('drawCanvas');
    const drawCtx = drawCanvas.getContext('2d');

    let isDrawing = false;
    let lastX = 0, lastY = 0;

    // store drawings per page: drawings[page] = array of paths; each path = [{x,y}, ...]
    let drawings = {};
    // store original viewport sizes per page for robust scaling if needed
    let pageViewports = {};

    // default drawing style
    function setupDrawStyle() {
        drawCtx.lineWidth = 2;
        drawCtx.strokeStyle = "#000";
        drawCtx.lineCap = "round";
        drawCtx.lineJoin = "round";
    }

    // Initialize draw style
    setupDrawStyle();

    // Load PDF
    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdfDoc = pdf;
        pageCount = pdf.numPages;
        document.getElementById('pageCount').textContent = pageCount;
        renderPage(pageNum);
    }).catch(err => {
        console.error("PDF load error:", err);
        alert("Gagal memuat dokumen. Cek URL file atau permission storage.");
    });

    // Render page and draw existing strokes for that page
    function renderPage(num) {
        pdfDoc.getPage(num).then(page => {
            const viewport = page.getViewport({ scale });
            pageViewports[num] = { width: viewport.width, height: viewport.height, scale };

            // resize canvases to exact PDF pixel size
            pdfCanvas.width = Math.round(viewport.width);
            pdfCanvas.height = Math.round(viewport.height);

            drawCanvas.width = Math.round(viewport.width);
            drawCanvas.height = Math.round(viewport.height);

            // ensure canvases display as same size blocks (avoid CSS stretching)
            pdfCanvas.style.width = pdfCanvas.width + "px";
            pdfCanvas.style.height = pdfCanvas.height + "px";
            drawCanvas.style.width = drawCanvas.width + "px";
            drawCanvas.style.height = drawCanvas.height + "px";

            // render PDF page
            page.render({ canvasContext: pdfCtx, viewport }).promise.then(() => {
                // after PDF rendered, redraw existing strokes (if any)
                drawCtx.clearRect(0, 0, drawCanvas.width, drawCanvas.height);
                setupDrawStyle();

                if (drawings[num]) {
                    drawings[num].forEach(path => {
                        if (!path || !path.length) return;
                        drawCtx.beginPath();
                        drawCtx.moveTo(path[0].x, path[0].y);
                        for (let i = 1; i < path.length; i++) {
                            drawCtx.lineTo(path[i].x, path[i].y);
                        }
                        drawCtx.stroke();
                    });
                }

                document.getElementById('pageNum').textContent = pageNum;
            }).catch(err => {
                console.error("Render error:", err);
            });
        });
    }

    // Convert client coords to canvas coords (account bounding rect)
    function clientToCanvas(e) {
        const rect = drawCanvas.getBoundingClientRect();
        const clientX = (e.touches ? e.touches[0].clientX : e.clientX);
        const clientY = (e.touches ? e.touches[0].clientY : e.clientY);
        const x = clientX - rect.left;
        const y = clientY - rect.top;
        return { x, y };
    }

    // Start drawing
    function startDraw(e) {
        e.preventDefault();
        const pos = clientToCanvas(e);
        isDrawing = true;
        lastX = pos.x;
        lastY = pos.y;

        if (!drawings[pageNum]) drawings[pageNum] = [];
        drawings[pageNum].push([{ x: lastX, y: lastY }]);
    }

    // Continue drawing
    function moveDraw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const pos = clientToCanvas(e);
        const x = pos.x, y = pos.y;

        const currentPath = drawings[pageNum][drawings[pageNum].length - 1];
        currentPath.push({ x, y });

        drawCtx.beginPath();
        drawCtx.moveTo(lastX, lastY);
        drawCtx.lineTo(x, y);
        drawCtx.stroke();

        lastX = x;
        lastY = y;
    }

    // Stop drawing
    function stopDraw(e) {
        if (!isDrawing) return;
        e && e.preventDefault();
        isDrawing = false;
    }

    // Mouse events
    drawCanvas.addEventListener('mousedown', startDraw);
    drawCanvas.addEventListener('mousemove', moveDraw);
    window.addEventListener('mouseup', stopDraw);

    // Touch events (mobile)
    drawCanvas.addEventListener('touchstart', startDraw, { passive:false });
    drawCanvas.addEventListener('touchmove', moveDraw, { passive:false });
    window.addEventListener('touchend', stopDraw);

    // Toolbar handlers
    document.getElementById('prevPage').addEventListener('click', () => {
        if (pageNum <= 1) return;
        pageNum--;
        renderPage(pageNum);
    });

    document.getElementById('nextPage').addEventListener('click', () => {
        if (pageNum >= pageCount) return;
        pageNum++;
        renderPage(pageNum);
    });

    // Helper to scale paths for a given page from oldScale -> newScale
    function scalePagePaths(page, oldScale, newScale) {
        if (!drawings[page] || !pageViewports[page]) return;
        const ratio = newScale / oldScale;
        drawings[page] = drawings[page].map(path => path.map(pt => ({ x: pt.x * ratio, y: pt.y * ratio })));
    }

    document.getElementById('zoomIn').addEventListener('click', () => {
        const oldScale = scale;
        scale = parseFloat((scale + 0.25).toFixed(2));
        // scale paths for current page so strokes stay at same relative position
        scalePagePaths(pageNum, oldScale, scale);
        renderPage(pageNum);
    });

    document.getElementById('zoomOut').addEventListener('click', () => {
        if (scale <= 0.5) return;
        const oldScale = scale;
        scale = parseFloat((scale - 0.25).toFixed(2));
        scalePagePaths(pageNum, oldScale, scale);
        renderPage(pageNum);
    });

    // Undo last stroke on current page
    document.getElementById('undo').addEventListener('click', () => {
        if (drawings[pageNum] && drawings[pageNum].length) {
            drawings[pageNum].pop();
            renderPage(pageNum);
        }
    });

    // Clear all strokes on current page
    document.getElementById('clear').addEventListener('click', () => {
        drawings[pageNum] = [];
        renderPage(pageNum);
    });

    // Convert every page's drawing to a PNG dataURL (preserve canvas size)
    function buildSignedPagesData() {
        const result = {};
        // For each page that has drawings, render the drawings onto a temp canvas sized to that page
        for (const pStr of Object.keys(drawings)) {
            const p = parseInt(pStr, 10);
            if (!drawings[p] || !drawings[p].length) continue;

            // Determine target width/height from pageViewports if available,
            const vw = pageViewports[p] ? Math.round(pageViewports[p].width) : drawCanvas.width;
            const vh = pageViewports[p] ? Math.round(pageViewports[p].height) : drawCanvas.height;

            const temp = document.createElement('canvas');
            temp.width = vw;
            temp.height = vh;
            const tctx = temp.getContext('2d');

            // set same style
            tctx.lineWidth = drawCtx.lineWidth;
            tctx.strokeStyle = drawCtx.strokeStyle;
            tctx.lineCap = drawCtx.lineCap;
            tctx.lineJoin = drawCtx.lineJoin;

            // draw each path
            drawings[p].forEach(path => {
                if (!path || !path.length) return;
                tctx.beginPath();
                tctx.moveTo(path[0].x, path[0].y);
                for (let i = 1; i < path.length; i++) {
                    tctx.lineTo(path[i].x, path[i].y);
                }
                tctx.stroke();
            });

            // toDataURL
            result[p] = temp.toDataURL('image/png');
        }
        return result;
    }

    // Submit handler - pack signed_pages JSON and submit
    document.getElementById('saveForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const signed = buildSignedPagesData();
        if (Object.keys(signed).length === 0) {
            if (!confirm('Tidak ada coretan yang terdeteksi. Tetap simpan tanpa coretan?')) {
                return;
            }
        }
        document.getElementById('signedPages').value = JSON.stringify(signed);
        this.submit();
    });

})();
</script>
@endpush

@endsection
