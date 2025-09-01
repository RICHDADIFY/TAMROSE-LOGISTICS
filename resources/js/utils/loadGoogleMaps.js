let loadPromise = null;

export default function loadGoogleMaps(apiKey) {
  if (typeof window !== "undefined" && window.google && window.google.maps) {
    return Promise.resolve(window.google);
  }
  if (loadPromise) return loadPromise;
  if (!apiKey) return Promise.reject(new Error("Google Maps browser API key missing"));

  loadPromise = new Promise((resolve, reject) => {
    const existing = document.getElementById("gmaps-js");
    if (existing) {
      existing.addEventListener("load", () => resolve(window.google));
      existing.addEventListener("error", () => reject(new Error("Failed to load Google Maps JS API")));
      return;
    }

    const script = document.createElement("script");
    script.id = "gmaps-js";
    script.async = true;
    script.defer = true;
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly`;
    script.onload = () => resolve(window.google);
    script.onerror = () => reject(new Error("Failed to load Google Maps JS API"));
    document.head.appendChild(script);
  });

  return loadPromise;
}
