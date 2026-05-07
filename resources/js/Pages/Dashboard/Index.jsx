import AdminLayout from "@/Layouts/AdminLayout";
import GeoMap from "@/Components/Analytics/GeoMap";
import MetricCard from "@/Components/Analytics/MetricCard";
import TemporalHeatmap from "@/Components/Analytics/TemporalHeatmap";
import TimeSeriesChart from "@/Components/Analytics/TimeSeriesChart";
import { Icon } from "@iconify/react";
import { Head, router } from "@inertiajs/react";

const number = new Intl.NumberFormat("es-AR");

function formatDuration(seconds = 0) {
    const minutes = Math.floor(seconds / 60);
    const rest = seconds % 60;
    if (minutes <= 0) return `${rest}s`;
    return `${minutes}m ${rest}s`;
}

export default function Dashboard({ stats, recent_quotes, recent_messages, analytics }) {
    const kpis = analytics.kpis;

    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <div className="space-y-8">
                <div className="relative overflow-hidden rounded-[28px] border border-white/70 bg-[#123A54] p-7 text-white shadow-[0_24px_70px_rgba(18,58,84,0.24)]">
                    <div className="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-[#25A7CA]/30 blur-3xl" />
                    <div className="absolute bottom-0 right-10 h-28 w-72 rounded-full bg-emerald-400/12 blur-3xl" />
                    <div className="relative grid gap-6 xl:grid-cols-[1fr_420px] xl:items-end">
                        <div>
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-cyan-100">
                                <span className="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_0_5px_rgba(110,231,183,0.18)]" />
                                Actividad del sitio
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight lg:text-4xl">Resumen general</h1>
                            <p className="mt-3 max-w-3xl text-sm leading-6 text-cyan-50/80">
                                Una mirada simple de cuántas personas visitan la web, qué páginas miran, desde dónde llegan y qué actividad sospechosa fue bloqueada.
                            </p>
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <HeroStat label="Vistas 7 días" value={number.format(kpis.page_views_7d)} />
                            <HeroStat label="Visitas" value={number.format(kpis.sessions_7d)} />
                            <HeroStat label="Personas" value={number.format(kpis.visitors_7d)} />
                        </div>
                    </div>
                </div>

                <section className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <MetricCard
                        label="Personas activas ahora"
                        value={number.format(kpis.active_users)}
                        detail="Personas navegando el sitio durante los últimos 5 minutos"
                        icon="solar:user-check-rounded-outline"
                        tone="emerald"
                    />
                    <MetricCard
                        label="Promedio en el sitio"
                        value={formatDuration(kpis.avg_session_seconds)}
                        detail="Tiempo promedio que cada visita permanece navegando"
                        icon="solar:clock-circle-outline"
                        tone="cyan"
                    />
                    <MetricCard
                        label="Accesos sospechosos bloqueados"
                        value={number.format(kpis.blocked_bots)}
                        detail="Intentos automáticos o inseguros frenados en los últimos 7 días"
                        icon="solar:shield-warning-outline"
                        tone="rose"
                    />
                </section>

                <section className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <MetricCard label="Vistas 7 días" value={number.format(kpis.page_views_7d)} icon="solar:eye-outline" tone="slate" />
                    <MetricCard label="Visitas 7 días" value={number.format(kpis.sessions_7d)} icon="solar:graph-up-outline" tone="slate" />
                    <MetricCard label="Personas 7 días" value={number.format(kpis.visitors_7d)} icon="solar:users-group-rounded-outline" tone="slate" />
                    <MetricCard label="Tiempo total en la web" value={formatDuration(kpis.engaged_seconds_7d)} icon="solar:stopwatch-outline" tone="amber" />
                </section>

                <section className="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <TimeSeriesChart data={analytics.time_series} />
                    <div className="rounded-[24px] border border-white/70 bg-white p-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
                        <div className="mb-5 flex items-center justify-between">
                            <div>
                                <h2 className="text-base font-semibold text-slate-950">Páginas más vistas</h2>
                                <p className="text-xs text-slate-500">Qué secciones interesaron más en los últimos 7 días</p>
                            </div>
                            <div className="grid h-10 w-10 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100">
                                <Icon icon="solar:ranking-outline" width={20} />
                            </div>
                        </div>
                        <div className="space-y-3">
                            {analytics.top_pages.length === 0 && <p className="text-sm text-slate-400">Todavía no hay visitas suficientes para mostrar.</p>}
                            {analytics.top_pages.map((page) => (
                                <div key={page.path} className="group flex items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 transition hover:border-cyan-100 hover:bg-cyan-50/40">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-slate-800">{page.path}</p>
                                        <p className="text-xs text-slate-500">{number.format(page.sessions)} visitas</p>
                                    </div>
                                    <span className="text-sm font-bold text-slate-950">{number.format(page.page_views)}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <GeoMap points={analytics.geo} />
                    <TemporalHeatmap data={analytics.heatmap} />
                </section>

                <section className="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_0.9fr]">
                    <div className="rounded-[24px] border border-white/70 bg-white shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
                        <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                            <div>
                                <h2 className="text-base font-semibold text-slate-950">Navegaciones recientes</h2>
                                <p className="text-xs text-slate-500">Últimas personas que estuvieron recorriendo el sitio</p>
                            </div>
                            <div className="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <Icon icon="solar:pulse-outline" width={20} />
                            </div>
                        </div>
                        <div className="divide-y divide-slate-100">
                            {analytics.active_sessions.length === 0 && <p className="px-6 py-4 text-sm text-slate-400">No hay navegaciones recientes para mostrar.</p>}
                            {analytics.active_sessions.map((session) => (
                                <div key={session.session_id} className="grid gap-3 px-6 py-4 md:grid-cols-[1fr_140px_90px] md:items-center">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-slate-900">{session.last_path}</p>
                                        <p className="text-xs text-slate-500">{session.location} · {session.device} · visita {session.session_id}</p>
                                    </div>
                                    <p className="text-xs text-slate-500">{session.last_seen_at}</p>
                                    <p className="text-right text-sm font-semibold text-slate-900">{formatDuration(session.duration_seconds)}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6">
                        <RecentPanel
                            title="Últimos presupuestos"
                            empty="Sin solicitudes recientes."
                            action={() => router.visit("/admin/comercial/quotations")}
                            items={recent_quotes}
                            icon="solar:document-add-outline"
                            render={(item) => ({
                                title: item.name,
                                subtitle: item.company || item.email,
                                unread: !item.is_read,
                            })}
                        />
                        <RecentPanel
                            title="Últimos mensajes"
                            empty="Sin mensajes recientes."
                            action={() => router.visit("/admin/contact-messages")}
                            items={recent_messages}
                            icon="solar:letter-outline"
                            render={(item) => ({
                                title: item.name,
                                subtitle: item.email,
                                unread: !item.is_read,
                            })}
                        />
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}

function HeroStat({ label, value }) {
    return (
        <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-cyan-100/80">{label}</p>
            <p className="mt-2 text-2xl font-bold text-white">{value}</p>
        </div>
    );
}

function RecentPanel({ title, empty, action, items, icon, render }) {
    return (
        <div className="rounded-[24px] border border-white/70 bg-white shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
            <div className="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <h2 className="text-base font-semibold text-slate-950">{title}</h2>
                <button onClick={action} className="rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100 hover:text-cyan-900">Ver todos</button>
            </div>
            <div className="divide-y divide-slate-100">
                {items.length === 0 && <p className="px-6 py-4 text-sm text-slate-400">{empty}</p>}
                {items.map((item) => {
                    const view = render(item);
                    return (
                        <div key={item.id} className="flex items-center gap-3 px-6 py-3">
                            <div className="grid h-9 w-9 place-items-center rounded-xl bg-cyan-50 text-cyan-700">
                                <Icon icon={icon} width={17} />
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold text-slate-900">{view.title}</p>
                                <p className="truncate text-xs text-slate-500">{view.subtitle}</p>
                            </div>
                            {view.unread && <span className="h-2 w-2 rounded-full bg-cyan-500" />}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
