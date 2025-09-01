import { useEffect, useRef, useState } from 'react';

export default function AvatarCapture({ onFile, error, initialPreviewUrl = null }) {
  const [preview, setPreview] = useState(initialPreviewUrl || null);
  const [camError, setCamError] = useState('');
  const [usingCam, setUsingCam] = useState(false);
  const [mediaStream, setMediaStream] = useState(null);
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);

  const canUseCamera =
    typeof navigator !== 'undefined' &&
    navigator.mediaDevices &&
    typeof navigator.mediaDevices.getUserMedia === 'function' &&
    (window.isSecureContext || location.hostname === 'localhost');

  // If parent later provides an initial preview (SSR -> client), adopt it once.
  useEffect(() => {
    if (initialPreviewUrl && !preview) setPreview(initialPreviewUrl);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialPreviewUrl]);

  useEffect(() => {
    const video = videoRef.current;
    if (usingCam && video && mediaStream) {
      video.srcObject = mediaStream;
      const onMeta = async () => { try { await video.play(); } catch {} };
      video.onloadedmetadata = onMeta;
      return () => { video.onloadedmetadata = null; };
    }
  }, [usingCam, mediaStream]);

  useEffect(() => () => { if (mediaStream) mediaStream.getTracks().forEach(t => t.stop()); }, [mediaStream]);

  const handleFile = (f) => {
    if (!f) return;
    setPreview(URL.createObjectURL(f));
    onFile(f);
  };

  async function pickFrontDeviceId() {
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      const cams = devices.filter(d => d.kind === 'videoinput');
      const front = cams.find(c => /front|user/i.test(c.label));
      return (front || cams[0])?.deviceId || null;
    } catch { return null; }
  }

  const startCam = async () => {
    setCamError('');
    if (!canUseCamera) { setCamError('Camera requires HTTPS (or localhost).'); return; }
    setUsingCam(true);
    try {
      let constraints = { video: { facingMode: 'user' }, audio: false };
      const deviceId = await pickFrontDeviceId();
      if (deviceId) constraints = { video: { deviceId: { exact: deviceId } }, audio: false };

      let stream;
      try { stream = await navigator.mediaDevices.getUserMedia(constraints); }
      catch { stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); }

      setMediaStream(stream);
    } catch (e) {
      setUsingCam(false);
      const n = e?.name || 'Error';
      setCamError(
        n === 'NotAllowedError' ? 'Camera permission denied.' :
        n === 'NotFoundError' ? 'No camera detected.' :
        n === 'NotReadableError' ? 'Camera busy in another app.' :
        n === 'SecurityError' ? 'Use HTTPS or localhost.' :
        'Camera not available.'
      );
    }
  };

  const snap = () => {
    const video = videoRef.current;
    const canvas = canvasRef.current;
    if (!video?.videoWidth) return;

    const size = 480;
    canvas.width = size; canvas.height = size;
    const ctx = canvas.getContext('2d');

    const vw = video.videoWidth, vh = video.videoHeight;
    const s = Math.min(vw, vh);
    const sx = (vw - s)/2, sy = (vh - s)/2;
    ctx.drawImage(video, sx, sy, s, s, 0, 0, size, size);

    canvas.toBlob((blob) => {
      const file = new File([blob], 'avatar.png', { type: 'image/png' });
      setPreview(URL.createObjectURL(file));
      onFile(file);
    }, 'image/png', 0.92);
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-2">
        <span className="text-sm text-gray-800 dark:text-gray-100">Profile Photo (required)</span>
        {canUseCamera && (
          <button
            type="button"
            onClick={startCam}
            className="text-xs px-2 py-1 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100"
          >
            Use camera
          </button>
        )}
      </div>

      {/* Single circular preview (shows existing avatar OR new selection) */}
      <div className="w-28 h-28 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 mb-3 ring-1 ring-gray-300 dark:ring-gray-600">
        {preview
          ? <img src={preview} alt="preview" className="w-full h-full object-cover" />
          : <div className="w-full h-full flex items-center justify-center text-xs text-gray-600 dark:text-gray-300">No photo</div>}
      </div>

      {/* Gallery picker (styled, dark-friendly) */}
      <input
        ref={fileInputRef}
        id="avatar-file-input"
        type="file"
        accept="image/*"
        onChange={(e)=>handleFile(e.target.files[0])}
        className="hidden"
      />
      <label
        htmlFor="avatar-file-input"
        className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 text-sm cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition"
      >
        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 2a1 1 0 0 0-1 1v1H6a3 3 0 0 0-3 3v1h18V7a3 3 0 0 0-3-3h-2V3a1 1 0 1 0-2 0v1h-4V3a1 1 0 0 0-1-1zM21 9H3v9a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3V9zm-9 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 .002 6.002A3 3 0 0 0 12 13z"/></svg>
        Choose from gallery
      </label>
      <p className="mt-1 text-xs text-gray-700 dark:text-gray-300">Or use the in-app camera above. Max 3MB.</p>

      {usingCam && (
        <div className="mt-3 mb-2">
          <video ref={videoRef} autoPlay playsInline className="w-full rounded-lg mb-2" />
          <button
            type="button"
            onClick={snap}
            className="px-3 py-1 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700 transition"
          >
            Capture
          </button>
          <canvas ref={canvasRef} className="hidden" />
        </div>
      )}

      {(error || camError) && <p className="text-red-600 text-sm mt-1">{error || camError}</p>}

      {!canUseCamera && (
        <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
          Tip: HTTPS (or localhost) enables the in-page camera.
        </p>
      )}
    </div>
  );
}
