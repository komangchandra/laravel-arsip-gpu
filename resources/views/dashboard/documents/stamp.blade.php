@extends('dashboard.layouts.app')

@section('content')

@push('css')
<style>
/* Container & canvas */
#pageContainer {
    position: relative;
    display: inline-block;
    text-align: left;
    margin-bottom: 20px;
}

/* pdf canvas */
#pdfCanvas {
    display: block;
    border: 1px solid #e0e0e0;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.02);
}

/* stamp container sits above pdf canvas */
#stampContainer {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 30;
    pointer-events: none; /* container itself doesn't block events */
}

/* each stamp wrapper */
.stamp-wrap {
    position: absolute;
    transform-origin: top left; /* we manage rotation around top-left then adjust */
    pointer-events: auto;
    z-index: 40;
}

/* image inside wrapper */
.stamp-wrap img.stamp-img {
    display: block;
    width: 100%;
    height: 100%;
    user-select: none;
    -webkit-user-drag: none;
    pointer-events: none; /* image doesn't catch mouse (wrapper does) */
}

/* outline when selected */
.stamp-wrap.selected {
    outline: 2px dashed red;
}

/* handles */
.handle {
    position: absolute;
    width: 14px;
    height: 14px;
    background: #fff;
    border: 2px solid #007bff;
    border-radius: 3px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    z-index: 60;
}

/* bottom-right resize handle */
.handle.resize {
    right: -10px;
    bottom: -10px;
    cursor: nwse-resize;
}

