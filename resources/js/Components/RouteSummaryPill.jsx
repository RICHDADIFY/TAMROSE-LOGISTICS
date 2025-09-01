export default function RouteSummaryPill({ summary }) {
  if (!summary) {
    return (
      <span className="inline-flex items-center text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600">
        calculating…
      </span>
    );
  }
  return (
    <span className="inline-flex items-center gap-2 text-xs px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700">
      <span>Distance: <span className="font-medium">{summary.distance_text}</span></span>
      <span className="opacity-40">•</span>
      <span>ETA: <span className="font-medium">{summary.duration_text}</span></span>
    </span>
  );
}
