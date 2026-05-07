import AdminLayout from "@/Layouts/AdminLayout";
import { useForm } from "@inertiajs/react";
import { Icon } from "@iconify/react";

export default function FloatingWhatsapp({ whatsapp }) {
    const { data, setData, post, processing, errors } = useForm({
        phone: whatsapp?.phone || "",
        message: whatsapp?.message || "",
        label: whatsapp?.label || "WhatsApp",
        is_active: whatsapp?.is_active ?? true,
    });

    const digits = String(data.phone || "").replace(/\D+/g, "");
    const previewUrl = digits
        ? `https://wa.me/${digits}${data.message ? `?text=${encodeURIComponent(data.message)}` : ""}`
        : null;

    const submit = (event) => {
        event.preventDefault();
        post("/admin/extras/whatsapp");
    };

    return (
        <AdminLayout>
            <div className="max-w-4xl">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">WhatsApp flotante</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Administrá el botón flotante que aparece en la web pública para consultas directas.
                    </p>
                </div>

                <div className="grid lg:grid-cols-[1fr_320px] gap-6">
                    <form onSubmit={submit} className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
                        <div className="flex items-start justify-between gap-4 pb-5 border-b border-gray-100">
                            <div>
                                <p className="text-sm font-semibold text-gray-800">Estado del botón</p>
                                <p className="text-xs text-gray-400 mt-1">
                                    Podés ocultarlo temporalmente sin perder el número ni el mensaje.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setData("is_active", !data.is_active)}
                                className={`relative inline-flex h-7 w-12 items-center rounded-full transition ${data.is_active ? "bg-[#25A7CA]" : "bg-gray-200"}`}
                                aria-pressed={data.is_active}
                            >
                                <span
                                    className={`inline-block h-5 w-5 rounded-full bg-white shadow transition ${data.is_active ? "translate-x-6" : "translate-x-1"}`}
                                />
                            </button>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Número de WhatsApp
                            </label>
                            <input
                                type="text"
                                value={data.phone}
                                onChange={(event) => setData("phone", event.target.value)}
                                placeholder="Ej: +54 9 11 2345 6789"
                                className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30"
                            />
                            {errors.phone && <p className="text-xs text-red-500 mt-1">{errors.phone}</p>}
                            <p className="text-xs text-gray-400 mt-1">
                                Incluí código de país y área. La web usará automáticamente el formato correcto de wa.me.
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Mensaje prellenado
                            </label>
                            <textarea
                                value={data.message}
                                onChange={(event) => setData("message", event.target.value)}
                                maxLength={500}
                                rows={4}
                                placeholder="Hola, quiero hacer una consulta desde la web de CONAR."
                                className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30 resize-none"
                            />
                            {errors.message && <p className="text-xs text-red-500 mt-1">{errors.message}</p>}
                            <div className="flex justify-between gap-3 mt-1">
                                <p className="text-xs text-gray-400">
                                    Aparece escrito cuando el visitante abre WhatsApp.
                                </p>
                                <span className="text-xs text-gray-300">{data.message.length}/500</span>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Etiqueta flotante
                            </label>
                            <input
                                type="text"
                                value={data.label}
                                onChange={(event) => setData("label", event.target.value)}
                                maxLength={40}
                                placeholder="WhatsApp"
                                className="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30"
                            />
                            {errors.label && <p className="text-xs text-red-500 mt-1">{errors.label}</p>}
                            <p className="text-xs text-gray-400 mt-1">
                                Se muestra al pasar el mouse o navegar con teclado.
                            </p>
                        </div>

                        <div className="flex justify-end gap-3 pt-5 border-t border-gray-100">
                            {previewUrl && (
                                <a
                                    href={previewUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 inline-flex items-center gap-2"
                                >
                                    <Icon icon="solar:external-link-outline" width={15} />
                                    Probar link
                                </a>
                            )}
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-5 py-2 bg-[#25A7CA] text-white rounded-xl text-sm font-medium hover:bg-[#1d96b8] disabled:opacity-60 inline-flex items-center gap-2"
                            >
                                {processing ? <Icon icon="svg-spinners:ring-resize" width={14} /> : <Icon icon="solar:check-circle-outline" width={14} />}
                                Guardar
                            </button>
                        </div>
                    </form>

                    <aside className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 h-fit">
                        <p className="text-sm font-semibold text-gray-800 mb-1">Vista operativa</p>
                        <p className="text-xs text-gray-400 mb-5">
                            Así queda la configuración funcional del botón público.
                        </p>

                        <div className="rounded-2xl border border-gray-100 bg-gray-50 p-5 space-y-4">
                            <div className="flex items-center gap-3">
                                <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${data.is_active ? "bg-green-100 text-green-600" : "bg-gray-100 text-gray-400"}`}>
                                    <Icon icon="solar:phone-calling-rounded-bold" width={20} />
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-gray-800">{data.is_active ? "Activo" : "Oculto"}</p>
                                    <p className="text-xs text-gray-400">{digits || "Sin número configurado"}</p>
                                </div>
                            </div>

                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">URL final</p>
                                <p className="text-xs text-gray-500 break-all">
                                    {previewUrl || "Completá un número para generar la URL."}
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}
