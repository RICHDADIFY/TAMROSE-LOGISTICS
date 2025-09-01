<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardReport
{
    public static function build(array $filters): array
    {
        $from = Carbon::parse($filters['date_from'] ?? now()->subDays(6)->toDateString())->startOfDay();
        $to   = Carbon::parse($filters['date_to']   ?? now()->toDateString())->endOfDay();

        $trr = DB::table('trip_requests');
        $trp = DB::table('trips');
        $veh = DB::table('vehicles');

        $pending = (clone $trr)
            ->whereBetween('created_at', [$from,$to])
            ->when($filters['status'] ?? null, fn($q,$s)=>$q->where('status',$s))
            ->where('status','pending')->count();

        $tripsToday = (clone $trp)->whereBetween('depart_at', [now()->startOfDay(), now()->endOfDay()])->count();

        $tripWindow  = (clone $trp)->whereBetween('depart_at', [$from,$to]);
        $tripCnt     = (clone $tripWindow)->count();
        $completeCnt = (clone $tripWindow)->whereNotNull('return_at')->count();
        $completion  = $tripCnt ? round($completeCnt / $tripCnt, 3) : 0;

        $avgResp = (clone $trr)->whereBetween('created_at', [$from,$to])
            ->whereNotNull('approved_at')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, approved_at)'));
        $avgResp = $avgResp ? round($avgResp, 1) : 0;

        $busyVehicles = (clone $trp)
            ->whereBetween('depart_at', [$from,$to])
            ->whereIn('status',['in_progress','assigned','scheduled'])
            ->whereNotNull('vehicle_id')->distinct()->count('vehicle_id');

        $totalVehicles = (clone $veh)->count();
        $utilization   = $totalVehicles ? round($busyVehicles / $totalVehicles, 3) : 0;

        $cancelCount = (clone $trr)->whereBetween('created_at', [$from,$to])->where('status','cancelled')->count();
        $reqTotal    = (clone $trr)->whereBetween('created_at', [$from,$to])->count();
        $cancelRate  = $reqTotal ? round($cancelCount / $reqTotal, 3) : 0;

        $reqTrend = (clone $trr)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->whereBetween('created_at', [$from,$to])
            ->groupBy('d')->orderBy('d')->get();

        $tripTrend = (clone $trp)
            ->selectRaw('DATE(depart_at) d, COUNT(*) c')
            ->whereBetween('depart_at', [$from,$to])
            ->groupBy('d')->orderBy('d')->get();

        $topRoutes = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->selectRaw("CONCAT(r.from_location,' → ',r.to_location) route, COUNT(*) c")
            ->groupBy('route')->orderByDesc('c')->limit(7)->get();

        $heat = (clone $trr)
            ->selectRaw('WEEKDAY(created_at) d, HOUR(created_at) h, COUNT(*) c')
            ->whereBetween('created_at', [$from,$to])->groupBy('d','h')->get();

        $approvals = DB::table('trip_requests as r')
            ->leftJoin('users as u','u.id','=','r.user_id')
            ->where('r.status','pending')
            ->orderBy('r.desired_departure')->limit(15)
            ->get(['r.id','r.user_id','r.from_location','r.to_location','r.desired_departure','r.passengers','u.name as user_name'])
            ->map(fn($x)=>[
                'id'=>$x->id,'user_id'=>$x->user_id,
                'from_location'=>$x->from_location,'to_location'=>$x->to_location,
                'desired_departure'=>$x->desired_departure,'passengers'=>$x->passengers,
                'user'=>['name'=>$x->user_name],
            ]);

        $unassigned = DB::table('trips as t')
            ->leftJoin('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->whereNull('t.driver_id')
            ->orderBy('t.depart_at')->limit(15)
            ->get([
                't.id','t.depart_at',
                DB::raw("COALESCE(r.passengers,0) as passengers"),
                DB::raw("CONCAT(COALESCE(r.from_location,'Unknown'),' → ',COALESCE(r.to_location,'Unknown')) as route"),
            ]);

        $driverBoard = DB::table('trips as t')
            ->leftJoin('users as u','u.id','=','t.driver_id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->whereNotNull('t.driver_id')
            ->selectRaw('t.driver_id, COALESCE(u.name,"Unknown") driver_name, COUNT(*) trips')
            ->groupBy('t.driver_id','driver_name')->orderByDesc('trips')->limit(10)->get();

        // ---------- Insights ----------
        // Peak request hour
        $peakHour = (clone $trr)->whereBetween('created_at', [$from,$to])
            ->selectRaw('HOUR(created_at) h, COUNT(*) c')->groupBy('h')->orderByDesc('c')->first();

        // On-time proxy: depart_at <= desired_departure + 10min
        $onTimeByDow = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->selectRaw('WEEKDAY(t.depart_at) dow, SUM(t.depart_at <= DATE_ADD(r.desired_departure, INTERVAL 10 MINUTE)) ontime, COUNT(*) total')
            ->groupBy('dow')->get();
        $worstDow = $onTimeByDow->map(fn($x)=>[
            'dow'=>$x->dow, 'rate'=>$x->total ? $x->ontime/$x->total : 0
        ])->sortBy('rate')->first();

        // Top route growth vs previous period
        $spanDays = $from->diffInDays($to) + 1;
        $pFrom = (clone $from)->subDays($spanDays); $pTo = (clone $from)->subDay();
        $curRoutes = DB::table('trips as t')->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->selectRaw("CONCAT(r.from_location,' → ',r.to_location) route, COUNT(*) c")
            ->groupBy('route')->pluck('c','route');
        $prevRoutes = DB::table('trips as t')->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$pFrom,$pTo])
            ->selectRaw("CONCAT(r.from_location,' → ',r.to_location) route, COUNT(*) c")
            ->groupBy('route')->pluck('c','route');
        $growth = collect($curRoutes)->map(function($c,$route) use ($prevRoutes){
            $prev = $prevRoutes[$route] ?? 0;
            return ['route'=>$route,'delta'=>$c-$prev];
        })->sortByDesc('delta')->first();

        // Drivers needing attention (rate < 0.7, trips >=5)
        $driverOnTime = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [$from,$to])
            ->whereNotNull('t.driver_id')
            ->selectRaw('t.driver_id, SUM(t.depart_at <= DATE_ADD(r.desired_departure, INTERVAL 10 MINUTE)) ontime, COUNT(*) total')
            ->groupBy('t.driver_id')->get();
        $driversAttention = $driverOnTime->filter(fn($x)=>$x->total>=5 && ($x->ontime/$x->total)<0.7)
            ->pluck('driver_id')->values();

        return [
            'kpis' => [
                'pending'=>$pending,'trips_today'=>$tripsToday,'on_time_rate'=>$completion,
                'avg_response_min'=>$avgResp,'utilization'=>$utilization,'cancel_rate'=>$cancelRate,
            ],
            'req_trend'=>$reqTrend,'trip_trend'=>$tripTrend,'top_routes'=>$topRoutes,'heat'=>$heat,
            'approvals'=>$approvals,'unassigned'=>$unassigned,'driver_board'=>$driverBoard,
            'insights' => [
                'peak_hour' => $peakHour ? (int)$peakHour->h : null,
                'worst_dow' => $worstDow ? ['dow'=>$worstDow['dow'], 'rate'=>round($worstDow['rate'],3)] : null,
                'top_growth_route' => $growth ?: null,
                'drivers_attention' => $driversAttention,
            ],
            'period' => ['from'=>$from->toDateTimeString(),'to'=>$to->toDateTimeString()],
        ];
    }
}
