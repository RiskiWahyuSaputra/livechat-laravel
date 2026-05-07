{{-- Image Lightbox Popup — include di setiap halaman yang tampilkan gambar chat --}}
<div id="img-lightbox"
     style="display:none; position:fixed; inset:0; z-index:999999; align-items:center; justify-content:center; cursor:zoom-out;"
     onclick="if(event.target===this||event.target===document.getElementById('img-lightbox-backdrop'))closeLightbox()">

    {{-- Backdrop blur --}}
    <div id="img-lightbox-backdrop"
         style="position:absolute; inset:0; background:rgba(0,0,0,0.88); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);"></div>

    {{-- Close button --}}
    <button onclick="closeLightbox()"
            title="Tutup (Esc)"
            style="position:absolute; top:16px; right:16px; z-index:1; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.2); color:white; width:44px; height:44px; border-radius:50%; font-size:22px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s;"
            onmouseover="this.style.background='rgba(255,255,255,0.28)'"
            onmouseout="this.style.background='rgba(255,255,255,0.15)'">
        &times;
    </button>

    {{-- Open in new tab button --}}
    <a id="img-lightbox-link" href="#" target="_blank" title="Buka di tab baru"
       style="position:absolute; top:16px; right:68px; z-index:1; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.2); color:white; width:44px; height:44px; border-radius:50%; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all 0.2s;"
       onmouseover="this.style.background='rgba(255,255,255,0.28)'"
       onmouseout="this.style.background='rgba(255,255,255,0.15)'">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
    </a>

    {{-- Image --}}
    <img id="img-lightbox-img" src="" alt="Preview"
         style="position:relative; z-index:1; max-width:92vw; max-height:88vh; border-radius:12px; box-shadow:0 30px 100px rgba(0,0,0,0.6); object-fit:contain; cursor:default; transform:scale(0.92); transition:transform 0.25s cubic-bezier(.34,1.56,.64,1); user-select:none;"
         onclick="event.stopPropagation()"
         draggable="false">
</div>

<script>
(function () {
    if (window.__lightboxReady) return;
    window.__lightboxReady = true;

    window.openLightbox = function (src) {
        var lb  = document.getElementById('img-lightbox');
        var img = document.getElementById('img-lightbox-img');
        var lnk = document.getElementById('img-lightbox-link');
        if (!lb || !img) return;
        img.src = src;
        if (lnk) lnk.href = src;
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Animate in
        requestAnimationFrame(function () {
            img.style.transform = 'scale(1)';
        });
    };

    window.closeLightbox = function () {
        var lb  = document.getElementById('img-lightbox');
        var img = document.getElementById('img-lightbox-img');
        if (!lb) return;
        if (img) img.style.transform = 'scale(0.92)';
        setTimeout(function () {
            lb.style.display = 'none';
            if (img) { img.style.transform = 'scale(0.92)'; img.src = ''; }
            document.body.style.overflow = '';
        }, 180);
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.closeLightbox();
    });
})();
</script>
