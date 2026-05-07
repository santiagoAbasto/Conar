export default function TimeSeriesChart({ data }) {
    const max = Math.max(...data.map((item) => item.page_views), 1);
    const totalViews = data.reduce((sum, item) => sum + item.page_views, 0);
    const totalSessions = data.reduce((sum, item) => sum + item.sessions, 0);

    return (
        <div className="rounded-[24px] border border-white/70 bg-white p-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
            <div className="mb-5 flex items-center justify-between">
                <div>
                    <h2 className="text-base font-semibold text-slate-950">Visitas por hora</h2>
                    <p className="text-xs text-slate-500">Movimiento del sitio durante las últimas 24 horas, en horario Argentina</p>
                </div>
                <div className="hidden items-center gap-2 md:flex">
                    <span className="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">{totalViews} vistas</span>
                    <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{totalSessions} visitas</span>
                </div>
            </div>

            <div className="relative flex h-60 items-end gap-2 rounded-2xl border border-slate-100 bg-[linear-gradient(180deg,#F8FAFC_0%,#FFFFFF_100%)] px-4 pb-7 pt-6">
                <div className="pointer-events-none absolute inset-x-4 top-1/4 border-t border-dashed border-slate-200" />
                <div className="pointer-events-none absolute inset-x-4 top-1/2 border-t border-dashed border-slate-200" />
                <div className="pointer-events-none absolute inset-x-4 top-3/4 border-t border-dashed border-slate-200" />
                {data.length === 0 && (
                    <div className="grid h-full w-full place-items-center rounded-xl bg-slate-50 text-sm text-slate-400">
                        Todavía no hay visitas suficientes para mostrar
                    </div>
                )}
                {data.map((item, index) => (
                    <div key={`${item.label}-${index}`} className="relative z-10 flex min-w-0 flex-1 flex-col items-center gap-2">
                        <div className="flex h-44 w-full items-end justify-center gap-1 px-1">
                            <div
                                className="w-3 rounded-t-full bg-[linear-gradient(180deg,#22D3EE,#0891B2)] shadow-[0_8px_18px_rgba(8,145,178,0.24)]"
                                style={{ height: `${Math.max((item.page_views / max) * 100, item.page_views ? 6 : 0)}%` }}
                                title={`${item.page_views} vistas`}
                            />
                            <div
                                className="w-3 rounded-t-full bg-slate-400/80"
                                style={{ height: `${Math.max((item.sessions / max) * 100, item.sessions ? 6 : 0)}%` }}
                                title={`${item.sessions} visitas`}
                            />
                        </div>
                        <span className="hidden text-[10px] font-medium text-slate-400 sm:block">{item.label}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
