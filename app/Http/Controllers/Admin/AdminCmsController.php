<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Support\Translation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminCmsController extends Controller
{
    private function getPage(string $slug): Page
    {
        return Page::firstOrCreate(['slug' => $slug], [
            'title'     => ['es' => ucfirst($slug)],
            'is_active' => true,
        ]);
    }

    /* ---------------------------------------- HOME ABOUT ---------------------------------------- */

    public function homeAbout()
    {
        $page = $this->getPage('home');

        // Busca la sección about existente o devuelve null para que el frontend muestre el form vacío
        $section = $page->sections
            ->where('type', 'about')
            ->first();

        return Inertia::render('Admin/CMS/HomeAbout', [
            'page'    => ['id' => $page->id],
            'section' => $section ? $this->mapSection($section) : null,
        ]);
    }

    /* ---------------------------------------- ABOUT ---------------------------------------- */

    public function about()
    {
        $page = $this->getPage('nosotros');
        return Inertia::render('Admin/CMS/About', [
            'page'     => $page,
            'sections' => $page->sections->map(fn($s) => $this->mapSection($s)),
        ]);
    }

    /* ---------------------------------------- CALIDAD ---------------------------------------- */

    public function calidad()
    {
        $page = $this->getPage('calidad');
        return Inertia::render('Admin/CMS/Calidad', [
            'page'     => $page,
            'sections' => $page->sections->map(fn($s) => $this->mapSection($s)),
        ]);
    }

    /* ---------------------------------------- CONTACTO ---------------------------------------- */

    public function contacto()
    {
        $page = $this->getPage('contacto');

        $footer = json_decode(Setting::where('key', 'footer')->value('value') ?? '{}', true);
        $contactPage = json_decode(Setting::where('key', 'contact_page')->value('value') ?? '{}', true);
        $footer = is_array($footer) ? $footer : [];
        $contactPage = is_array($contactPage) ? $contactPage : [];

        return Inertia::render('Admin/CMS/Contacto', [
            'page'     => $page,
            'sections' => $page->sections->map(fn($s) => $this->mapSection($s)),
            'contactInfo' => [
                'address' => Translation::payload($contactPage['address'] ?? ($footer['address'] ?? '')),
                'email'   => $contactPage['email'] ?? ($footer['email'] ?? ''),
                'phone'   => $contactPage['phone'] ?? ($footer['phone'] ?? ''),
                'fixed_phone' => $contactPage['fixed_phone'] ?? ($footer['fixed_phone'] ?? ''),
                'mail_recipients' => $contactPage['mail_recipients'] ?? ($footer['mail_recipients'] ?? 'admin@conar.com,soporte@conar.com.ar,dda@conar.com.ar'),
                'contact_items' => $this->contactItemsForForm($contactPage['contact_items'] ?? ($footer['contact_items'] ?? [])),
            ],
        ]);
    }

    public function updateContactInfo(Request $request)
    {
        $data = $request->validate([
            'address' => 'nullable',
            'email'   => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:100',
            'fixed_phone' => 'nullable|string|max:100',
            'mail_recipients' => 'nullable|string|max:500',
            'contact_items' => 'nullable|array',
            'contact_items.*.type' => 'required_with:contact_items|string|in:email,phone,whatsapp',
            'contact_items.*.label' => 'nullable',
            'contact_items.*.value' => 'nullable|string|max:200',
            'contact_items.*.is_active' => 'boolean',
        ]);

        $contactPage = [];

        $contactPage['address'] = Translation::input($request->input('address'));
        $contactPage['email']   = $data['email'] ?? '';
        $contactPage['phone']   = $data['phone'] ?? '';
        $contactPage['fixed_phone'] = $data['fixed_phone'] ?? '';
        $contactPage['mail_recipients'] = $data['mail_recipients'] ?? 'admin@conar.com,soporte@conar.com.ar,dda@conar.com.ar';
        $contactPage['contact_items'] = $this->sanitizeContactItems($request->input('contact_items', []), $contactPage);

        Setting::updateOrCreate(
            ['key' => 'contact_page'],
            ['value' => json_encode($contactPage, JSON_UNESCAPED_UNICODE)]
        );

        return back()->with('success', 'Datos de la página de contacto actualizados.');
    }

    public function footer()
    {
        $footer = json_decode(Setting::where('key', 'footer')->value('value') ?? '{}', true);
        $footer = is_array($footer) ? $footer : [];

        return Inertia::render('Admin/Extras/Footer', [
            'contactInfo' => [
                'address' => Translation::payload($footer['address'] ?? ''),
                'email'   => $footer['email'] ?? '',
                'phone'   => $footer['phone'] ?? '',
                'fixed_phone' => $footer['fixed_phone'] ?? '',
                'mail_recipients' => $footer['mail_recipients'] ?? 'admin@conar.com,soporte@conar.com.ar,dda@conar.com.ar',
                'contact_items' => $this->contactItemsForForm($footer['contact_items'] ?? []),
            ],
        ]);
    }

    public function updateFooter(Request $request)
    {
        $data = $request->validate([
            'address' => 'nullable',
            'email'   => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:100',
            'fixed_phone' => 'nullable|string|max:100',
            'mail_recipients' => 'nullable|string|max:500',
            'contact_items' => 'nullable|array',
            'contact_items.*.type' => 'required_with:contact_items|string|in:email,phone,whatsapp',
            'contact_items.*.label' => 'nullable',
            'contact_items.*.value' => 'nullable|string|max:200',
            'contact_items.*.is_active' => 'boolean',
        ]);

        $setting = Setting::where('key', 'footer')->first();
        $footer = $setting ? json_decode($setting->value, true) : [];
        $footer = is_array($footer) ? $footer : [];

        $footer['address'] = Translation::input($request->input('address'));
        $footer['email']   = $data['email'] ?? '';
        $footer['phone']   = $data['phone'] ?? '';
        $footer['fixed_phone'] = $data['fixed_phone'] ?? '';
        $footer['mail_recipients'] = $data['mail_recipients'] ?? 'admin@conar.com,soporte@conar.com.ar,dda@conar.com.ar';
        $footer['contact_items'] = $this->sanitizeContactItems($request->input('contact_items', []), $footer);

        Setting::updateOrCreate(
            ['key' => 'footer'],
            ['value' => json_encode($footer, JSON_UNESCAPED_UNICODE)]
        );

        return back()->with('success', 'Datos del footer actualizados.');
    }

    private function contactItemsForForm(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($item) => [
                'type' => $item['type'] ?? 'email',
                'label' => Translation::text($item['label'] ?? '') ?? '',
                'value' => trim((string) ($item['value'] ?? '')),
                'is_active' => filter_var($item['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values()
            ->all();
    }

    private function sanitizeContactItems(array $items, array $fallback = []): array
    {
        $sanitized = collect($items)
            ->map(fn ($item) => [
                'type' => $item['type'] ?? 'email',
                'label' => trim((string) (Translation::text($item['label'] ?? '') ?? '')),
                'value' => trim((string) ($item['value'] ?? '')),
                'is_active' => filter_var($item['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->filter(fn ($item) => $item['value'] !== '')
            ->values()
            ->all();

        if ($sanitized) {
            return $sanitized;
        }

        return array_values(array_filter([
            !empty($fallback['email']) ? [
                'type' => 'email',
                'label' => 'Email',
                'value' => trim((string) $fallback['email']),
                'is_active' => true,
            ] : null,
            !empty($fallback['phone']) ? [
                'type' => 'whatsapp',
                'label' => 'WhatsApp',
                'value' => trim((string) $fallback['phone']),
                'is_active' => true,
            ] : null,
            !empty($fallback['fixed_phone']) ? [
                'type' => 'phone',
                'label' => 'Teléfono fijo',
                'value' => trim((string) $fallback['fixed_phone']),
                'is_active' => true,
            ] : null,
        ]));
    }

    /* ---------------------------------------- SECTIONS CRUD ---------------------------------------- */

    public function storeSection(Request $request, Page $page)
    {
        $data = $request->validate([
            'type'        => 'required|string|max:80',
            'title'       => 'nullable',
            'subtitle'    => 'nullable',
            'content'     => 'nullable',
            'button_text' => 'nullable',
            'button_link' => 'nullable|string|max:500',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms', 'public');
        }

        foreach (['title', 'subtitle', 'content', 'button_text'] as $field) {
            $data[$field] = Translation::input($request->input($field));
        }
        $data['is_active']  = $request->boolean('is_active', true);

        $page->sections()->create($data);

        return back()->with('success', 'Sección creada correctamente.');
    }

    public function updateSection(Request $request, PageSection $section)
    {
        $data = $request->validate([
            'title'       => 'nullable',
            'subtitle'    => 'nullable',
            'content'     => 'nullable',
            'button_text' => 'nullable',
            'button_link' => 'nullable|string|max:500',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms', 'public');
        } else {
            unset($data['image']);
        }

        foreach (['title', 'subtitle', 'content', 'button_text'] as $field) {
            $data[$field] = Translation::input($request->input($field));
        }
        $data['is_active']  = $request->boolean('is_active', true);

        $section->update($data);

        return back()->with('success', 'Sección actualizada correctamente.');
    }

    public function destroySection(PageSection $section)
    {
        $page = $section->page;
        $section->delete();

        return back()->with('success', 'Sección eliminada.');
    }

    private function mapSection(PageSection $s): array
    {
        return [
            'id'          => $s->id,
            'type'        => $s->type,
            'title'       => Translation::payload($s->getRawOriginal('title')),
            'subtitle'    => Translation::payload($s->getRawOriginal('subtitle')),
            'content'     => Translation::payload($s->getRawOriginal('content')),
            'button_text' => Translation::payload($s->getRawOriginal('button_text')),
            'button_link' => $s->button_link,
            'image_url'   => $s->image ? asset('storage/' . $s->image) : null,
            'is_active'   => $s->is_active,
            'order'       => $s->order,
        ];
    }
}
