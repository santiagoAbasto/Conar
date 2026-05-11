import AdminLayout from "@/Layouts/AdminLayout";
import { useForm } from "@inertiajs/react";
import { Icon } from "@iconify/react";

import RichTextEditor from "@/Components/Admin/RichTextEditor";
import ImageUploadField from "@/Components/Admin/ImageUploadField";
import TranslatableField from "@/Components/Admin/TranslatableField";

export default function PostsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        title: { es: "", pt: "", en: "" },
        excerpt: { es: "", pt: "", en: "" },
        content: { es: "", pt: "", en: "" },
        category: { es: "", pt: "", en: "" },
        published_at: "",
        order: "A",
        is_active: true,
        is_featured: false,
        image: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post("/admin/posts", { forceFormData: true });
    };

    return (
        <AdminLayout>
            <div className="max-w-3xl">
                <div className="flex items-center gap-3 mb-6">
                    <button onClick={() => history.back()} className="p-2 rounded-lg hover:bg-gray-100 text-gray-500"><Icon icon="solar:arrow-left-outline" width={18} /></button>
                    <h1 className="text-2xl font-bold text-gray-800">Nueva Novedad</h1>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                        <TranslatableField label="Título" value={data.title} onChange={(v) => setData("title", v)} required error={errors.title} />
                        <TranslatableField label="Extracto" value={data.excerpt} onChange={(v) => setData("excerpt", v)} type="textarea" rows={3} placeholder="Resumen breve para la vista de lista..." />
                        <TranslatableField label="Contenido completo" value={data.content} onChange={(v) => setData("content", v)} type="rich" placeholder="Cuerpo completo de la novedad..." />
                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <TranslatableField label="Categoría" value={data.category} onChange={(v) => setData("category", v)} placeholder="Empresa, Productos, Eventos..." />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Fecha de publicación</label>
                                <input type="date" value={data.published_at} onChange={(e) => setData("published_at", e.target.value)} className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none" />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Orden <span className="text-gray-400 font-normal">(A, B, AA…)</span></label>
                                <input type="text" value={data.order} onChange={(e) => setData("order", e.target.value.toUpperCase())} maxLength={10} placeholder="A" className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 className="font-semibold text-gray-700 mb-4">Imagen destacada</h2>
                        <ImageUploadField
                            onChange={(f) => setData("image", f)}
                            specs={{ width: 600, height: 600, maxMB: 3, formats: ["PNG", "JPG"] }}
                            error={errors.image}
                        />
                    </div>

                    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-6">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData("is_active", e.target.checked)} className="w-4 h-4 rounded" />
                            <span className="text-sm text-gray-700">Activo</span>
                        </label>
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={data.is_featured} onChange={(e) => setData("is_featured", e.target.checked)} className="w-4 h-4 rounded" />
                            <span className="text-sm text-gray-700">Destacado</span>
                        </label>
                    </div>

                    <div className="flex justify-end gap-3">
                        <button type="button" onClick={() => history.back()} className="px-5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" disabled={processing} className="px-5 py-2.5 rounded-xl bg-[#25A7CA] text-white text-sm font-medium hover:bg-[#1d96b8] disabled:opacity-60 transition">
                            {processing ? "Guardando..." : "Crear Novedad"}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
