import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useRef } from 'react';
import { LineChart, Line, BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid } from 'recharts';
import * as htmlToImage from 'html-to-image';

export default function DashboardIndex() {
  const { data, filters, canExport } = usePage().props;

  const exportBtns = canExport && (
    <div className="flex gap-2">
      <a href={route('dashboard.export.excel', filters)} className="px-3 py-1.5 rounded-lg bg-[#227442] text-white text-xs">Excel</a>
      <a href={route('dashboard.export.csv', filters)}   className="px-3 py-1.5 rounded-lg bg-slate-700 text-white text-xs">CSV</a>
      <a href={route('dashboard.export.pdf', filters)}   className="px-3 py-1.5 rounded-lg bg-blue-700 text-white text-xs">PDF</a>
    </div>
  );

  // refs for PNG exports
  const refReqTrend  = useRef(null);
  const refTripTrend = useRef(null);
  const refOnTime    = useRef(null);
  const refTopRoutes = useRef(null);

  const dl = async (ref, filename) => {
    if (!ref.current) return;
    const dataUrl = await htmlToImage.toPng(ref.current, { pixelRatio: 2, backgroundColor: '#0f172a' });
    const a = document.createElement('a'); a.href = dataUrl; a.download = filename; a.click();
  };

  return (
    <AuthenticatedLayout header={
      <div className="flex items-center justify-between">
        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">Dashboard</h2>
        {exportBtns}
      </div>
    }>
      <Head title="Dashboard" />

      <div className="p-4 md:p-6 space-y-6 dark:bg-slate-950">

        {/* Insight callouts (server-provided) */}
        {Array.isArray(data.insights) && data.insights.length > 0 && (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {data.insights.map((i, idx) => (
              <div key={idx} className="rounded-xl p-3 bg-white ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                <div className="text-xs text-slate-500 dark:text-slate-400">{i.title}</div>
                <div className="text-lg font-semibold text-slate-900 dark:text-white">{i.value}</div>
                {i.note && <div className="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{i.note}</div>}
              </div>
            ))}
          </div>
        )}

        {/* KPI cards */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
          {[
            ['Pending', data.kpis.pending],
            ['Trips Today', data.kpis.trips_today],
            ['On-Time %', Math.round((data.kpis.on_time_rate||0)*100)+'%'],
            ['Avg Response (min)', data.kpis.avg_response_min ?? '—'],
            ['Assign Rate', Math.round((data.kpis.assign_rate||0)*100)+'%'],
            ['Cancel Rate', Math.round((data.kpis.cancel_rate||0)*100)+'%'],
          ].map(([label, val]) => (
            <div key={label} className="rounded-xl p-3 bg-white ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
              <div className="text-xs text-slate-500 dark:text-slate-400">{label}</div>
              <div className="text-lg font-semibold text-slate-900 dark:text-white">{val}</div>
            </div>
          ))}
        </div>

        {/* Requests Trend */}
        <CardWithDownload title="Requests (per day)" onDownload={() => dl(refReqTrend,'requests-trend.png')}>
          <div ref={refReqTrend} className="h-64 px-2 relative overflow-hidden">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={data.req_trend} margin={{ top: 10, right: 10, bottom: 4, left: 0 }}>
                <CartesianGrid strokeOpacity={0.15} />
                <XAxis dataKey="d" />
                <YAxis />
                <Tooltip
                  wrapperStyle={{ zIndex: 10, pointerEvents: 'none' }}
                  contentStyle={{ background: 'rgba(2,6,23,.95)', border: '1px solid rgba(148,163,184,.25)' }}
                  labelStyle={{ color: '#e2e8f0' }} itemStyle={{ color: '#e2e8f0' }}
                />
                <Line type="monotone" dataKey="c" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </CardWithDownload>

        {/* Trips Trend */}
        <CardWithDownload title="Trips (per day)" onDownload={() => dl(refTripTrend,'trips-trend.png')}>
          <div ref={refTripTrend} className="h-64 px-2 relative overflow-hidden">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={data.trip_trend}>
                <CartesianGrid strokeOpacity={0.15} />
                <XAxis dataKey="d" />
                <YAxis />
                <Tooltip wrapperStyle={{ zIndex: 10, pointerEvents: 'none' }} />
                <Line type="monotone" dataKey="c" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </CardWithDownload>

        {/* On-time % */}
        <CardWithDownload title="On-Time %" onDownload={() => dl(refOnTime,'on-time.png')}>
          <div ref={refOnTime} className="h-64 px-2 relative overflow-hidden">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={data.on_time_trend}>
                <CartesianGrid strokeOpacity={0.15} />
                <XAxis dataKey="d" />
                <YAxis domain={[0, 100]} />
                <Tooltip wrapperStyle={{ zIndex: 10, pointerEvents: 'none' }} />
                <Line type="monotone" dataKey="pct" strokeWidth={2} />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </CardWithDownload>

        {/* Top Routes */}
        <CardWithDownload title="Top Routes" onDownload={() => dl(refTopRoutes,'top-routes.png')}>
          <div ref={refTopRoutes} className="h-64 px-2 relative overflow-hidden">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data.top_routes}>
                <CartesianGrid strokeOpacity={0.15} />
                <XAxis dataKey="route" hide />
                <YAxis />
                <Tooltip wrapperStyle={{ zIndex: 10, pointerEvents: 'none' }} />
                <Bar dataKey="c" />
              </BarChart>
            </ResponsiveContainer>
            <div className="mt-2 text-xs text-slate-500 dark:text-slate-400">
              {data.top_routes.slice(0,5).map(r => r.route).join(' • ')}
            </div>
          </div>
        </CardWithDownload>

        {/* Queues & Leaderboard (ensure no overlap by keeping each card independent) */}
        <div className="grid md:grid-cols-2 gap-4">
          <SimpleTable title="Pending Approvals"
                       rows={data.approvals}
                       cols={['user.name','from_location','to_location','desired_departure','passengers']} />
          <SimpleTable title="Unassigned Trips"
                       rows={data.unassigned}
                       cols={['route','depart_at','passengers']} />
        </div>

        <SimpleTable title="Driver Leaderboard" rows={data.driver_board} cols={['driver_name','trips']} />
      </div>
    </AuthenticatedLayout>
  );
}

function CardWithDownload({ title, onDownload, children }) {
  return (
    <div className="relative overflow-hidden rounded-xl p-3 bg-white ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
      <div className="flex items-center justify-between mb-1">
        <div className="text-sm font-medium text-slate-800 dark:text-slate-200">{title}</div>
        <button onClick={onDownload}
          className="text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100">
          Download PNG
        </button>
      </div>
      {children}
    </div>
  );
}

function SimpleTable({ title, rows, cols }) {
  const show = (row, path) => path.split('.').reduce((a,k)=> (a?.[k]), row);
  return (
    <div className="relative overflow-hidden rounded-xl p-3 bg-white ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
      <div className="text-sm font-medium text-slate-800 dark:text-slate-200 mb-2">{title}</div>
      <div className="overflow-x-auto">
        <table className="min-w-full text-xs">
          <thead>
            <tr className="text-left text-slate-500 dark:text-slate-400">
              {cols.map(c => <th key={c} className="py-2 pe-4">{c.replace('.',' ')}</th>)}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {rows.map((r, i) => (
              <tr key={i} className="text-slate-800 dark:text-slate-200">
                {cols.map(c => <td key={c} className="py-2 pe-4">{String(show(r,c) ?? '')}</td>)}
              </tr>
            ))}
            {rows.length === 0 && (
              <tr><td colSpan={cols.length} className="py-6 text-center text-slate-500 dark:text-slate-400">No data.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
