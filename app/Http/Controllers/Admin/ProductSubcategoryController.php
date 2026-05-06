<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductSubcategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Subcategories/Index', [
            'subcategories' => ProductSubcategory::with('category')
                ->withCount('products')
                ->orderBy('order')
                ->orderBy('name')
                ->get()
                ->map(fn($s) => [
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'slug'           => $s->slug,
                    'is_active'      => $s->is_active,
                    'show_in_home'   => $s->show_in_home,
                    'order'          => $s->order,
                    'products_count' => $s->products_count,
                    'category'       => $s->category ? ['id' => $s->category->id, 'name' => $s->category->name] : null,
                ]),
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Subcategories/Create', [
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'  => 'required|exists:product_categories,id',
            'name'         => 'required|string|max:120',
            'order'        => 'nullable|string|max:10|regex:/^[A-Z]*$/',
            'is_active'    => 'boolean',
            'show_in_home' => 'boolean',
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        }

        $data['is_active']    = $request->boolean('is_active', true);
        $data['show_in_home'] = $request->boolean('show_in_home', false);

        ProductSubcategory::create($data);

        return redirect('/admin/subcategories')
            ->with('success', 'Subcategoría creada correctamente.');
    }

    public function edit(ProductSubcategory $subcategory)
    {
        return Inertia::render('Admin/Subcategories/Edit', [
            'subcategory' => [
                'id'           => $subcategory->id,
                'name'         => $subcategory->name,
                'slug'         => $subcategory->slug,
                'category_id'  => $subcategory->category_id,
                'is_active'    => $subcategory->is_active,
                'show_in_home' => $subcategory->show_in_home,
                'order'        => $subcategory->order,
            ],
            'categories' => ProductCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, ProductSubcategory $subcategory)
    {
        $data = $request->validate([
            'category_id'  => 'required|exists:product_categories,id',
            'name'         => 'required|string|max:120',
            'order'        => 'nullable|string|max:10|regex:/^[A-Z]*$/',
            'is_active'    => 'boolean',
            'show_in_home' => 'boolean',
            'image'        => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('subcategories', 'public');
        } else {
            unset($data['image']);
        }

        $data['is_active']    = $request->boolean('is_active', true);
        $data['show_in_home'] = $request->boolean('show_in_home', false);
        $subcategory->update($data);

        return redirect('/admin/subcategories')
            ->with('success', 'Subcategoría actualizada correctamente.');
    }

    public function destroy(ProductSubcategory $subcategory)
    {
        $subcategory->delete();
        return redirect('/admin/subcategories')
            ->with('success', 'Subcategoría eliminada.');
    }
}
