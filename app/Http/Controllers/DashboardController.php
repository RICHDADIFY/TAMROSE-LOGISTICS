<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : now()->subDays(6)->startOfDay();
        $to   = $request->date_to   ? Carbon::parse($request->date_to)->endOfDay()   : now()->endOfDay();

        // Requests created per day
        $reqTrend = DB::table('trip_requests')
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        // Trips departing per day
        $tripTrend = DB::table('trips')
            ->selectRaw('DATE(depart_at) d, COUNT(*) c')
            ->whereBetween('depart_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        // On-time: depart_at within ±10 minutes of desired_departure
        $onTimeRaw = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->selectRaw('DATE(t.depart_at) d,
                         SUM(CASE WHEN ABS(TIMESTAMPDIFF(MINUTE, r.desired_departure, t.depart_at)) <= 10 THEN 1 ELSE 0 END) on_time,
                         COUNT(*) total')
            ->whereBetween('t.depart_at', [$from, $to])
            ->groupBy('d')->orderBy('d')->get();

        $onTimeTrend = $onTimeRaw->map(fn($x)=>[
            'd'   => $x->d,
            'pct' => $x->total ? round($x->on_time * 100 / $x->total, 1) : 0
        ]);

        // Pending approvals queue
        $approvals = DB::table('trip_requests as r')
            ->leftJoin('users as u','u.id','=','r.user_id')
            ->select('u.name as user.name','r.from_location','r.to_location','r.desired_departure','r.passengers')
            ->where('r.status','pending')
            ->orderBy('r.desired_departure')
            ->limit(30)->get();

        // Unassigned upcoming trips (next 7 days)
        $unassigned = DB::table('trips as t')
            ->leftJoin('trip_requests as r','r.trip_id','=','t.id')
            ->whereNull('t.driver_id')
            ->whereBetween('t.depart_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->orderBy('t.depart_at')
            ->limit(15)
            ->get([
                't.id',
                't.depart_at',
                DB::raw("CONCAT(COALESCE(r.from_location,''),' → ',COALESCE(r.to_location,'')) as route"),
                DB::raw('COALESCE(r.passengers, 0) as passengers'),
            ]);

        // Driver leaderboard (by trips in range)
        $driverBoard = DB::table('trips as t')
            ->leftJoin('users as u','u.id','=','t.driver_id')
            ->selectRaw('COALESCE(u.name,"(Unassigned)") as driver_name, COUNT(*) as trips')
            ->whereBetween('t.depart_at', [$from, $to])
            ->groupBy('driver_name')
            ->orderByDesc('trips')
            ->limit(10)->get();

        // KPIs
        $pending = DB::table('trip_requests')->where('status','pending')->count();
        $tripsToday = DB::table('trips')->whereDate('depart_at', now()->toDateString())->count();

        $resp = DB::table('trip_requests')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, approved_at)) as avgm')
            ->value('avgm');

        $assignRate = DB::table('trips')
            ->selectRaw('SUM(CASE WHEN driver_id IS NOT NULL THEN 1 ELSE 0 END)/COUNT(*)')
            ->whereBetween('depart_at', [$from, $to])
            ->value(DB::raw('SUM(CASE WHEN driver_id IS NOT NULL THEN 1 ELSE 0 END)/COUNT(*)')) ?? 0;

        $cancelRate = DB::table('trip_requests')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END)/COUNT(*)')
            ->value(DB::raw('SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END)/COUNT(*)')) ?? 0;

        $onTimeTotal = $onTimeRaw->sum('on_time'); $onTimeAll = $onTimeRaw->sum('total');
        $onTimeRate = $onTimeAll ? $onTimeTotal / $onTimeAll : 0;

        // Insights (lightweight)
        $peakHour = DB::table('trip_requests')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->selectRaw('HOUR(created_at) h, COUNT(*) c')
            ->groupBy('h')->orderByDesc('c')->limit(1)->first();

        $worstWeekday = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->whereBetween('t.depart_at', [now()->subDays(30), now()])
            ->selectRaw('DAYNAME(t.depart_at) d,
                         SUM(CASE WHEN ABS(TIMESTAMPDIFF(MINUTE, r.desired_departure, t.depart_at)) <= 10 THEN 1 ELSE 0 END) on_time,
                         COUNT(*) total')
            ->groupBy('d')->get()
            ->map(fn($x)=>['d'=>$x->d,'pct'=>$x->total ? round($x->on_time*100/$x->total):0])
            ->sortBy('pct')->first();

        $topRoute = DB::table('trip_requests')
            ->selectRaw('CONCAT(from_location," → ",to_location) as route, COUNT(*) c')
            ->groupBy('route')->orderByDesc('c')->limit(1)->first();

        $driversAttention = DB::table('trips as t')
            ->join('trip_requests as r','r.trip_id','=','t.id')
            ->leftJoin('users as u','u.id','=','t.driver_id')
            ->selectRaw('COALESCE(u.name,"(Unassigned)") as name,
                         SUM(CASE WHEN ABS(TIMESTAMPDIFF(MINUTE, r.desired_departure, t.depart_at)) <= 10 THEN 1 ELSE 0 END) ontime,
                         COUNT(*) total')
            ->whereBetween('t.depart_at', [now()->subDays(30), now()])
            ->groupBy('name')->get()
            ->map(fn($x)=>['name'=>$x->name,'pct'=>$x->total?round($x->ontime*100/$x->total):0,'total'=>$x->total])
            ->filter(fn($x)=> $x['total']>=5 && $x['pct'] < 70)
            ->pluck('name')->slice(0,3)->implode(', ');

        $insights = [
            ['title'=>'Peak request hour','value'=> isset($peakHour->h) ? sprintf('%02d:00', $peakHour->h) : '—', 'note'=>'last 30 days'],
            ['title'=>'Worst on-time weekday','value'=> $worstWeekday->d ?? '—', 'note'=> isset($worstWeekday['pct']) ? $worstWeekday['pct'].'% on-time' : null],
            ['title'=>'Top route','value'=> $topRoute->route ?? '—', 'note'=> isset($topRoute->c) ? $topRoute->c.' reqs' : null],
            ['title'=>'Drivers to check','value'=> $driversAttention ?: '—', 'note'=>'on-time < 70%, trips ≥ 5'],
        ];

        return Inertia::render('Dashboard/Index', [
            'filters' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()],
            'canExport' => true,
            'data' => [
                'kpis' => [
                    'pending' => $pending,
                    'trips_today' => $tripsToday,
                    'on_time_rate' => $onTimeRate,
                    'avg_response_min' => $resp ? round($resp,1) : null,
                    'assign_rate' => (float)$assignRate,
                    'cancel_rate' => (float)$cancelRate,
                ],
                'req_trend' => $reqTrend,
                'trip_trend' => $tripTrend,
                'on_time_trend' => $onTimeTrend,
                'top_routes' => DB::table('trip_requests')
                    ->selectRaw('CONCAT(from_location," → ",to_location) as route, COUNT(*) c')
                    ->groupBy('route')->orderByDesc('c')->limit(8)->get(),
                'approvals' => $approvals,
                'unassigned' => $unassigned,
                'driver_board' => $driverBoard,
                'insights' => $insights,
            ],
        ]);
    }
}
