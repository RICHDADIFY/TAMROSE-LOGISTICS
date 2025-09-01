// resources/js/utils/csrf-init.js

/**
 * Warm up the CSRF cookie so Laravel Sanctum
 * sets a fresh XSRF-TOKEN before any fetch/axios calls.
 */
export async function warmCsrf() {
  try {
    await fetch('/sanctum/csrf-cookie', {
      credentials: 'same-origin',
    });
  } catch (e) {
    console.warn('CSRF warm failed:', e);
  }
}
