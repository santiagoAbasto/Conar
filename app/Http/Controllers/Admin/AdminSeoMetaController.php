<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminSeoMetaController extends Controller
{
    const PAGES = [
        'home'           => 'Inicio (Home)',
        'nosotros'       => 'Nosotros',
        'products'       => 'Productos (listado)',
        'categories'     => 'Categorías',
        'subcategories'  => 'Subcategorías',
        'industries'     => 'Industrias / Sectores',
        'novedades'      => 'Novedades',
        'contacto'       => 'Contacto',
        'presupuesto'    => 'Presupuesto',
        'buscador'       => 'Buscador',
    ];

    public function index()
    {
        $metas = SeoMeta::all()->keyBy('page');

        $pages = collect(self::PAGES)->map(fn($label, $key) => [
            'key'         => $key,
            'label'       => $label,
            'title'       => $metas[$key]->title ?? '',
            'description' => $metas[$key]->description ?? '',
            'keywords'    => $metas[$key]->keywords ?? '',
            'og_image_url'=> $metas[$key]->og_image_url ?? null,
            'id'          => $metas[$key]->id ?? null,
        ])->values();

        return Inertia::render('Admin/Seo/Index', [
            'pages' => $pages,
        ]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'page'        => 'required|string|in:' . implode(',', array_keys(self::PAGES)),
            'title'       => 'nullable|string|max:160',
            'description' => 'nullable|string|max:320',
            'keywords'    => 'nullable|string|max:500',
            'og_image'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('seo', 'public');
        } else {
            unset($data['og_image']);
        }

        SeoMeta::updateOrCreate(
            ['page' => $data['page']],
            $data
        );

        return back()->with('success', 'Metadatos guardados correctamente.');
    }
}
