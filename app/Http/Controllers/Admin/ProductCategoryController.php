<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Support\Translation;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => ProductCategory::withCount('products')
                ->orderBy('order')
                ->get()
                ->map(fn($c) => [
                    'id'            => $c->id,
                    'name'          => Translation::payload($c->getRawOriginal('name')),
                    'slug'          => $c->slug,
                    'description'   => Translation::payload($c->getRawOriginal('description')),
                    'image_url'     => $c->image_url,
                    'color'         => $c->color,
                    'is_active'     => $c->is_active,
                    'order'         => $c->order,
                    'products_count'=> $c->products_count,
                ]),
            'colorPalette' => ProductCategory::COLOR_PALETTE,
            'suggestedColor' => ProductCategory::suggestedColor(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Categories/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required',
            'description' => 'nullable',
            'order'       => 'nullable|string|max:10',
            'color'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['name']      = Translation::input($request->input('name'));
        $data['description'] = Translation::input($request->input('description'));
        $data['slug']      = Str::slug($data['name']['es']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['order']     = strtoupper(trim($data['order'] ?? '')) ?: 'A';
        $data['color']     = strtoupper($data['color'] ?: ProductCategory::suggestedColor());

        ProductCategory::create($data);

        return redirect('/admin/categories')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(ProductCategory $category)
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => [
                'id'          => $category->id,
                'name'        => Translation::payload($category->getRawOriginal('name')),
                'slug'        => $category->slug,
                'description' => Translation::payload($category->getRawOriginal('description')),
                'image_url'   => $category->image_url,
                'color'       => $category->color,
                'is_active'   => $category->is_active,
                'order'       => $category->order,
            ],
        ]);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'name'        => 'required',
            'description' => 'nullable',
            'order'       => 'nullable|string|max:10',
            'color'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        } else {
            unset($data['image']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['name']      = Translation::input($request->input('name'));
        $data['description'] = Translation::input($request->input('description'));
        $data['order']     = strtoupper(trim($data['order'] ?? '')) ?: 'A';
        $data['color']     = strtoupper($data['color'] ?: $category->color);
        $category->update($data);

        return redirect('/admin/categories')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(ProductCategory $category)
    {
        $category->delete();
        return redirect('/admin/categories')
            ->with('success', 'Categoría eliminada.');
    }
}
