// resources/js/Components/DeliverModal.jsx
import React, { useEffect, useRef, useState } from 'react';
import { postForm } from '@/lib/http';



export default function DeliverModal({ consignment, onClose }) {
  const [receiverName, setReceiverName]   = useState('');
  const [receiverPhone, setReceiverPhone] = useState('');
  const [otp, setOtp]                     = useState('');
  
  const [photos, setPhotos]               = useState([]);
  const [gps, setGps]                     = useState({ lat: null, lng: null });
  const [loading, setLoading]             = useState(false);
  const [error, setError]                 = useState(null);
  const initialRequireOtp = !!consignment.require_otp;
  const [requireOtp, setRequireOtp]     = useState(initialRequireOtp);
  const [useSignature, setUseSignature] = useState(!initialRequireOtp); // default to signature if OTP not required
  const [metaLoading, setMetaLoading]   = useState(true);
  

  

  
  

  const fileInputRef = useRef(null);

  // Signature canvas
  const canvasRef = useRef(null);
  

  useEffect(() => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => setGps({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => {}, { enableHighAccuracy: true, maximumAge: 15000, timeout: 8000 }
      );
    }
  }, []);

  useEffect(() => {
  let alive = true;
  (async () => {
    try {
      const res = await fetch(`/consignments/${consignment.id}/delivery-meta`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => null);
      if (!alive) return;

      if (data?.ok) {
        // Update to live values from server
        setRequireOtp(!!data.require_otp);
        // If OTP is required, default the UI to OTP (i.e., not signature)
        setUseSignature(!data.require_otp);
      }
    } finally {
      if (alive) setMetaLoading(false);
    }
  })();
  return () => { alive = false; };
}, [consignment.id]);


  const onFilesPicked = (e) => {
    const files = Array.from(e.target.files || []);
    setPhotos((prev) => prev.concat(files).slice(0, 6));
    e.target.value = '';
  };
  const removePhoto = (idx) => setPhotos((prev) => prev.filter((_, i) => i !== idx));

  // Signature draw helpers
  const clearSignature = () => {
  const c = canvasRef.current; if (!c) return;
  const ctx = c.getContext('2d');
  // reset transform so we clear the full backing store
  ctx.save();
  ctx.setTransform(1, 0, 0, 1, 0, 0);
  ctx.clearRect(0, 0, c.width, c.height);
  ctx.restore();

  // re-apply our drawing transform (optional, but keeps width/lineWidth consistent)
  const dpr = dprRef.current || 1;
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#0ea5e9';
};

  
// High-performance signature drawing (pointer events + DPR scaling)
// High-performance signature drawing (pointer events + DPR scaling)
// Re-initialize whenever the signature panel is visible.
// High-perf canvas setup + listeners. Rebind whenever Signature UI is visible.
const dprRef = useRef(1);

useEffect(() => {
  const c = canvasRef.current;
  if (!c || !useSignature) return; // only bind when signature UI is showing

  const ctx = c.getContext('2d', { willReadFrequently: false });

  const sizeCanvas = () => {
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    dprRef.current = dpr;

    const cssW = Math.max(1, Math.floor(c.clientWidth));
    const cssH = Math.max(1, Math.floor(c.clientHeight));

    c.width  = Math.max(1, Math.floor(cssW * dpr));
    c.height = Math.max(1, Math.floor(cssH * dpr));
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0ea5e9';
  };

  sizeCanvas();
  const ro = new ResizeObserver(sizeCanvas);
  ro.observe(c);

  let drawing = false;

  const getXY = (e) => {
    const r = c.getBoundingClientRect();
    const cx = e.clientX ?? e.touches?.[0]?.clientX ?? 0;
    const cy = e.clientY ?? e.touches?.[0]?.clientY ?? 0;
    return { x: cx - r.left, y: cy - r.top };
  };

  const down = (e) => {
    e.preventDefault();
    drawing = true;
    c.setPointerCapture?.(e.pointerId);
    const { x, y } = getXY(e);
    ctx.beginPath();
    ctx.moveTo(x, y);
  };

  const move = (e) => {
    if (!drawing) return;
    e.preventDefault();
    const { x, y } = getXY(e);
    ctx.lineTo(x, y);
    ctx.stroke();
  };

  const up = (e) => {
    if (!drawing) return;
    e.preventDefault();
    drawing = false;
    c.releasePointerCapture?.(e.pointerId);
  };

  // Pointer events + fallbacks (covers older iOS if needed)
  c.addEventListener('pointerdown', down, { passive: false });
  c.addEventListener('pointermove', move, { passive: false });
  window.addEventListener('pointerup', up, { passive: false });
  c.addEventListener('pointercancel', up, { passive: false });
  c.addEventListener('contextmenu', (e) => e.preventDefault());

  // Touch fallback
  c.addEventListener('touchstart', down, { passive: false });
  c.addEventListener('touchmove',  move, { passive: false });
  window.addEventListener('touchend', up, { passive: false });

  // Mouse fallback
  c.addEventListener('mousedown', down, { passive: false });
  c.addEventListener('mousemove', move, { passive: false });
  window.addEventListener('mouseup', up, { passive: false });

  return () => {
    ro.disconnect();

    c.removeEventListener('pointerdown', down);
    c.removeEventListener('pointermove', move);
    window.removeEventListener('pointerup', up);
    c.removeEventListener('pointercancel', up);

    c.removeEventListener('touchstart', down);
    c.removeEventListener('touchmove', move);
    window.removeEventListener('touchend', up);

    c.removeEventListener('mousedown', down);
    c.removeEventListener('mousemove', move);
    window.removeEventListener('mouseup', up);
  };
}, [useSignature]); // ✅ key dependency


