import { Fragment } from "react";

const days = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];

export default function TemporalHeatmap({ data }) {
    const byCell = new Map(data.map((item) => [`${item.weekday}-${item.hour}`, item.value]));
    const max = Math.max(...data.map((item) => item.value), 1);

    const color = (value) => {
        const intensity = value / max;
        if (!value) return "bg-slate-50";
        if (intensity > 0.75) return "bg-cyan-700";
        if (intensity > 0.5) return "bg-cyan-500";
        if (intensity > 0.25) return "bg-cyan-300";
        return "bg-cyan-100";
    };

    return (
        <div className="rounded-[24px] border border-white/70 bg-white p-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
            <div className="mb-5">
                <h2 className="text-base font-semibold text-slate-950">Días y horarios con más visitas</h2>
                <p className="text-xs text-slate-500">Ayuda a entender cuándo conviene publicar novedades o revisar consultas. Horario Argentina.</p>
            </div>

            <div className="overflow-x-auto rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                <div className="grid min-w-[720px] grid-cols-[44px_repeat(24,minmax(22px,1fr))] gap-1">
                    <div />
                    {Array.from({ length: 24 }, (_, hour) => (
                        <div key={hour} className="text-center text-[10px] text-slate-400">{hour}</div>
                    ))}
                    {days.map((day, weekday) => (
                        <Fragment key={day}>
                            <div key={`${day}-label`} className="flex h-7 items-center text-xs font-medium text-slate-500">{day}</div>
                            {Array.from({ length: 24 }, (_, hour) => {
                                const value = byCell.get(`${weekday}-${hour}`) || 0;
                                return (
                                    <div
                                        key={`${weekday}-${hour}`}
                                        className={`h-7 rounded-md border border-white ${color(value)}`}
                                        title={`${day} ${hour}:00 - ${value} vistas`}
                                    />
                                );
                            })}
                        </Fragment>
                    ))}
                </div>
            </div>
        </div>
    );
}
