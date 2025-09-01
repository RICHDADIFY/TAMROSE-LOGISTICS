<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Exports\DashboardExport;           // <-- we’ll use this
use Maatwebsite\Excel\Facades\Excel;      // requires maatwebsite/excel

class DashboardExportController extends Controller
{
    /** Build the same dataset the Dashboard uses (quick copy). */
    private function dataset(Request $req): array
    {
        $from = $req->date_from ? Carbon::parse($req->date_from)->startOfDay() : Carbon::now()->subDays(6)->startOfDay();
        $to   = $req->date_to   ? Carbon::parse($req->date_to)->endOfDay()     : Carbon::now()->endOfDay();

        $approvals = DB::table('trip_requests as r')->leftJoin('users as u','u.id','=','r.user_id')
            ->select('u.name','r.from_location','r.to_location','r.desired_departure','r.passengers')
            ->where('r.status','pending')->orderBy('r.desired_departure')->get();

        $unassigned = DB::table('trips as t')->leftJoin('trip_requests as r','r.trip_id','=','t.id')
            ->whereNull('t.driver_id')
            ->whereBetween('t.depart_at', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
            ->orderBy('t.depart_at')->get([
                't.depart_at',
                DB::raw("CONCAT(COALESCE(r.from_location,''),' → ',COALESCE(r.to_location,'')) as route"),
                DB::raw('COALESCE(r.passengers,0) as passengers'),
            ]);

        $kpis = [
            'pending'     => DB::table('trip_requests')->where('status','pending')->count(),
            'trips_today' => DB::table('trips')->whereDate('depart_at', Carbon::now()->toDateString())->count(),
        ];

        return compact('approvals','unassigned','kpis','from','to');
    }

    /** CSV (no package) */
    public function csv(Request $request): StreamedResponse
    {
        $data = $this->dataset($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="dashboard.csv"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Dashboard export', Carbon::now()->toDateTimeString()]);
            fputcsv($out, ['Range', $data['from'], $data['to']]);
            fputcsv($out, []);

            fputcsv($out, ['KPI','Value']);
            foreach ($data['kpis'] as $k=>$v) fputcsv($out, [$k, $v]);
            fputcsv($out, []);

            fputcsv($out, ['Pending Approvals']);
            fputcsv($out, ['name','from','to','desired_departure','passengers']);
            foreach ($data['approvals'] as $r) {
                fputcsv($out, [$r->name, $r->from_location, $r->to_location, $r->desired_departure, $r->passengers]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Unassigned Trips']);
            fputcsv($out, ['route','depart_at','passengers']);
            foreach ($data['unassigned'] as $r) {
                fputcsv($out, [$r->route, $r->depart_at, $r->passengers]);
            }

            fclose($out);
        }, 'dashboard.csv', $headers);
    }

    /** PDF (dompdf) */
    public function pdf(Request $request)
    {
        $data = $this->dataset($request);
        $pdf = Pdf::loadView('exports.dashboard', $data)->setPaper('a4', 'portrait');
        return $pdf->download('dashboard.pdf');
    }

    /** Excel (multi-sheet; requires maatwebsite/excel) */
    public function excel(Request $request)
    {
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            abort(501, 'Excel export not installed. Run: composer require maatwebsite/excel');
        }

        // Pass only the date filters; DashboardExport normalizes them internally
        return Excel::download(
            new DashboardExport($request->only(['date_from','date_to'])),
            'dashboard.xlsx'
        );
    }
}
