import { usePage, router } from "@inertiajs/react";
import { Icon } from "@iconify/react";

function submitLogout() {
    const token = document.head.querySelector('meta[name="csrf-token"]')?.content || '';
    const form = document.createElement("form");
    const csrf = document.createElement("input");

    form.method = "POST";
    form.action = "/admin/logout";
    form.style.display = "none";

    csrf.type = "hidden";
    csrf.name = "_token";
    csrf.value = token;

    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

export default function Header() {
    const { auth } = usePage().props;

    return (
        <header className="h-[72px] bg-white/90 border-b border-slate-200 px-6 lg:px-10 flex items-center justify-between backdrop-blur-xl">
            <div>
                <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#25A7CA]">CONAR</p>
                <h2 className="text-lg font-bold text-slate-950">
                    Panel Administrativo
                </h2>
            </div>
            <div className="flex items-center gap-4">
                {/* Search */}
                <div className="relative hidden md:block">
                    <Icon
                        icon="solar:magnifer-outline"
                        className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        type="text"
                        placeholder="Buscar..."
                        className="w-[250px] rounded-2xl border border-slate-200 bg-slate-50/80 py-2.5 pl-9 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#25A7CA]/40 focus:bg-white focus:ring-4 focus:ring-[#25A7CA]/10"
                    />
                </div>
                <button className="grid h-10 w-10 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-[#25A7CA]/30 hover:text-[#25A7CA]">
                    <Icon icon="solar:bell-outline" width={19} />
                </button>
                <div className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
                    <div className="flex h-9 w-9 items-center justify-center rounded-2xl bg-[#1F5F8B] text-sm font-bold text-white shadow-[0_10px_20px_rgba(31,95,139,0.22)]">
                        {auth?.user?.name?.charAt(0)}
                    </div>
                    <div className="hidden leading-tight lg:block">
                        <p className="text-sm font-semibold text-slate-800">{auth?.user?.name}</p>
                        <p className="text-xs text-slate-400">Administrador</p>
                    </div>
                    <button
                        onClick={submitLogout}
                        className="rounded-xl px-3 py-2 text-sm font-semibold text-red-500 transition hover:bg-red-50 hover:text-red-600"
                    >
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </header>
    );
}
