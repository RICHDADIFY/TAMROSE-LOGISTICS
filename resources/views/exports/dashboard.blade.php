<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
    h1 { font-size: 18px; margin: 0 0 8px; }
    h2 { font-size: 14px; margin: 16px 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px 8px; }
    th { background: #f1f5f9; text-align: left; }
    .kpi td { border: none; }
  </style>
</head>
<body>
  <h1>Tamrose Logistics — Dashboard</h1>
  <p>Range: {{ $from }} → {{ $to }}</p>

  <table class="kpi">
    <tr><td><strong>Pending</strong></td><td>{{ $kpis['pending'] }}</td></tr>
    <tr><td><strong>Trips Today</strong></td><td>{{ $kpis['trips_today'] }}</td></tr>
  </table>

  <h2>Pending Approvals</h2>
  <table>
    <thead><tr><th>Name</th><th>From</th><th>To</th><th>Desired departure</th><th>Passengers</th></tr></thead>
    <tbody>
      @forelse($approvals as $r)
        <tr>
          <td>{{ $r->name }}</td><td>{{ $r->from_location }}</td><td>{{ $r->to_location }}</td>
          <td>{{ $r->desired_departure }}</td><td>{{ $r->passengers }}</td>
        </tr>
      @empty
        <tr><td colspan="5">No data.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Unassigned Trips (next 7 days)</h2>
  <table>
    <thead><tr><th>Route</th><th>Depart at</th><th>Passengers</th></tr></thead>
    <tbody>
      @forelse($unassigned as $r)
        <tr><td>{{ $r->route }}</td><td>{{ $r->depart_at }}</td><td>{{ $r->passengers }}</td></tr>
      @empty
        <tr><td colspan="3">No data.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
