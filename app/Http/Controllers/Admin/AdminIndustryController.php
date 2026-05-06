<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
                    'title'          => $i->title,
                    'name'           => $i->name,
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
            'title'       => 'nullable|string|max:120',
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('industries', 'public');
        }

        $data['slug']      = Str::slug($data['name']);
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
                'title'       => $sector->title,
                'name'        => $sector->name,
                'slug'        => $sector->slug,
                'description' => $sector->description,
                'image_url'   => $sector->image_url,
                'is_active'   => $sector->is_active,
                'order'       => $sector->order,
            ],
        ]);
    }

    public function update(Request $request, Industry $sector)
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:120',
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string',
            'order'       => 'nullable|string|max:10',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

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
