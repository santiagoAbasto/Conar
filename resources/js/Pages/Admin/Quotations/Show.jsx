import AdminLayout from "@/Layouts/AdminLayout";
import { router } from "@inertiajs/react";
import { Icon } from "@iconify/react";

function DetailRow({ label, value }) {
    if (!value) return null;
    return (
        <div className="py-3 border-b border-gray-50 last:border-0">
            <p className="text-xs text-gray-400 mb-0.5">{label}</p>
            <p className="text-sm text-gray-800">{value}</p>
        </div>
    );
}

function ProcessData({ data }) {
    if (!data || Object.keys(data).length === 0) return null;

    return (
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
            <h2 className="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <Icon icon="solar:tuning-square-2-outline" width={16} className="text-[#25A7CA]" />
                Datos técnicos del proceso
            </h2>
            <div className="grid sm:grid-cols-2 gap-3">
                {Object.entries(data).map(([key, value]) => (
                    <div key={key} className="rounded-xl bg-gray-50 border border-gray-100 p-3">
                        <p className="text-xs text-gray-400 mb-1">{key}</p>
                        <p className="text-sm text-gray-800">{value || "—"}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function QuotationShow({ quotation }) {
    const destroy = () => {
        if (!confirm("¿Eliminar esta cotización?")) return;
        router.delete(`/admin/comercial/quotations/${quotation.id}`, { onSuccess: () => router.visit('/admin/comercial/quotations') });
    };

    return (
        <AdminLayout>
            <div className="max-w-3xl">
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                        <button onClick={() => history.back()} className="p-2 rounded-lg hover:bg-gray-100 text-gray-500"><Icon icon="solar:arrow-left-outline" width={18} /></button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-800">Cotización #{quotation.id}</h1>
                            <p className="text-sm text-gray-500">{quotation.created_at}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <a href={quotation.reply_mailto || `mailto:${quotation.email}`} className="flex items-center gap-2 px-4 py-2 bg-[#25A7CA] text-white rounded-xl text-sm hover:bg-[#1d96b8] transition">
                            <Icon icon="solar:letter-outline" width={16} />
                            Responder
                        </a>
                        <button onClick={destroy} className="flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 border border-red-100 rounded-xl text-sm hover:bg-red-100 transition">
                            <Icon icon="solar:trash-bin-trash-outline" width={16} />
                            Eliminar
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 className="font-semibold text-gray-700 mb-4 flex items-center gap-2"><Icon icon="solar:user-outline" width={16} className="text-[#25A7CA]" />Datos del solicitante</h2>
                        <DetailRow label="Nombre" value={quotation.name} />
                        <DetailRow label="Email" value={quotation.email} />
                        <DetailRow label="Teléfono" value={quotation.phone} />
                        <DetailRow label="Empresa" value={quotation.company} />
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 className="font-semibold text-gray-700 mb-4 flex items-center gap-2"><Icon icon="solar:document-add-outline" width={16} className="text-[#25A7CA]" />Detalles del pedido</h2>
                        <DetailRow label="Instrumental/Producto" value={quotation.instrumental} />
                        <DetailRow label="Solución" value={quotation.solution} />
                        <DetailRow label="Cantidad" value={quotation.quantity} />
                    </div>
                </div>

                {quotation.message && (
                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
                        <h2 className="font-semibold text-gray-700 mb-3 flex items-center gap-2"><Icon icon="solar:chat-square-outline" width={16} className="text-[#25A7CA]" />Mensaje</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-wrap">{quotation.message}</p>
                    </div>
                )}

                <ProcessData data={quotation.process_data} />

                {quotation.attachments?.length > 0 && (
                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-6">
                        <h2 className="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <Icon icon="solar:paperclip-outline" width={16} className="text-[#25A7CA]" />
                            Archivos adjuntos
                        </h2>
                        <div className="grid sm:grid-cols-2 gap-4">
                            {quotation.attachments.map((file) => (
                                <a
                                    key={file.path}
                                    href={file.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="group rounded-2xl border border-gray-100 overflow-hidden hover:border-[#25A7CA]/40 hover:shadow-sm transition bg-white"
                                >
                                    {file.is_image ? (
                                        <img src={file.url} alt={file.name} className="h-40 w-full object-cover bg-gray-50" />
                                    ) : (
                                        <div className="h-40 bg-gray-50 flex items-center justify-center">
                                            <Icon icon="solar:file-text-outline" width={38} className="text-gray-300 group-hover:text-[#25A7CA]" />
                                        </div>
                                    )}
                                    <div className="p-3 flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-gray-800 truncate">{file.name}</p>
                                            <p className="text-xs text-gray-400 uppercase">{file.extension || "archivo"}</p>
                                        </div>
                                        <Icon icon="solar:download-outline" width={17} className="text-gray-400 group-hover:text-[#25A7CA] flex-shrink-0" />
                                    </div>
                                </a>
                            ))}
                        </div>
                    </div>
                )}

                <div className="mt-6 flex gap-3">
                    <a href={quotation.reply_mailto || `mailto:${quotation.email}`} className="flex items-center gap-2 px-4 py-2.5 bg-[#25A7CA] text-white rounded-xl text-sm hover:bg-[#1d96b8] transition">
                        <Icon icon="solar:letter-outline" width={16} />
                        Responder por correo
                    </a>
                </div>
            </div>
        </AdminLayout>
    );
}
