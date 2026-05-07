import Sidebar from "@/Components/Admin/Sidebar";
import Header from "@/Components/Admin/Header";
import { usePage } from "@inertiajs/react";
import Toast from "@/Components/Admin/Toast";

export default function AdminLayout({ children }) {
    const { flash } = usePage().props;

    return (
        <div className="flex h-screen bg-[#EEF3F7] font-sans text-[#111827]">
            {/* TOAST GLOBAL */}
            <Toast message={flash?.success} type="success" />
            <Toast message={flash?.error} type="error" />

            {/* SIDEBAR */}
            <div className="hidden md:block bg-white/95 border-r border-slate-200 flex-shrink-0 shadow-[10px_0_40px_rgba(15,23,42,0.04)]">
                <Sidebar />
            </div>

            {/* MAIN */}
            <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
                <Header />
                <main className="relative flex-1 overflow-y-auto px-6 py-7 lg:px-10">
                    <div className="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[linear-gradient(180deg,rgba(37,167,202,0.14),rgba(238,243,247,0))]" />
                    <div className="relative mx-auto max-w-[1440px] space-y-6">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
