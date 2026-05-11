<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Http\Request;
use App\Support\Translation;

class ProductSpecController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'  => 'required',
            'value' => 'nullable',
            'order' => 'nullable|integer|min:0',
        ]);

        // Evita NULL en la BD
        $data['name'] = Translation::input($request->input('name'));
        $data['value'] = Translation::input($request->input('value'));

        $data['product_id'] = $product->id;
        $data['is_active']  = true;

        // Orden seguro
        if (!isset($data['order'])) {
            $maxOrder = $product->specifications()->max('order');
            $data['order'] = is_numeric($maxOrder) ? $maxOrder + 1 : 1;
        }

        ProductSpecification::create($data);

        return back()->with('success', 'Característica agregada.');
    }

    public function update(Request $request, Product $product, ProductSpecification $spec)
    {
        $data = $request->validate([
            'name'  => 'required',
            'value' => 'nullable',
        ]);

        // Evita NULL en BD
        $data['name'] = Translation::input($request->input('name'));
        $data['value'] = Translation::input($request->input('value'));

        $spec->update($data);

        return back()->with('success', 'Característica actualizada.');
    }

    public function destroy(Product $product, ProductSpecification $spec)
    {
        $spec->delete();

        return back()->with('success', 'Característica eliminada.');
    }
}
