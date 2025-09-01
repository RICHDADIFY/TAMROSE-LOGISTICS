// resources/js/Components/LiveTripMap.jsx
import { useEffect, useRef, useState } from 'react';
import { fetchJson } from '@/utils/fetchJson'; // <-- use the shared helper (CSRF-safe)

/**
 * LiveTripMap
 * Props:
 *   - tripId: number (required)
 *   - toLat: number (required)
 *   - toLng: number (required)
 *   - pollMs?: number (default 10000)
 *   - windowMins?: number (default 180)
 */
export default function LiveTripMap({
  tripId,
  toLat,
  toLng,
  pollMs = 10000,
  windowMins = 180,
}) {
  const mapRef = useRef(null);
  const map = useRef(null);
  const driverMarker = useRef(null);
  const destMarker = useRef(null);
  const directionsService = useRef(null);
  const directionsRenderer = useRef(null);
  const pollHandle = useRef(null);
  const firstRouteDrawn = useRef(false);

  const [etaText, setEtaText] = useState('—');
  const [stale, setStale] = useState(false);
  const [lastRecordedAt, setLastRecordedAt] = useState(null);
  const [error, setError] = useState(null);

  const ensureGoogleLoaded = () => {
    if (!window.google || !window.google.maps) {
      throw new Error('Google Maps JS not loaded yet');
    }
  };

  const fitBounds = (a, b) => {
    const bounds = new google.maps.LatLngBounds();
    bounds.extend(a);
    bounds.extend(b);
    map.current.fitBounds(bounds);
  };

  const setDriverIcon = () => {
    if (!driverMarker.current) return;
    const icon = {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 6,
      fillColor: stale ? '#888888' : '#0A84FF',
      fillOpacity: 1,
      strokeWeight: 1,
    };
    driverMarker.current.setIcon(icon);
  };

  const updateDriverMarker = (pos) => {
    if (!driverMarker.current) {
      driverMarker.current = new google.maps.Marker({
        map: map.current,
        position: pos,
        title: 'Driver',
      });
      setDriverIcon();
    } else {
      driverMarker.current.setPosition(pos);
      setDriverIcon();
    }
  };

  const drawRoute = async (from, to) => {
    if (!directionsService.current) return;
    const res = await directionsService.current.route({
      origin: from,
      destination: to,
      travelMode: google.maps.TravelMode.DRIVING,
      provideRouteAlternatives: false,
    });
    directionsRenderer.current.setDirections(res);
  };

  /* ----------------------- data loaders ----------------------- */
  const fetchRecent = async () => {
    const url = `/api/trips/${tripId}/recent-locations?minutes=${windowMins}`;
    const data = await fetchJson(url); // uses CSRF-safe helper

    if (!data?.points?.length) return;

    const last = data.points[data.points.length - 1];
    setLastRecordedAt(last.recorded_at);

    const lastTs = new Date(last.recorded_at).getTime();
    const isStale = Date.now() - lastTs > 10 * 60 * 1000; // 10 mins
    setStale(isStale);

    const pos = { lat: parseFloat(last.lat), lng: parseFloat(last.lng) };
    updateDriverMarker(pos);

    const dest = { lat: parseFloat(toLat), lng: parseFloat(toLng) };

    if (!firstRouteDrawn.current) {
      fitBounds(pos, dest);
      firstRouteDrawn.current = true;
    }

    await drawRoute(pos, dest);
  };

  const fetchEta = async () => {
    const url = `/api/trips/${tripId}/eta`;
    const data = await fetchJson(url);
    if (data?.eta_minutes != null) {
      setEtaText(`~${data.eta_minutes} mins`);
    } else {
      setEtaText('—');
    }
  };

  /* ----------------------- lifecycle ----------------------- */
  useEffect(() => {
    let mounted = true;

    const init = async () => {
      try {
        ensureGoogleLoaded();
        map.current = new google.maps.Map(mapRef.current, {
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: false,
        });

        directionsService.current = new google.maps.DirectionsService();
        directionsRenderer.current = new google.maps.DirectionsRenderer({
          map: map.current,
          suppressMarkers: true,
          preserveViewport: true,
        });

        // destination marker
        destMarker.current = new google.maps.Marker({
          map: map.current,
          position: { lat: parseFloat(toLat), lng: parseFloat(toLng) },
          title: 'Destination',
        });

        // first fetch
        await fetchRecent();
        await fetchEta();
        setError(null);

        // polling
        pollHandle.current = setInterval(async () => {
          try {
            await fetchRecent();
            await fetchEta();
          } catch (e) {
            // Keep previous UI; only surface persistent errors
            setError(e?.message || String(e));
          }
        }, pollMs);
      } catch (e) {
        if (mounted) setError(e?.message || String(e));
      }
    };

    init();

    return () => {
      mounted = false;
      if (pollHandle.current) clearInterval(pollHandle.current);
      if (directionsRenderer.current) directionsRenderer.current.setMap(null);
      if (driverMarker.current) driverMarker.current.setMap(null);
      if (destMarker.current) destMarker.current.setMap(null);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tripId, toLat, toLng, pollMs, windowMins]);

  /* ----------------------- render ----------------------- */
  return (
    <div className="space-y-2">
      <div className="flex flex-wrap items-center gap-3">
        <div className="text-sm px-2 py-1 rounded-full border">
          ETA: <strong>{etaText}</strong>
        </div>
        {stale && (
          <div className="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">
            Location is stale
          </div>
        )}
        {lastRecordedAt && (
          <div className="text-xs text-gray-500">
            last ping: {new Date(lastRecordedAt).toLocaleString()}
          </div>
        )}
        {error && (
          <div className="text-xs text-red-600 truncate">
            Error: {error}
          </div>
        )}
      </div>
      <div
        ref={mapRef}
        id="live-trip-map"
        className="w-full h-80 rounded-2xl overflow-hidden"
      />
    </div>
  );
}
