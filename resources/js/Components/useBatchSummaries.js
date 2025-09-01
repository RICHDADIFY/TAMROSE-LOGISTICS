import { useEffect, useState } from 'react';

export function useBatchSummaries(items, batchUrl) {
  const [data, setData] = useState({});

  useEffect(() => {
    if (!items?.length || !batchUrl) return;

    const payload = {
      items: items.map(i => ({
        key: i.key,
        from: `${i.from.lat},${i.from.lng}`,
        to:   `${i.to.lat},${i.to.lng}`,
      })),
    };

    // Grab CSRF token from the <meta> tag (make sure it exists in your base layout)
    const csrf =
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(batchUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,          // 👈 important for POST in web middleware
      },
      body: JSON.stringify(payload),
    })
      .then(async (r) => {
        const ct = r.headers.get('content-type') || '';
        if (!r.ok) {
          // Helpful console message (419 = CSRF; 401/403 = auth)
          console.warn(`[batch-summary] HTTP ${r.status}`);
          throw new Error(`HTTP_${r.status}`);
        }
        if (!ct.includes('application/json')) {
          console.warn('[batch-summary] Non-JSON response');
          throw new Error('NON_JSON');
        }
        return r.json();
      })
      .then(j => {
        setData(j?.results || {});
      })
      .catch(err => {
        console.warn('[batch-summary] failed:', err?.message || err);
        setData({}); // leave pills at "calculating…" if it fails
      });

  // stringify items so effect refires when list meaningfully changes
  }, [JSON.stringify(items), batchUrl]);

  return data; // map: key -> { ok, summary }
}
