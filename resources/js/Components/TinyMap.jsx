import { useEffect, useRef, useState } from "react";
import loadGoogleMaps from "@/utils/loadGoogleMaps";

export default function TinyMap({
  apiKey,
  from,
  to,
  height = "160px",
  lazy = false,
  padding = 48,          // fitBounds padding (px)
  className = "",
}) {
  const containerRef = useRef(null);
  const [error, setError] = useState("");
  const observerRef = useRef(null);

  useEffect(() => {
    let map;
    let markers = [];
    let polyline;
    let visible = !lazy; // if not lazy, consider visible immediately
    let resizeTimer;

    const hasNum = (v) => Number.isFinite(Number(v));
    const hasFrom = !!(from && hasNum(from.lat) && hasNum(from.lng));
    const hasTo   = !!(to   && hasNum(to.lat)   && hasNum(to.lng));

    const cleanup = () => {
      if (observerRef.current) {
        observerRef.current.disconnect();
        observerRef.current = null;
      }
      markers.forEach((m) => m?.setMap?.(null));
      markers = [];
      if (polyline) polyline.setMap(null);
      polyline = null;
      map = null;
      if (resizeTimer) clearTimeout(resizeTimer);
    };

    const initMap = async () => {
      setError("");
      if (!hasFrom && !hasTo) {
        setError("No map data");
        return;
      }

      try {
        const google = await loadGoogleMaps(apiKey);
        if (!containerRef.current) return;

        map = new google.maps.Map(containerRef.current, {
          center: hasFrom
            ? { lat: Number(from.lat), lng: Number(from.lng) }
            : { lat: Number(to.lat),   lng: Number(to.lng) },
          zoom: hasFrom && hasTo ? 12 : 14,
          disableDefaultUI: true,
          draggable: false,
          clickableIcons: false,
          mapTypeControl: false,
          fullscreenControl: false,
          streetViewControl: false,
          gestureHandling: "none",
        });

        // Add markers that exist
        if (hasFrom) {
          markers.push(
            new google.maps.Marker({
              position: { lat: Number(from.lat), lng: Number(from.lng) },
              map,
              label: "A",
            })
          );
        }
        if (hasTo) {
          markers.push(
            new google.maps.Marker({
              position: { lat: Number(to.lat), lng: Number(to.lng) },
              map,
              label: hasFrom ? "B" : "A",
            })
          );
        }

        if (hasFrom && hasTo) {
          const bounds = new google.maps.LatLngBounds();
          markers.forEach((m) => bounds.extend(m.getPosition()));

          // If same point, set a sensible zoom
          const samePoint =
            Number(from.lat) === Number(to.lat) &&
            Number(from.lng) === Number(to.lng);

          if (samePoint) {
            map.setCenter({ lat: Number(from.lat), lng: Number(from.lng) });
            map.setZoom(14);
          } else {
            // Fit both with padding
            map.fitBounds(bounds, padding);
          }

          // Simple A→B line (visual)
          polyline = new google.maps.Polyline({
            path: [
              { lat: Number(from.lat), lng: Number(from.lng) },
              { lat: Number(to.lat),   lng: Number(to.lng) },
            ],
            strokeOpacity: 0.6,
            strokeWeight: 3,
            map,
          });
        } else {
          // Only one point — center & zoom
          map.setCenter(markers[0].getPosition());
          map.setZoom(14);
        }

        // Handle container resize shortly after mount (e.g. tabs/cards)
        resizeTimer = setTimeout(() => {
          if (map && (hasFrom || hasTo)) {
            google.maps.event.trigger(map, "resize");
            if (hasFrom && hasTo) {
              const b = new google.maps.LatLngBounds();
              markers.forEach((m) => b.extend(m.getPosition()));
              map.fitBounds(b, padding);
            }
          }
        }, 50);
      } catch (e) {
        setError(e?.message || "Map failed to load");
      }
    };

    // Lazy initialization via IntersectionObserver
    const maybeInit = () => {
      if (visible) {
        initMap();
      } else if (containerRef.current && "IntersectionObserver" in window) {
        observerRef.current = new IntersectionObserver((entries) => {
          if (entries.some((en) => en.isIntersecting)) {
            visible = true;
            observerRef.current?.disconnect();
            observerRef.current = null;
            initMap();
          }
        }, { rootMargin: "100px" }); // pre‑warm just before entering viewport
        observerRef.current.observe(containerRef.current);
      } else {
        // Fallback: init immediately
        initMap();
      }
    };

    maybeInit();

    return cleanup;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [apiKey, from?.lat, from?.lng, to?.lat, to?.lng, lazy, padding]);

  if (error) {
    return (
      <div
        style={{
          width: "100%",
          height,
          borderRadius: 8,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: "#f3f4f6",
          fontSize: 12,
          color: "#6b7280",
        }}
        className={className}
      >
        {error}
      </div>
    );
  }

  return (
    <div
      ref={containerRef}
      style={{ width: "100%", height, borderRadius: 8, overflow: "hidden" }}
      className={className}
    />
  );
}
