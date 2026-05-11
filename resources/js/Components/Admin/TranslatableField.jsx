import RichTextEditor from "@/Components/Admin/RichTextEditor";

export const LANGUAGES = [
    { key: "es", label: "Español" },
    { key: "pt", label: "Portugués" },
    { key: "en", label: "Inglés" },
];

export function asTranslations(value = "") {
    if (value && typeof value === "object" && !Array.isArray(value)) {
        return {
            es: value.es || "",
            pt: value.pt || "",
            en: value.en || "",
        };
    }

    return {
        es: value || "",
        pt: "",
        en: "",
    };
}

export function t(value, fallback = "") {
    if (value && typeof value === "object" && !Array.isArray(value)) {
        return value.es || value.pt || value.en || fallback;
    }

    return value || fallback;
}

export default function TranslatableField({
    label,
    value,
    onChange,
    type = "text",
    rows = 3,
    placeholder = "",
    required = false,
    error,
}) {
    const current = asTranslations(value);

    const setLocale = (locale, nextValue) => {
        onChange({
            ...current,
            [locale]: nextValue,
        });
    };

    return (
        <div>
            <label className="block text-sm text-gray-600 mb-2">
                {label}{required ? " *" : ""}
            </label>

            <div className="space-y-3">
                {LANGUAGES.map((lang) => (
                    <div key={lang.key} className="grid grid-cols-[92px_1fr] gap-2 items-start">
                        <span className="inline-flex h-9 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-500">
                            {lang.label}
                        </span>

                        {type === "rich" ? (
                            <RichTextEditor
                                value={current[lang.key] || ""}
                                onChange={(next) => setLocale(lang.key, next)}
                                placeholder={placeholder}
                            />
                        ) : type === "textarea" ? (
                            <textarea
                                value={current[lang.key] || ""}
                                onChange={(e) => setLocale(lang.key, e.target.value)}
                                rows={rows}
                                placeholder={placeholder}
                                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30 resize-none"
                            />
                        ) : (
                            <input
                                type="text"
                                value={current[lang.key] || ""}
                                onChange={(e) => setLocale(lang.key, e.target.value)}
                                placeholder={placeholder}
                                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#25A7CA]/30"
                            />
                        )}
                    </div>
                ))}
            </div>

            {error && <p className="text-red-500 text-xs mt-1">{error}</p>}
        </div>
    );
}
