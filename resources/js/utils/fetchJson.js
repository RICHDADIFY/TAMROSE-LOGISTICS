function getCookie(name) {
  return document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1];
}

export async function fetchJson(url, options = {}) {
  const token = decodeURIComponent(getCookie('XSRF-TOKEN') || '');
  const headers = {
    'X-Requested-With': 'XMLHttpRequest',
    ...(token ? { 'X-XSRF-TOKEN': token } : {}),
    ...(options.headers || {}),
  };
  const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`${res.status} ${res.statusText} – ${body.slice(0, 300)}`);
  }
  const ct = res.headers.get('content-type') || '';
  return ct.includes('application/json') ? res.json() : body;
}
