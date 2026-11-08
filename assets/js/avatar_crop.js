/**
 * Adjust a new profile photo before it is uploaded (see includes/profile_header.php).
 * The person drags and zooms the photo inside a circle, with a preview at grid size,
 * and a square JPEG goes up in place of the original. Photos are always square after
 * this, so the circle looks the same on every page. If the browser cannot open the
 * picture, the original is sent as it is and the server decides.
 */
(function () {
    var OUTPUT_SIZE = 800, MAX_ZOOM = 4;

    var input   = document.getElementById('avatarInput');
    var overlay = document.getElementById('avatarCropOverlay');
    if (!input || !overlay) return;

    var stage   = document.getElementById('avatarCropStage');
    var preview = document.getElementById('avatarCropPreview');
    var slider  = document.getElementById('avatarCropZoom');
    var saveBtn = document.getElementById('avatarCropSave');

    document.body.appendChild(overlay);   // keeps it clear of the profile header's own stacking

    var img = null, objectUrl = null;
    var zoom = 1, centerX = 0, centerY = 0;   // the point of the photo at the middle of the circle
    var pointers = new Map(), pinchDistance = 0;

    function halfView() { return Math.min(img.naturalWidth, img.naturalHeight) / (2 * zoom); }

    function keepInside() {
        var half = halfView();
        centerX = Math.min(img.naturalWidth - half, Math.max(half, centerX));
        centerY = Math.min(img.naturalHeight - half, Math.max(half, centerY));
    }

    function paint(canvas, size) {
        var half = halfView(), ctx = canvas.getContext('2d');
        canvas.width = canvas.height = size;
        ctx.fillStyle = '#fff';          // a transparent PNG gets a white background, not black
        ctx.fillRect(0, 0, size, size);
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(img, centerX - half, centerY - half, half * 2, half * 2, 0, 0, size, size);
    }

    function render() {
        if (!img) return;
        keepInside();
        var ratio = window.devicePixelRatio || 1;
        paint(stage, Math.round(stage.clientWidth * ratio));
        paint(preview, Math.round(preview.clientWidth * ratio));
    }

    function setZoom(value) {
        zoom = Math.min(MAX_ZOOM, Math.max(1, value));
        slider.value = zoom;
        render();
    }

    function move(dx, dy) {
        var perPixel = halfView() * 2 / stage.clientWidth;
        centerX -= dx * perPixel;
        centerY -= dy * perPixel;
        render();
    }

    function open() {
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        zoom = 1;
        slider.value = 1;
        centerX = img.naturalWidth / 2;
        centerY = img.naturalHeight / 2;
        saveBtn.disabled = false;
        render();
    }

    function close() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
        pointers.clear();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        img = null;
    }

    function cancel() {
        close();
        input.value = '';
    }

    function save() {
        saveBtn.disabled = true;
        var out = document.createElement('canvas');
        paint(out, OUTPUT_SIZE);
        out.toBlob(function (blob) {
            if (!blob) { saveBtn.disabled = false; return; }
            var transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'profile.jpg', { type: 'image/jpeg' }));
            input.files = transfer.files;
            close();
            input.form.submit();
        }, 'image/jpeg', 0.9);
    }

    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;
        if (!window.DataTransfer || !window.URL) { input.form.submit(); return; }
        objectUrl = URL.createObjectURL(file);
        var picture = new Image();
        picture.onload = function () { img = picture; open(); };
        picture.onerror = function () { input.form.submit(); };
        picture.src = objectUrl;
    });

    function pinchGap() {
        var p = Array.from(pointers.values());
        return Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y);
    }

    stage.addEventListener('pointerdown', function (e) {
        stage.setPointerCapture(e.pointerId);
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pointers.size === 2) pinchDistance = pinchGap();
    });
    stage.addEventListener('pointermove', function (e) {
        var last = pointers.get(e.pointerId);
        if (!last) return;
        var dx = e.clientX - last.x, dy = e.clientY - last.y;
        last.x = e.clientX;
        last.y = e.clientY;
        if (pointers.size === 1) {
            move(dx, dy);
        } else if (pointers.size === 2) {
            var gap = pinchGap();
            if (pinchDistance > 0) setZoom(zoom * gap / pinchDistance);
            pinchDistance = gap;
        }
    });
    ['pointerup', 'pointercancel'].forEach(function (type) {
        stage.addEventListener(type, function (e) { pointers.delete(e.pointerId); });
    });

    stage.addEventListener('wheel', function (e) {
        e.preventDefault();
        setZoom(zoom * Math.exp(-e.deltaY * 0.002));
    }, { passive: false });

    stage.addEventListener('keydown', function (e) {
        var step = 10, keys = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
        if (keys[e.key]) { e.preventDefault(); move(keys[e.key][0], keys[e.key][1]); }
    });

    slider.addEventListener('input', function () { setZoom(parseFloat(slider.value)); });
    saveBtn.addEventListener('click', save);
    overlay.querySelectorAll('[data-crop-cancel]').forEach(function (b) { b.addEventListener('click', cancel); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) cancel(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.classList.contains('open')) cancel(); });
    window.addEventListener('resize', render);
})();
