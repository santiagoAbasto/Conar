import AdminLayout from "@/Layouts/AdminLayout";
import { useForm, router } from "@inertiajs/react";
import { Icon } from "@iconify/react";

import ImageUploadField from "@/Components/Admin/ImageUploadField";
import TranslatableField, { asTranslations } from "@/Components/Admin/TranslatableField";

export default function HomeAbout({ page, section }) {

    const isNew = !section;

    const { data, setData, post, processing, errors } = useForm({
        _method:      isNew ? undefined : "PUT",
        type:         "about",
        title:        asTranslations(section?.title || ""),
        content:      asTranslations(section?.content || ""),
        button_text:  asTranslations(section?.button_text || ""),
        button_link:  section?.button_link || "",
        is_active:    section?.is_active   ?? true,
        order:        section?.order       ?? 0,
        image:        null,
    });

    const submit = (e) => {
        e.preventDefault();
        const url = isNew
            ? `/admin/pages/${page.id}/sections`
            : `/admin/sections/${section.id}`;
        post(url, { forceFormData: true });
    };

    return (
        <AdminLayout>
            <div className="max-w-2xl">

                {/* HEADER */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">Inicio - Sobre nosotros</h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Edita el bloque "Sobre nosotros" que aparece en la página de inicio.
                    </p>
                </div>

                {/* FORM */}
                <form onSubmit={submit} className="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">

                    {/* Imagen */}
                    <div>
                        <label className="block text-xs font-medium text-gray-500 mb-2">Imagen</label>
                        <ImageUploadField
                            currentUrl={section?.image_url}
                            onChange={(f) => setData("image", f)}
                            specs={{ width: 600, height: 600, maxMB: 3, formats: ["PNG", "JPG"] }}
                            error={errors.image}
                        />
                    </div>

                    <hr className="border-gray-100" />

                    {/* Título */}
                    <TranslatableField label="Título" value={data.title} onChange={(v) => setData("title", v)} placeholder="Ej: Sobre CONAR" error={errors.title} />

                    {/* Contenido */}
                    <TranslatableField label="Contenido" value={data.content} onChange={(v) => setData("content", v)} type="textarea" rows={5} placeholder="Escribe el texto que aparece junto a la imagen..." error={errors.content} />

                    <hr className="border-gray-100" />

                    {/* Botón */}
                    <div className="grid grid-cols-2 gap-4">
                        <TranslatableField label="Texto del botón" value={data.button_text} onChange={(v) => setData("button_text", v)} placeholder="Ej: Más información" />
                        <div>
                            <label className="block text-xs font-medium text-gray-500 mb-1">URL del botón</label>
                            <input
                                type="text"
                                value={data.button_link}
                                onChange={(e) => setData("button_link", e.target.value)}
                                placeholder="/nosotros"
                                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30"
                            />
                        </div>
                    </div>

                    <hr className="border-gray-100" />

                    {/* Footer del form */}
                    <div className="flex items-center justify-between">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData("is_active", e.target.checked)}
                                className="w-4 h-4 rounded accent-[#25A7CA]"
                            />
                            <span className="text-sm text-gray-700">Sección activa</span>
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2 bg-[#25A7CA] text-white rounded-lg text-sm font-medium hover:bg-[#1d96b8] disabled:opacity-60 transition"
                        >
                            <Icon icon={processing ? "solar:refresh-outline" : "solar:diskette-outline"} width={15} />
                            {processing ? "Guardando..." : "Guardar cambios"}
                        </button>
                    </div>

                </form>
            </div>
        </AdminLayout>
    );
}
