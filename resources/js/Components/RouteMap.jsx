import { Loader } from '@googlemaps/js-api-loader';
import { useEffect, useRef, useState } from 'react';

function decodePolyline(str) {
  let index = 0, lat = 0, lng = 0, coordinates = [];
  while (index < str.length) {
    let b, shift = 0, result = 0;
    do { b = str.charCodeAt(index++) - 63; result |= (b & 0x1f) << shift; shift += 5; } while (b >= 0x20);
    const dlat = (result & 1) ? ~(result >> 1) : (result >> 1); lat += dlat;
    shift = 0; result = 0;
    do { b = str.charCodeAt(index++) - 63; result |= (b & 0x1f) << shift; shift += 5; } while (b >= 0x20);
    const dlng = (result & 1) ? ~(result >> 1) : (result >> 1); lng += dlng;
    coordinates.push({ lat: lat / 1e5, lng: lng / 1e5 });
  }
  return coordinates;
}

export default function RouteMap({ from, to, onSummary, apiKey, summaryUrl }) {
  const mapRef = useRef(null);
  const [map, setMap] = useState(null);
  const [renderer, setRenderer] = useState(null); // for client fallback
  const [error, setError] = useState(null);

  const polylineRef = useRef(null);
  const markersRef = useRef([]);
  const reqCounterRef = useRef(0);

  // NEW: remember last OD pair we processed + last OD pair we fitBounds for
  const routeKeyRef = useRef(null);
  const drawnKeyRef = useRef(null);

  // Load Maps once
  useEffect(() => {
    let cancelled = false;
    const key = apiKey || import.meta.env.VITE_GOOGLE_MAPS_BROWSER_KEY;
    if (!key) {
      setError('Missing browser API key');
      onSummary?.({ error: 'missing-key' });
      return;
    }
    const loader = new Loader({ apiKey: key, version: 'weekly' });
    loader.load().then(() => {
      if (cancelled) return;
      const g = window.google;
      const m = new g.maps.Map(mapRef.current, {
        zoom: 12,
        center: from || to || { lat: 9.0820, lng: 8.6753 },
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        gestureHandling: 'greedy',
      });
      setMap(m);
      setRenderer(new g.maps.DirectionsRenderer({ map: m, suppressMarkers: true, preserveViewport: true }));
    }).catch(err => {
      setError(`Maps loader failed: ${err?.message || 'unknown'}`);
      onSummary?.({ error: 'loader-failed', detail: err?.message });
    });
    return () => { cancelled = true; };
  }, [apiKey]);

  const clearCustomRoute = () => {
    if (polylineRef.current) {
      polylineRef.current.setMap(null);
      polylineRef.current = null;
    }
    markersRef.current.forEach(m => m.setMap(null));
    markersRef.current = [];
  };

  useEffect(() => {
    if (!map || !from?.lat || !from?.lng || !to?.lat || !to?.lng) return;

    // Stable key (round a bit to avoid micro changes)
    const key = `${from.lat.toFixed(6)},${from.lng.toFixed(6)}|${to.lat.toFixed(6)},${to.lng.toFixed(6)}`;

    // If we’ve already processed this OD pair, do nothing → prevents blinking/redraw & zoom snap
    if (routeKeyRef.current === key) return;
    routeKeyRef.current = key;

    const g = window.google;
    const myReqId = ++reqCounterRef.current;

    const fitIfFirstTime = (bounds) => {
      if (drawnKeyRef.current !== key) {
        map.fitBounds(bounds);           // only on first draw for this route
        drawnKeyRef.current = key;
      }
    };

    if (summaryUrl) {
      const url = `${summaryUrl}?from=${from.lat},${from.lng}&to=${to.lat},${to.lng}`;
      fetch(url, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }, // avoid Inertia modal
      })
        .then(async (r) => {
          const ct = r.headers.get('content-type') || '';
          if (!ct.includes('application/json')) throw new Error('non-json');
          return r.json();
        })
        .then((data) => {
          if (myReqId !== reqCounterRef.current) return; // stale
          if (!data?.ok) throw new Error(data?.status || 'server-failed');

          onSummary?.(data.summary || null);
          setError(null);

          clearCustomRoute();
          const path = decodePolyline(data.overview_polyline || '');
          const bounds = new g.maps.LatLngBounds();
          path.forEach(p => bounds.extend(p));

          polylineRef.current = new g.maps.Polyline({
            path, map, strokeWeight: 5, strokeOpacity: 0.9,
          });

          const a = new g.maps.Marker({ position: from, map, label: 'A' });
          const b = new g.maps.Marker({ position: to,   map, label: 'B' });
          markersRef.current.push(a, b);

          fitIfFirstTime(bounds);
        })
        .catch(() => {
          // Fallback to client DirectionsService once
          const svc = new g.maps.DirectionsService();
          const req = {
            origin: from, destination: to,
            travelMode: g.maps.TravelMode.DRIVING,
            provideRouteAlternatives: false,
            drivingOptions: { departureTime: new Date() },
          };
          svc.route(req, (res, status) => {
            if (myReqId !== reqCounterRef.current) return; // stale
            if (status !== 'OK' || !res?.routes?.length) {
              setError(`Directions failed: ${status}`);
              onSummary?.({ error: `directions-${status}` });
              return;
            }
            setError(null);
            renderer?.setDirections(res);
            const leg = res.routes[0]?.legs?.[0];

            // Build bounds from the polyline points, then fit only first time
            const bounds = new g.maps.LatLngBounds();
            res.routes[0].overview_path?.forEach(p => bounds.extend(p));
            fitIfFirstTime(bounds);

            if (leg && onSummary) {
              onSummary({
                distance_text: leg.distance?.text,
                distance_m: leg.distance?.value,
                duration_text: leg.duration?.text,
                duration_s: leg.duration?.value,
                duration_in_traffic_s: leg.duration_in_traffic?.value,
              });
            }
          });
        });

      return;
    }

    // No server URL → pure client path
    const svc = new g.maps.DirectionsService();
    const req = {
      origin: from, destination: to,
      travelMode: g.maps.TravelMode.DRIVING,
      provideRouteAlternatives: false,
      drivingOptions: { departureTime: new Date() },
    };
    svc.route(req, (res, status) => {
      if (myReqId !== reqCounterRef.current) return;
      if (status !== 'OK' || !res?.routes?.length) {
        setError(`Directions failed: ${status}`);
        onSummary?.({ error: `directions-${status}` });
        return;
      }
      setError(null);
      renderer?.setDirections(res);

      const bounds = new g.maps.LatLngBounds();
      res.routes[0].overview_path?.forEach(p => bounds.extend(p));
      fitIfFirstTime(bounds);

      const leg = res.routes[0]?.legs?.[0];
      if (leg && onSummary) {
        onSummary({
          distance_text: leg.distance?.text,
          distance_m: leg.distance?.value,
          duration_text: leg.duration?.text,
          duration_s: leg.duration?.value,
          duration_in_traffic_s: leg.duration_in_traffic?.value,
        });
      }
    });
  }, [map, from, to, summaryUrl]);

  return (
    <div>
      <div ref={mapRef} style={{ width: '100%', height: '280px', borderRadius: '12px', overflow: 'hidden' }} />
      {error && <div className="mt-2 text-xs text-rose-600">{error}</div>}
    </div>
  );
}
