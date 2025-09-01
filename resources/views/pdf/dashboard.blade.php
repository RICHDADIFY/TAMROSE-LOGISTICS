<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { border: 1px solid #ddd; padding: 6px; }
    th { background: #f3f4f6; text-align: left; }
  </style>
</head>
<body>
  <h2>Tamrose Logistics — Dashboard</h2>
  <p>Period: {{ $data['period']['from'] }} → {{ $data['period']['to'] }}</p>

  <h3>KPIs</h3>
  <table>
    @foreach($data['kpis'] as $k=>$v)
      <tr><th>{{ $k }}</th><td>{{ is_numeric($v)?$v:json_encode($v) }}</td></tr>
    @endforeach
  </table>

  <h3>Top Routes</h3>
  <table>
    <tr><th>Route</th><th>Count</th></tr>
    @foreach($data['top_routes'] as $r)
      <tr><td>{{ $r->route }}</td><td>{{ $r->c }}</td></tr>
    @endforeach
  </table>

  <h3>Pending Approvals</h3>
  <table>
    <tr><th>From</th><th>To</th><th>Desired</th><th>Pax</th></tr>
    @foreach($data['approvals'] as $a)
      <tr><td>{{ $a['from_location'] }}</td><td>{{ $a['to_location'] }}</td><td>{{ $a['desired_departure'] }}</td><td>{{ $a['passengers'] }}</td></tr>
    @endforeach
  </table>
</body>
</html>