// 2) PUT THIS CLEAR-ON-TOGGLE EFFECT RIGHT AFTER SETUP
  useEffect(() => {
    if (!useSignature) return;
    const c = canvasRef.current;
    if (!c) return;
    const ctx = c.getContext('2d');
    ctx.save();
    ctx.setTransform(1,0,0,1,0,0);
    ctx.clearRect(0,0,c.width,c.height);
    ctx.restore();
  }, [useSignature]);


  // Get signature as a *small* Blob (webp preferred, ~5–25 KB)
  const getSignatureBlob = () =>
    new Promise((resolve) => {
      const c = canvasRef.current;
      if (!c) return resolve(null);
      // detect blank
      const blank = document.createElement('canvas'); blank.width = c.width; blank.height = c.height;
      if (c.toDataURL() === blank.toDataURL()) return resolve(null);
      c.toBlob((b) => resolve(b), 'image/webp', 0.9); // webp keeps it very small
    });

  const submit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const fd = new FormData();
      fd.append('mode', useSignature ? 'signature' : 'otp'); // 👈 tell backend explicitly
      if (useSignature) {
        const sig = await getSignatureBlob();
        if (!sig) {
          setError('Please capture a signature, or switch to OTP.');
          setLoading(false);
          return;
        }
        // Enforce ~256 KB cap client-side too
        if (sig.size > 256 * 1024) {
          setError('Signature too large; please try a simpler stroke.');
          setLoading(false);
          return;
        }
        fd.append('signature', sig, 'signature.webp');
      } else {
        fd.append('otp', otp);
      }

      fd.append('receiver_name', receiverName);
      fd.append('receiver_phone', receiverPhone);
      if (gps.lat && gps.lng) { fd.append('lat', gps.lat); fd.append('lng', gps.lng); }
      photos.forEach((f) => fd.append('photos[]', f));

      const res  = await postForm(`/consignments/${consignment.id}/verify-otp`, fd);
      const text = await res.text();
      let data = null; try { data = JSON.parse(text); } catch {}

      if (!res.ok || !data || data.ok !== true) {
        const message = typeof data?.error === 'string'
          ? data.error
          : (data?.error ? JSON.stringify(data.error) : (text || '').slice(0, 160));
        setError(`Failed (${res.status}). ${message || 'No JSON'}`);
        return;
      }

      onClose(true); // parent triggers router.reload({ only: ['trip'] })
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // === ADD THESE LINES HERE (safe-to-submit rules) ===
  const otpNeededButMissing = requireOtp && !useSignature && otp.trim().length === 0;
  const canSubmit = !loading && !metaLoading && !otpNeededButMissing;
  const btnHint = metaLoading
    ? 'Checking delivery requirements…'
    : (otpNeededButMissing ? 'Enter the OTP to continue' : '');


  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="w-full max-w-md rounded-2xl border border-gray-200 dark:border-slate-700/70 bg-white dark:bg-slate-900 p-5 shadow-xl">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-3">Deliver Consignment</h2>

        {error && (
          <div className="mb-3 text-xs px-2 py-1 rounded bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200">
            {error}
          </div>
        )}

        {metaLoading && (
          <div className="mb-2 text-xs text-slate-500 dark:text-slate-400">
            Checking delivery requirements…
          </div>
        )}

        <form onSubmit={submit} className="space-y-3">
          <div>
            <label className="block text-sm text-slate-700 dark:text-slate-300 mb-1">Receiver Name</label>
            <input
              className="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 py-2"
              required
              value={receiverName}
              onChange={e=>setReceiverName(e.target.value)}
            />
          </div>

          <div>
            <label className="block text-sm text-slate-700 dark:text-slate-300 mb-1">Receiver Phone</label>
            <input
              className="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 py-2"
              value={receiverPhone}
              onChange={e=>setReceiverPhone(e.target.value)}
            />
          </div>

          {/* Toggle & inputs */}
                    {requireOtp ? (
            <p className="text-xs text-slate-500 dark:text-slate-400">
              OTP is required. If the receiver can’t provide it, you may switch to signature:
              <button type="button" onClick={()=>setUseSignature(s=>!s)} className="ml-1 underline">
                {useSignature ? 'Use OTP' : 'Use Signature Instead'}
              </button>
            </p>
          ) : (
            <p className="text-xs text-slate-500 dark:text-slate-400">
              OTP is disabled for this consignment. Use signature + photos.
            </p>
          )}


          {!useSignature && (
            <div>
              <label className="block text-sm text-slate-700 dark:text-slate-300 mb-1">OTP Code</label>
              <input
                className="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 px-3 py-2 tracking-widest"
                value={otp}
                onChange={e=>setOtp(e.target.value)}
                inputMode="numeric"
                placeholder="••••"
                required={requireOtp}

              />
            </div>
          )}

          {useSignature && (
            <div>
              <label className="block text-sm text-slate-700 dark:text-slate-300 mb-1">Receiver Signature</label>
              <div className="rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                <canvas
                  ref={canvasRef}
                  width={480}
                  height={160}
                  style={{ width: '100%', height: '10rem', touchAction: 'none' }}  // 👈 important
                  className="bg-white dark:bg-slate-900 rounded"
                ></canvas>

                <div className="flex justify-end mt-2">
                  <button type="button" onClick={clearSignature}
                    className="h-8 px-3 rounded bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-sm">
                    Clear
                  </button>
                </div>
              </div>
              <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Saved as a tiny WebP (~5–25 KB).</p>
            </div>
          )}

          {/* Photos */}
          <div>
            <div className="flex items-center justify-between mb-1">
              <label className="text-sm text-slate-700 dark:text-slate-300">Proof Photos</label>
              <span className="text-xs text-slate-500 dark:text-slate-400">{photos.length}/6</span>
            </div>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                className="h-9 px-3 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700"
              >
                Add Photos
              </button>
              <span className="text-xs text-slate-500 dark:text-slate-400">(camera or gallery)</span>
            </div>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/*"
              multiple
              className="hidden"
              onChange={onFilesPicked}
            />
            {photos.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {photos.map((f, i) => (
                  <div key={i} className="relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700">
                    <img src={URL.createObjectURL(f)} alt={`proof-${i}`} className="w-full h-full object-cover" />
                    <button
                      type="button"
                      onClick={() => removePhoto(i)}
                      className="absolute top-1 right-1 h-5 px-2 rounded bg-black/60 text-white text-[11px]"
                      title="Remove"
                    >✕</button>
                  </div>
                ))}
              </div>
            )}
          </div>

         <div className="flex justify-end gap-2 pt-1">
            <button
              type="button"
              onClick={() => onClose(false)}
              className="h-9 px-3 rounded-lg bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!canSubmit}
              aria-disabled={!canSubmit}
              title={btnHint || undefined}
              className={`h-9 px-4 rounded-lg text-sm text-white
                ${canSubmit ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-600/60 cursor-not-allowed'}
              `}
            >
              {loading ? 'Submitting…' : 'Confirm Delivery'}
            </button>

          </div>
        </form>
      </div>
    </div>
  );
}
