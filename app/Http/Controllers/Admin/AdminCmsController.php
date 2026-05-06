<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
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

        return Inertia::render('Admin/CMS/Contacto', [
            'page'     => $page,
            'sections' => $page->sections->map(fn($s) => $this->mapSection($s)),
            'contactInfo' => [
                'address' => $footer['address'] ?? '',
                'email'   => $footer['email'] ?? '',
                'phone'   => $footer['phone'] ?? '',
            ],
        ]);
    }

    public function updateContactInfo(Request $request)
    {
        $data = $request->validate([
            'address' => 'nullable|string|max:500',
            'email'   => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:100',
        ]);

        $setting = Setting::where('key', 'footer')->first();
        $footer = $setting ? json_decode($setting->value, true) : [];

        $footer['address'] = $data['address'] ?? '';
        $footer['email']   = $data['email'] ?? '';
        $footer['phone']   = $data['phone'] ?? '';

        Setting::updateOrCreate(
            ['key' => 'footer'],
            ['value' => json_encode($footer)]
        );

        return back()->with('success', 'Datos de contacto actualizados.');
    }

    /* ---------------------------------------- SECTIONS CRUD ---------------------------------------- */

    public function storeSection(Request $request, Page $page)
    {
        $data = $request->validate([
            'type'        => 'required|string|max:80',
            'title'       => 'nullable|string|max:300',
            'subtitle'    => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:500',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cms', 'public');
        }

        $data['title']      = isset($data['title']) ? ['es' => $data['title']] : null;
        $data['subtitle']   = isset($data['subtitle']) ? ['es' => $data['subtitle']] : null;
        $data['content']    = isset($data['content']) ? ['es' => $data['content']] : null;
        $data['button_text']= isset($data['button_text']) ? ['es' => $data['button_text']] : null;
        $data['is_active']  = $request->boolean('is_active', true);

        $page->sections()->create($data);

        return back()->with('success', 'Sección creada correctamente.');
    }

    public function updateSection(Request $request, PageSection $section)
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:300',
            'subtitle'    => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
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

        $data['title']      = isset($data['title']) ? ['es' => $data['title']] : null;
        $data['subtitle']   = isset($data['subtitle']) ? ['es' => $data['subtitle']] : null;
        $data['content']    = isset($data['content']) ? ['es' => $data['content']] : null;
        $data['button_text']= isset($data['button_text']) ? ['es' => $data['button_text']] : null;
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
            'title'       => is_array($s->title) ? ($s->title['es'] ?? '') : $s->title,
            'subtitle'    => is_array($s->subtitle) ? ($s->subtitle['es'] ?? '') : $s->subtitle,
            'content'     => is_array($s->content) ? ($s->content['es'] ?? '') : $s->content,
            'button_text' => is_array($s->button_text) ? ($s->button_text['es'] ?? '') : $s->button_text,
            'button_link' => $s->button_link,
            'image_url'   => $s->image ? asset('storage/' . $s->image) : null,
            'is_active'   => $s->is_active,
            'order'       => $s->order,
        ];
    }
}
