import { Icon } from "@iconify/react";

export default function MetricCard({ label, value, detail, icon, tone = "cyan" }) {
    const tones = {
        cyan: {
            icon: "bg-cyan-50 text-cyan-700 ring-cyan-100",
            glow: "bg-cyan-500/12",
            line: "bg-cyan-500",
        },
        emerald: {
            icon: "bg-emerald-50 text-emerald-700 ring-emerald-100",
            glow: "bg-emerald-500/12",
            line: "bg-emerald-500",
        },
        amber: {
            icon: "bg-amber-50 text-amber-700 ring-amber-100",
            glow: "bg-amber-500/14",
            line: "bg-amber-500",
        },
        rose: {
            icon: "bg-rose-50 text-rose-700 ring-rose-100",
            glow: "bg-rose-500/12",
            line: "bg-rose-500",
        },
        slate: {
            icon: "bg-slate-50 text-slate-700 ring-slate-100",
            glow: "bg-slate-500/8",
            line: "bg-slate-400",
        },
    };
    const current = tones[tone] || tones.cyan;

    return (
        <div className="group relative overflow-hidden rounded-[22px] border border-white/70 bg-white p-5 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80 transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_22px_54px_rgba(15,23,42,0.12)]">
            <div className={`absolute -right-10 -top-12 h-28 w-28 rounded-full blur-2xl ${current.glow}`} />
            <div className={`absolute inset-x-5 top-0 h-1 rounded-b-full ${current.line}`} />
            <div className="flex items-start justify-between gap-4">
                <div className="relative">
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</p>
                    <p className="mt-2 text-3xl font-bold text-slate-950">{value}</p>
                </div>
                <div className={`relative grid h-12 w-12 place-items-center rounded-2xl ring-1 transition group-hover:scale-105 ${current.icon}`}>
                    <Icon icon={icon} width={22} />
                </div>
            </div>
            {detail && <p className="relative mt-4 text-xs leading-relaxed text-slate-500">{detail}</p>}
        </div>
    );
}