/* top rotate handle */
.handle.rotate {
    left: 50%;
    transform: translateX(-50%);
    top: -26px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #28a745;
    cursor: grab;
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

/* responsive tweak */
@media (max-width: 768px) {
    #pageContainer { zoom: 0.9; }
}
</style>
@endpush

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Stampel Document: {{ $document->title }}</h1>

    <div class="toolbar mb-3">
        <div class="group">
            <button type="button" class="btn btn-primary" id="prevPage"><i class="fas fa-arrow-left"></i></button>
            <span class="mx-2">Page: <strong><span id="pageNum">1</span> / <span id="pageCount">0</span></strong></span>
            <button type="button" class="btn btn-primary" id="nextPage"><i class="fas fa-arrow-right"></i></button>
        </div>

        <div class="group">
            <button type="button" class="btn btn-secondary" id="zoomIn"><i class="fas fa-search-plus"></i></button>
            <button type="button" class="btn btn-secondary" id="zoomOut"><i class="fas fa-search-minus"></i></button>
        </div>

        <div class="group">
            <button type="button" class="btn btn-danger" id="deleteSelected"><i class="fas fa-trash-alt"></i></button>
            <button type="button" class="btn btn-outline-secondary" id="clearAll"><i class="fas fa-ban"></i></button>
        </div>

        <div class="group ms-2">
            <button type="button" class="btn btn-info btn-icon-split" id="addStampGpu">
                <span class="icon text-white-50"><i class="fas fa-stamp"></i></span>
                <span class="text">Stampel GPU</span>
            </button>

            <button type="button" class="btn btn-info btn-icon-split" id="addStampGe">
                <span class="icon text-white-50"><i class="fas fa-stamp"></i></span>
                <span class="text">Stampel GE</span>
            </button>
        </div>
    </div>

    <div id="pageContainer">
        <canvas id="pdfCanvas"></canvas>
        <div id="stampContainer"></div>
    </div>

    <form id="stampForm" method="POST" action="{{ route('dashboard.documents.stamp.store', $document->id) }}">
        @csrf
        <a href="{{ route('dashboard.documents.index') }}" class="btn btn-secondary btn-icon-split my-3">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
        <button type="submit" class="btn btn-success btn-icon-split my-3" id="saveBtn">
            <span class="icon text-white-50"><i class="fas fa-save"></i></span>
            <span class="text">Simpan Stampel</span>
        </button>
    </form>
</div>

@push('js')
<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
</script>

<script>
(() => {
    // ----- CONFIG: use your public images paths -----
    const STAMP_GPU = "{{ asset('images/stampel-gpu.png') }}"; // 320x108 px (you provided)
    const STAMP_GE  = "{{ asset('images/stampel-ge.png') }}";  // 268x72 px  (you provided)

    const url = "{{ asset('storage/' . $document->file_path) }}";
    const pdfCanvas = document.getElementById('pdfCanvas');
    const pdfCtx = pdfCanvas.getContext('2d');
    const stampContainer = document.getElementById('stampContainer');

    let pdfDoc = null;
    let pageNum = 1;
    let pageCount = 0;
    let scale = 1.0;

    // stamps: { pageNumber: [ {id,type,x,y,w,h,rotation} ] }
    let stamps = {};
    let idCounter = 1;
    let activeWrapper = null;

    // load PDF
    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdfDoc = pdf;
        pageCount = pdf.numPages;
        document.getElementById('pageCount').textContent = pageCount;
        renderPage(pageNum);
    }).catch(err=>{
        console.error('PDF load error', err);
        alert('Gagal memuat dokumen. Cek URL file atau permission storage.');
    });

    function renderPage(n) {
        pdfDoc.getPage(n).then(page => {
            const viewport = page.getViewport({ scale });
            pdfCanvas.width = Math.round(viewport.width);
            pdfCanvas.height = Math.round(viewport.height);
            pdfCanvas.style.width = pdfCanvas.width + 'px';
            pdfCanvas.style.height = pdfCanvas.height + 'px';

            // size and position stamp container to match canvas
            stampContainer.style.width  = pdfCanvas.width + 'px';
            stampContainer.style.height = pdfCanvas.height + 'px';
            stampContainer.style.left = pdfCanvas.offsetLeft + 'px';
            stampContainer.style.top  = pdfCanvas.offsetTop + 'px';

            page.render({ canvasContext: pdfCtx, viewport }).promise.then(()=> {
                document.getElementById('pageNum').textContent = pageNum;
                refreshStampsForPage();
            });
        });
    }

    // utilities
    function ensurePageArray(p) {
        if (!stamps[p]) stamps[p] = [];
    }

    // create wrapper DOM for a stamp object
    function createStampWrapper(stObj) {
        const wrap = document.createElement('div');
        wrap.className = 'stamp-wrap';
        wrap.style.left = stObj.x + 'px';
        wrap.style.top  = stObj.y + 'px';
        wrap.style.width = stObj.w + 'px';
        wrap.style.height = stObj.h + 'px';
        wrap.dataset.id = stObj.id;
        wrap.dataset.page = stObj.page;
        wrap.style.transform = `rotate(${stObj.rotation || 0}deg)`;

        // image
        const img = document.createElement('img');
        img.className = 'stamp-img';
        img.draggable = false;
        img.src = stObj.type === 'gpu' ? STAMP_GPU : STAMP_GE;
        wrap.appendChild(img);

        // rotate handle (top center)
        const rotateHandle = document.createElement('div');
        rotateHandle.className = 'handle rotate';
        wrap.appendChild(rotateHandle);

        // resize handle (bottom-right)
        const resizeHandle = document.createElement('div');
        resizeHandle.className = 'handle resize';
        wrap.appendChild(resizeHandle);

        // events
        wrap.addEventListener('mousedown', wrapMouseDown);
        wrap.addEventListener('dblclick', () => { removeStampById(stObj.page, stObj.id); });

        // handles events
        resizeHandle.addEventListener('mousedown', resizeMouseDown);
        rotateHandle.addEventListener('mousedown', rotateMouseDown);

        return wrap;
    }

    function refreshStampsForPage() {
        // clear container
        stampContainer.innerHTML = '';
        ensurePageArray(pageNum);
        stamps[pageNum].forEach(s => {
            const wrap = createStampWrapper(s);
            stampContainer.appendChild(wrap);
        });
    }
    function addStamp(type) {
        ensurePageArray(pageNum);
        const defaultW = Math.min(160, Math.round(pdfCanvas.width * 0.25));
        const aspect = (type==='gpu') ? (108/320) : (72/268);
        const defaultH = Math.round(defaultW * aspect);

        const stObj = {
            id: 's' + (Date.now()) + '_' + (idCounter++),
            page: pageNum,
            type,
            x: Math.max(10, Math.round((pdfCanvas.width - defaultW) / 2)),
            y: Math.max(10, Math.round((pdfCanvas.height - defaultH) / 2)),
            w: defaultW,
            h: defaultH,
            rotation: 0
        };

        stamps[pageNum].push(stObj);
        refreshStampsForPage();
        selectStampById(stObj.id);
    }
    document.getElementById('addStampGpu').addEventListener('click', () => addStamp('gpu'));
    document.getElementById('addStampGe').addEventListener('click', () => addStamp('ge'));
    function deselectAll() {
        document.querySelectorAll('.stamp-wrap').forEach(el => el.classList.remove('selected'));
        activeWrapper = null;
    }
    function selectStampById(id) {
        deselectAll();
        const el = stampContainer.querySelector(`.stamp-wrap[data-id="${id}"]`);
        if (el) {
            el.classList.add('selected');
            activeWrapper = el;
        }
    }
    let dragState = null;
    function wrapMouseDown(e) {
        if (e.target.classList.contains('handle')) return;

        e.preventDefault();
        const wrap = e.currentTarget;
        selectStampById(wrap.dataset.id);

        const startRect = wrap.getBoundingClientRect();
        const containerRect = stampContainer.getBoundingClientRect();

        const startX = e.clientX;
        const startY = e.clientY;
        const origLeft = parseFloat(wrap.style.left);
        const origTop  = parseFloat(wrap.style.top);

        dragState = { wrap, startX, startY, origLeft, origTop, containerRect };

        window.addEventListener('mousemove', wrapDragging);
        window.addEventListener('mouseup', wrapDragEnd);
    }
    function wrapDragging(e) {
        if (!dragState) return;
        e.preventDefault();
        const { wrap, startX, startY, origLeft, origTop, containerRect } = dragState;
        let dx = e.clientX - startX;
        let dy = e.clientY - startY;
        let newLeft = origLeft + dx;
        let newTop  = origTop + dy;

        // clamp
        newLeft = Math.max(0, Math.min(newLeft, containerRect.width - wrap.offsetWidth));
        newTop  = Math.max(0, Math.min(newTop, containerRect.height - wrap.offsetHeight));

        wrap.style.left = newLeft + 'px';
        wrap.style.top  = newTop + 'px';

        updateStampModel(wrap);
    }
    function wrapDragEnd() {
        window.removeEventListener('mousemove', wrapDragging);
        window.removeEventListener('mouseup', wrapDragEnd);
        dragState = null;
    }
    let resizeState = null;
    function resizeMouseDown(e) {
        e.stopPropagation();
        e.preventDefault();
        const handle = e.currentTarget;
        const wrap = handle.parentElement;
        selectStampById(wrap.dataset.id);

        const startRect = wrap.getBoundingClientRect();
        const containerRect = stampContainer.getBoundingClientRect();
        const startX = e.clientX;
        const startY = e.clientY;
        const origW = startRect.width;
        const origH = startRect.height;
        const origLeft = parseFloat(wrap.style.left);
        const origTop  = parseFloat(wrap.style.top);

        resizeState = { wrap, startX, startY, origW, origH, origLeft, origTop, containerRect };

        window.addEventListener('mousemove', resizing);
        window.addEventListener('mouseup', resizeEnd);
    }
    function resizing(e) {
        if (!resizeState) return;
        e.preventDefault();
        const { wrap, startX, startY, origW, origH, origLeft, origTop, containerRect } = resizeState;
        let dx = e.clientX - startX;
        let dy = e.clientY - startY;

        let newW = Math.max(30, origW + dx);
        let newH = Math.max(30, origH + dy);

        // clamp to container bounds
        newW = Math.min(newW, containerRect.width - origLeft);
        newH = Math.min(newH, containerRect.height - origTop);

        wrap.style.width = newW + 'px';
        wrap.style.height = newH + 'px';

        updateStampModel(wrap);
    }
    function resizeEnd() {
        window.removeEventListener('mousemove', resizing);
        window.removeEventListener('mouseup', resizeEnd);
        resizeState = null;
    }
    let rotateState = null;
    function rotateMouseDown(e) {
        e.stopPropagation();
        e.preventDefault();
        const handle = e.currentTarget;
        const wrap = handle.parentElement;
        selectStampById(wrap.dataset.id);

        const rect = wrap.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;

        rotateState = { wrap, centerX, centerY };

        window.addEventListener('mousemove', rotating);
        window.addEventListener('mouseup', rotateEnd);
    }
    function rotating(e) {
        if (!rotateState) return;
        e.preventDefault();
        const { wrap, centerX, centerY } = rotateState;
        const angle = Math.atan2(e.clientY - centerY, e.clientX - centerX) * (180 / Math.PI);
        // convert angle so 0 is normal orientation (we adjust)
        const rotation = angle + 90;
        wrap.style.transform = `rotate(${rotation}deg)`;
        updateStampModel(wrap, rotation);
    }
    function rotateEnd() {
        window.removeEventListener('mousemove', rotating);
        window.removeEventListener('mouseup', rotateEnd);
        rotateState = null;
    }
    function updateStampModel(wrapEl, rotationOverride) {
        const id = wrapEl.dataset.id;
        const page = parseInt(wrapEl.dataset.page, 10);
        const x = Math.round(parseFloat(wrapEl.style.left) || 0);
        const y = Math.round(parseFloat(wrapEl.style.top) || 0);
        const w = Math.round(wrapEl.offsetWidth);
        const h = Math.round(wrapEl.offsetHeight);
        // rotation: read from transform
        let rotation = typeof rotationOverride !== 'undefined' ? rotationOverride : 0;
        if (typeof rotationOverride === 'undefined') {
            const t = wrapEl.style.transform || '';
            const m = t.match(/rotate\((-?\d+(\.\d+)?)deg\)/);
            rotation = m ? parseFloat(m[1]) : 0;
        }

        // find in stamps model and update
        ensurePageArray(page);
        const s = stamps[page].find(i => i.id == id);
        if (s) {
            s.x = x; s.y = y; s.w = w; s.h = h; s.rotation = Math.round(rotation);
        }
    }
    function removeStampById(page, id) {
        if (!stamps[page]) return;
        stamps[page] = stamps[page].filter(s => s.id != id);
        refreshStampsForPage();
    }
    document.getElementById('deleteSelected').addEventListener('click', () => {
        if (!activeWrapper) return alert('Pilih stampel terlebih dahulu.');
        const id = activeWrapper.dataset.id;
        const page = parseInt(activeWrapper.dataset.page, 10);
        removeStampById(page, id);
    });
    document.getElementById('clearAll').addEventListener('click', () => {
        if (!confirm('Hapus semua stampel pada halaman ini?')) return;
        stamps[pageNum] = [];
        refreshStampsForPage();
    });
    document.getElementById('prevPage').addEventListener('click', () => {
        if (pageNum <= 1) return;
        pageNum--;
        activeWrapper = null;
        renderPage(pageNum);
    });
    document.getElementById('nextPage').addEventListener('click', () => {
        if (pageNum >= pageCount) return;
        pageNum++;
        activeWrapper = null;
        renderPage(pageNum);
    });
    function rescaleStampsForPage(oldScale, newScale) {
        if (!stamps[pageNum]) return;
        const ratio = newScale / oldScale;
        stamps[pageNum].forEach(s => {
            s.x = Math.round(s.x * ratio);
            s.y = Math.round(s.y * ratio);
            s.w = Math.round(s.w * ratio);
            s.h = Math.round(s.h * ratio);
        });
    }
    document.getElementById('zoomIn').addEventListener('click', () => {
        const old = scale;
        scale = parseFloat((scale + 0.25).toFixed(2));
        rescaleStampsForPage(old, scale);
        renderPage(pageNum);
    });
    document.getElementById('zoomOut').addEventListener('click', () => {
        if (scale <= 0.5) return;
        const old = scale;
        scale = parseFloat((scale - 0.25).toFixed(2));
        rescaleStampsForPage(old, scale);
        renderPage(pageNum);
    });
    document.getElementById('stampForm').addEventListener('submit', function(e) {
        // pastikan input hidden untuk canvas dan stamps ada di form
        if (!document.getElementById('canvasWidthInput')) {
            const inpW = document.createElement('input');
            inpW.type = 'hidden';
            inpW.name = 'canvas_width';
            inpW.id = 'canvasWidthInput';
            this.appendChild(inpW);

            const inpH = document.createElement('input');
            inpH.type = 'hidden';
            inpH.name = 'canvas_height';
            inpH.id = 'canvasHeightInput';
            this.appendChild(inpH);

            const inpS = document.createElement('input');
            inpS.type = 'hidden';
            inpS.name = 'stamps';
            inpS.id = 'stampsInput';
            this.appendChild(inpS);
        }

        const payload = {};

        for (const p of Object.keys(stamps)) {
            if (!stamps[p] || stamps[p].length === 0) continue;

            payload[p] = stamps[p].map(s => {
                // konversi posisi ke rasio 0..1 relatif canvas
                return {
                    id: s.id,
                    type: s.type,
                    x_ratio: s.x / pdfCanvas.width,
                    y_ratio: s.y / pdfCanvas.height,
                    width_ratio: s.w / pdfCanvas.width,
                    height_ratio: s.h / pdfCanvas.height,
                    rotation: s.rotation || 0
                };
            });
        }

        document.getElementById('canvasWidthInput').value = pdfCanvas.width;
        document.getElementById('canvasHeightInput').value = pdfCanvas.height;
        document.getElementById('stampsInput').value = JSON.stringify(payload);
    });

})();
</script>
@endpush

@endsection
