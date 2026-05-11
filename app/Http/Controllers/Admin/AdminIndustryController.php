<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Support\Translation;

class AdminIndustryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Sectors/Index', [
            'industries' => Industry::withCount('products')
                ->orderBy('order')
                ->get()
                ->map(fn($i) => [
                    'id'             => $i->id,
                    'title'          => Translation::payload($i->getRawOriginal('title')),
                    'name'           => Translation::payload($i->getRawOriginal('name')),
                    'slug'           => $i->slug,
                    'image_url'      => $i->image_url,
                    'is_active'      => $i->is_active,
                    'order'          => $i->order,
                    'products_count' => $i->products_count,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Sectors/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'nullable',
            'name'        => 'required',
            'description' => 'nullable',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('industries', 'public');
        }

        foreach (['title', 'name', 'description'] as $field) {
            $data[$field] = Translation::input($request->input($field));
        }
        $data['slug']      = Str::slug($data['name']['es']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['order']     = strtoupper(trim($data['order'] ?? '')) ?: 'A';

        Industry::create($data);

        return redirect('/admin/sectors')
            ->with('success', 'Sector creado correctamente.');
    }

    public function edit(Industry $sector)
    {
        return Inertia::render('Admin/Sectors/Edit', [
            'industry' => [
                'id'          => $sector->id,
                'title'       => Translation::payload($sector->getRawOriginal('title')),
                'name'        => Translation::payload($sector->getRawOriginal('name')),
                'slug'        => $sector->slug,
                'description' => Translation::payload($sector->getRawOriginal('description')),
                'image_url'   => $sector->image_url,
                'is_active'   => $sector->is_active,
                'order'       => $sector->order,
            ],
        ]);
    }

    public function update(Request $request, Industry $sector)
    {
        $data = $request->validate([
            'title'       => 'nullable',
            'name'        => 'required',
            'description' => 'nullable',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        foreach (['title', 'name', 'description'] as $field) {
            $data[$field] = Translation::input($request->input($field));
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('industries', 'public');
        } else {
            unset($data['image']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['order']     = strtoupper(trim($data['order'] ?? '')) ?: 'A';
        $sector->update($data);

        return redirect('/admin/sectors')
            ->with('success', 'Sector actualizado correctamente.');
    }

    public function destroy(Industry $sector)
    {
        $sector->delete();
        return redirect('/admin/sectors')
            ->with('success', 'Sector eliminado.');
    }
}
