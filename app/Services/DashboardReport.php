<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReport
{
    /**
     * Build all datasets the dashboard (and exports) need.
     *
     * @param  array $filters  ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD'] (optional)
     * @param  \App\Models\User|null $user  (not used now, reserved for scoping)
     * @return array
     */
    public static function build(array $filters = [], $user = null): array
    {
        [$from, $to] = self::rangeFromFilters($filters);

        // --- KPIs ---
        $pending = DB::table('trip_requests')->where('status', 'pending')->count();

        $tripsToday = DB::table('trips')
            ->whereBetween('depart_at', [Carbon::today(), Carbon::today()->endOfDay()])
            ->count();

        $avgResponseMin = (float) DB::table('trip_requests')
            ->whereNotNull('approved_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, approved_at)')) ?? 0.0;

        $totReqInRange = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])->count();

        $cancelReq = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['cancelled', 'canceled', 'rejected'])
            ->count();

        $cancelRate = $totReqInRange ? ($cancelReq / $totReqInRange) : 0.0;

        // On-time: trip depart_at <= requested desired_departure
        $ot = DB::table('trips as t')
            ->join('trip_requests as r', 'r.trip_id', '=', 't.id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN t.depart_at <= r.desired_departure THEN 1 ELSE 0 END) as ontime')
            ->first();
        $onTimeRate = ($ot && $ot->total) ? ($ot->ontime / $ot->total) : 0.0;

        // Utilization: seats used / capacity
        $util = DB::table('trips as t')
            ->join('trip_requests as r', 'r.trip_id', '=', 't.id')
            ->join('vehicles as v', 'v.id', '=', 't.vehicle_id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->selectRaw('SUM(LEAST(r.passengers, v.capacity)) as used, SUM(v.capacity) as cap')
            ->first();
        $utilization = ($util && $util->cap) ? ($util->used / $util->cap) : 0.0;

        $kpis = [
            'pending'          => $pending,
            'trips_today'      => $tripsToday,
            'avg_response_min' => round($avgResponseMin, 1),
            'cancel_rate'      => $cancelRate,
            'on_time_rate'     => $onTimeRate,
            'utilization'      => $utilization,
        ];

        // --- Trends / charts ---
        $reqTrend = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')->get();

        $tripTrend = DB::table('trips')
            ->whereBetween('depart_at', [$from, $to])
            ->selectRaw('DATE(depart_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')->get();

        $topRoutes = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("CONCAT(from_location,' → ',to_location) as route, COUNT(*) as c")
            ->groupBy('route')->orderByDesc('c')->limit(10)->get();

        // --- Queues ---
        $approvals = DB::table('trip_requests as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.status', 'pending')
            ->orderByDesc('r.created_at')->limit(10)
            ->select('u.name', 'r.from_location', 'r.to_location', 'r.desired_departure', 'r.passengers')
            ->get();

        $unassigned = DB::table('trips as t')
            ->leftJoin('trip_requests as r', 'r.trip_id', '=', 't.id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->whereNull('t.driver_id')
            ->orderBy('t.depart_at')->limit(15)
            ->selectRaw("t.id, t.depart_at, COALESCE(CONCAT(r.from_location,' → ', r.to_location),'') as route, COALESCE(r.passengers,0) as passengers")
            ->get();

        // --- Leaderboard ---
        $driverBoard = DB::table('trips as t')
            ->join('users as u', 'u.id', '=', 't.driver_id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('u.name as driver_name, COUNT(*) as trips')
            ->limit(8)->get();

        return [
            'kpis'         => $kpis,
            'req_trend'    => $reqTrend,
            'trip_trend'   => $tripTrend,
            'top_routes'   => $topRoutes,
            'approvals'    => $approvals,
            'unassigned'   => $unassigned,
            'driver_board' => $driverBoard,
            'filters'      => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'insights'     => self::insights($from, $to),
        ];
    }

    protected static function rangeFromFilters(array $filters): array
    {
        $from = !empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : now()->subDays(6)->startOfDay();
        $to   = !empty($filters['to'])   ? Carbon::parse($filters['to'])->endOfDay()   : now()->endOfDay();
        return [$from, $to];
    }

    protected static function insights(Carbon $from, Carbon $to): array
    {
        $peak = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('HOUR(created_at) as h, COUNT(*) as c')
            ->groupBy('h')->orderByDesc('c')->limit(1)->first();

        $worstDay = DB::table('trips as t')
            ->join('trip_requests as r', 'r.trip_id', '=', 't.id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->selectRaw("DAYNAME(t.depart_at) as d, AVG(CASE WHEN t.depart_at <= r.desired_departure THEN 1 ELSE 0 END) as rate")
            ->groupBy('d')->orderBy('rate')->limit(1)->first();

        $drivers = DB::table('trips as t')
            ->join('trip_requests as r', 'r.trip_id', '=', 't.id')
            ->join('users as u', 'u.id', '=', 't.driver_id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->groupBy('u.id', 'u.name')
            ->havingRaw('COUNT(*) >= 5')
            ->orderBy('rate') // lowest first
            ->selectRaw("u.name, COUNT(*) as total, AVG(CASE WHEN t.depart_at <= r.desired_departure THEN 1 ELSE 0 END) as rate")
            ->limit(3)->get();

        return [
            'peak_hour'          => $peak->h ?? null,
            'worst_on_time_day'  => $worstDay->d ?? null,
            'drivers_attention'  => $drivers,
        ];
    }
}
