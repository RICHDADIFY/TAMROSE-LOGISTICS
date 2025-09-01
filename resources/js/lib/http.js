export async function postForm(url, formData) {
  let token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!formData.has('_token')) formData.append('_token', token);

  const buildHeaders = () => ({
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': token,
    'Cache-Control': 'no-store',
    'Pragma': 'no-cache',
  });

  const doPost = () => fetch(url, {
    method: 'POST',
    body: formData,
    headers: buildHeaders(),
    credentials: 'same-origin',
  });

  // First attempt
  let res = await doPost();

  // If session rotated / token stale → fetch fresh token and retry once
  if (res.status === 419) {
    try {
      const r = await fetch('/csrf-token', { credentials: 'same-origin', headers: { 'Accept': 'application/json' }});
      const j = await r.json();
      if (j?.token) {
        token = j.token;
        if (formData.has('_token')) formData.set('_token', token);
        res = await doPost(); // retry once
      }
    } catch (e) {
      // ignore, will return the 419 response
    }
  }

  return res;
}
