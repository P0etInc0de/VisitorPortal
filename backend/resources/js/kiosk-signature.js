const STROKE_COLOR = '#111827';
const BASE_WIDTH = 2.2;

export function initKioskSignature(canvas, hiddenInput) {
    if (!canvas || !hiddenInput || !canvas.getContext) {
        return;
    }

    if (canvas.__kioskSignature) {
        canvas.__kioskSignature.resize();

        return;
    }

    const context = canvas.getContext('2d');
    let strokes = [];
    let current = null;
    let drawing = false;

    const ratio = () => window.devicePixelRatio || 1;

    const redraw = () => {
        const scale = ratio();
        context.setTransform(scale, 0, 0, scale, 0, 0);
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = STROKE_COLOR;

        strokes.forEach((stroke) => {
            if (stroke.points.length === 0) {
                return;
            }

            context.beginPath();
            context.lineWidth = stroke.width;
            context.moveTo(stroke.points[0].x, stroke.points[0].y);

            if (stroke.points.length === 1) {
                context.lineTo(stroke.points[0].x + 0.1, stroke.points[0].y + 0.1);
            } else {
                stroke.points.slice(1).forEach((point) => context.lineTo(point.x, point.y));
            }

            context.stroke();
        });
    };

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        const scale = ratio();
        canvas.width = Math.max(1, Math.round(rect.width * scale));
        canvas.height = Math.max(1, Math.round(rect.height * scale));
        redraw();
    };

    const position = (event) => {
        const rect = canvas.getBoundingClientRect();

        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top,
        };
    };

    const widthFor = (event) => {
        if (event.pointerType === 'pen' && event.pressure > 0) {
            return 1.2 + event.pressure * 2.6;
        }

        return BASE_WIDTH;
    };

    const sync = () => {
        const hasInk = strokes.some((stroke) => stroke.points.length > 0);
        hiddenInput.value = hasInk ? canvas.toDataURL('image/png') : '';
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const start = (event) => {
        event.preventDefault();
        drawing = true;

        try {
            canvas.setPointerCapture(event.pointerId);
        } catch (error) {
            // Pointer capture is best-effort; drawing still works without it.
        }

        current = { points: [position(event)], width: widthFor(event) };
        strokes.push(current);
        redraw();
    };

    const move = (event) => {
        if (!drawing || !current) {
            return;
        }

        event.preventDefault();
        current.points.push(position(event));
        redraw();
    };

    const end = (event) => {
        if (!drawing) {
            return;
        }

        if (event.pointerId !== undefined) {
            try {
                canvas.releasePointerCapture(event.pointerId);
            } catch (error) {
                // Ignore pointers that were not captured.
            }
        }

        drawing = false;
        current = null;
        sync();
    };

    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', end);
    canvas.addEventListener('pointercancel', end);

    const controller = {
        resize,
        clear() {
            strokes = [];
            current = null;
            drawing = false;
            redraw();
            sync();
        },
    };

    if (window.ResizeObserver) {
        const observer = new ResizeObserver(() => resize());
        observer.observe(canvas);
        controller.__observer = observer;
    } else {
        window.addEventListener('resize', resize);
    }

    canvas.__kioskSignature = controller;
    resize();
}
