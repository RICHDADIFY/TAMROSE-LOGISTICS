<?php

namespace App\Exports;

use App\Services\DashboardReport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\ArraySheet;

class DashboardExport implements WithMultipleSheets
{
    public function __construct(public array $filters = []) {}

    public function sheets(): array
    {
        // Normalize filter keys
        $filters = [
            'from' => $this->filters['date_from'] ?? ($this->filters['from'] ?? null),
            'to'   => $this->filters['date_to']   ?? ($this->filters['to']   ?? null),
        ];

        $data = DashboardReport::build($filters);

        // ---- Normalize each dataset into arrays of associative rows ----
        $kpiRows = collect($data['kpis'] ?? [])
            ->map(fn($v,$k)=> ['metric'=>$k, 'value'=>$v])
            ->values()->all();

        $reqTrend = collect($data['req_trend'] ?? [])
            ->map(fn($r)=> ['d'=>$r->d ?? ($r['d'] ?? null), 'c'=>$r->c ?? ($r['c'] ?? 0)])
            ->all();

        $tripTrend = collect($data['trip_trend'] ?? [])
            ->map(fn($r)=> ['d'=>$r->d ?? ($r['d'] ?? null), 'c'=>$r->c ?? ($r['c'] ?? 0)])
            ->all();

        $topRoutes = collect($data['top_routes'] ?? [])
            ->map(fn($r)=> ['route'=>$r->route ?? ($r['route'] ?? ''), 'c'=>$r->c ?? ($r['c'] ?? 0)])
            ->all();

        $approvals = collect($data['approvals'] ?? [])
            ->map(fn($r)=> [
                'name'               => $r->name               ?? ($r['name']               ?? ''),
                'from_location'      => $r->from_location      ?? ($r['from_location']      ?? ''),
                'to_location'        => $r->to_location        ?? ($r['to_location']        ?? ''),
                'desired_departure'  => $r->desired_departure  ?? ($r['desired_departure']  ?? ''),
                'passengers'         => $r->passengers         ?? ($r['passengers']         ?? 0),
            ])->all();

        $unassigned = collect($data['unassigned'] ?? [])
            ->map(fn($r)=> [
                'route'      => $r->route      ?? ($r['route']      ?? ''),
                'depart_at'  => $r->depart_at  ?? ($r['depart_at']  ?? ''),
                'passengers' => $r->passengers ?? ($r['passengers'] ?? 0),
            ])->all();

        $driverBoard = collect($data['driver_board'] ?? [])
            ->map(fn($r)=> [
                'driver_name' => $r->driver_name ?? ($r['driver_name'] ?? ''),
                'trips'       => $r->trips       ?? ($r['trips']       ?? 0),
            ])->all();

        // Insights can be either key=>value map or [ {title,value,note}, ... ]
        $ins = $data['insights'] ?? [];
        if (is_array($ins) && isset($ins[0]) && (isset($ins[0]['title']) || isset($ins[0]->title))) {
            // Array of cards -> flatten
            $insightRows = collect($ins)->map(function ($x) {
                $x = (array)$x;
                return ['metric'=>$x['title'] ?? '', 'value'=>$x['value'] ?? '', 'note'=>$x['note'] ?? null];
            })->all();
            $insightHeaders = ['metric','value','note'];
        } else {
            // Key/value map (from service)
            $insightRows = collect($ins)->map(fn($v,$k)=> [
                'metric'=>$k,
                'value'=> is_scalar($v) ? $v : (is_null($v) ? '' : json_encode($v)),
            ])->values()->all();
            $insightHeaders = ['metric','value'];
        }

        // ---- Build sheets with explicit headers (avoids empty/undefined errors) ----
        return [
            new ArraySheet('KPIs',               $kpiRows,     ['metric','value']),
            new ArraySheet('Requests Trend',     $reqTrend,    ['d','c']),
            new ArraySheet('Trips Trend',        $tripTrend,   ['d','c']),
            new ArraySheet('Top Routes',         $topRoutes,   ['route','c']),
            new ArraySheet('Pending Approvals',  $approvals,   ['name','from_location','to_location','desired_departure','passengers']),
            new ArraySheet('Unassigned Trips',   $unassigned,  ['route','depart_at','passengers']),
            new ArraySheet('Driver Leaderboard', $driverBoard, ['driver_name','trips']),
            new ArraySheet('Insights',           $insightRows, $insightHeaders),
        ];
    }
}
