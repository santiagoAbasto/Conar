<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminProductController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Product::with(['category','subcategory'])
            ->orderBy('order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name','like','%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id',$request->category);
        }

        return Inertia::render('Admin/Products/Index',[
            'products' => $query->paginate(20)->through(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'slug'         => $p->slug,
                'brand'        => $p->brand,
                'image_url'    => $p->image_url,
                'is_active'    => $p->is_active,
                'is_featured'  => $p->is_featured,
                'show_in_home' => $p->show_in_home,
                'order'        => $p->order,
                'category'     => $p->category ? [
                    'id'=>$p->category->id,
                    'name'=>$p->category->name,
                    'color'=>$p->category->color,
                ] : null,
                'subcategory'  => $p->subcategory ? [
                    'id'=>$p->subcategory->id,
                    'name'=>$p->subcategory->name
                ] : null,
            ]),

            'categories' => ProductCategory::orderBy('name')
                ->get(['id','name']),

            'filters' => $request->only(['search','category']),
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return Inertia::render('Admin/Products/Create',[
            'categories' => ProductCategory::orderBy('name')
                ->get(['id','name']),

            'subcategories' => ProductSubcategory::orderBy('name')
                ->get(['id','name','category_id']),
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $data = $request->validate([

            'category_id'       => 'required|exists:product_categories,id',
            'subcategory_id'    => 'nullable|exists:product_subcategories,id',

            'name'              => 'required|string|max:200',
            'subtitle'          => 'nullable|string|max:300',
            'brand'             => 'nullable|string|max:120',

            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',

            'order'             => 'nullable|string|max:10|regex:/^[A-Z]*$/',

            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'show_in_home'      => 'boolean',

            'image'             => 'nullable|image|max:4096',
            'video'             => 'nullable|mimes:mp4,webm|max:20000',
            'video_url'         => 'nullable|url|max:500',
            'brand_logo'        => 'nullable|image|max:2048',

        ]);


        /*
        |--------------------------------------------------
        | IMAGE
        |--------------------------------------------------
        */

        if ($request->hasFile('image')) {

            $data['image'] = $request->file('image')
                ->store('products','public');

        }


        /*
        |--------------------------------------------------
        | VIDEO LOCAL
        |--------------------------------------------------
        */

        if ($request->hasFile('video')) {

            $data['video'] = $request->file('video')
                ->store('products/videos','public');

        }


        /*
        |--------------------------------------------------
        | BRAND LOGO
        |--------------------------------------------------
        */

        if ($request->hasFile('brand_logo')) {

            $data['brand_logo'] = $request->file('brand_logo')
                ->store('brand-logos','public');

        }


        /*
        |--------------------------------------------------
        | BOOLEAN NORMALIZATION
        |--------------------------------------------------
        */

        $data['is_active']    = $request->boolean('is_active',true);
        $data['is_featured']  = $request->boolean('is_featured',false);
        $data['show_in_home'] = $request->boolean('show_in_home',false);


        $product = Product::create($data);


        return redirect("/admin/products/{$product->id}/edit")
            ->with('success','Producto creado correctamente.');
    }



    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product)
    {
        $product->load([
            'specifications' => fn($q) => $q->orderBy('order'),
            'downloads'      => fn($q) => $q->orderBy('order')
        ]);

        return Inertia::render('Admin/Products/Edit',[

            'product' => [

                'id'                => $product->id,
                'name'              => $product->name,
                'slug'              => $product->slug,
                'subtitle'          => $product->subtitle,
                'brand'             => $product->brand,

                'brand_logo_url'    => $product->brand_logo
                    ? Storage::url($product->brand_logo)
                    : null,

                'short_description' => $product->short_description,
                'description'       => $product->description,

                'image_url'         => $product->image_url,
                'video'             => $product->video,
                'video_url'         => $product->video_url,

                'is_active'         => $product->is_active,
                'is_featured'       => $product->is_featured,
                'show_in_home'      => $product->show_in_home,
                'order'             => $product->order,

                'category_id'       => $product->category_id,
                'subcategory_id'    => $product->subcategory_id,

                'specifications' => $product->specifications->map(fn($s) => [
                    'id'=>$s->id,
                    'name'=>$s->name,
                    'value'=>$s->value,
                    'order'=>$s->order,
                ]),

                'downloads' => $product->downloads->map(fn($d) => [
                    'id'=>$d->id,
                    'name'=>$d->name,
                    'file_url'=>$d->file_url,
                    'type'=>$d->type,
                    'size'=>$d->size,
                    'is_active'=>$d->is_active,
                    'is_principal'=>$d->is_principal,
                ]),
            ],

            'categories' => ProductCategory::orderBy('name')
                ->get(['id','name']),

            'subcategories' => ProductSubcategory::orderBy('name')
                ->get(['id','name','category_id']),
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Product $product)
    {

        $data = $request->validate([

            'category_id'       => 'required|exists:product_categories,id',
            'subcategory_id'    => 'nullable|exists:product_subcategories,id',

            'name'              => 'required|string|max:200',
            'subtitle'          => 'nullable|string|max:300',
            'brand'             => 'nullable|string|max:120',

            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',

            'order'             => 'nullable|string|max:10|regex:/^[A-Z]*$/',

            'is_active'         => 'boolean',
            'is_featured'       => 'boolean',
            'show_in_home'      => 'boolean',

            'image'             => 'nullable|image|max:4096',
            'video'             => 'nullable|mimes:mp4,webm|max:20000',
            'video_url'         => 'nullable|url|max:500',
            'brand_logo'        => 'nullable|image|max:2048',

        ]);


        /*
        |--------------------------------------------------
        | IMAGE
        |--------------------------------------------------
        */

        if ($request->hasFile('image')) {

            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')
                ->store('products','public');

        } else {

            unset($data['image']);
        }



        /*
        |--------------------------------------------------
        | VIDEO
        |--------------------------------------------------
        */

        if ($request->hasFile('video')) {

            if ($product->video && Storage::disk('public')->exists($product->video)) {
                Storage::disk('public')->delete($product->video);
            }

            $data['video'] = $request->file('video')
                ->store('products/videos','public');

        } else {

            unset($data['video']);
        }



        /*
        |--------------------------------------------------
        | BRAND LOGO
        |--------------------------------------------------
        */

        if ($request->hasFile('brand_logo')) {

            if ($product->brand_logo && Storage::disk('public')->exists($product->brand_logo)) {
                Storage::disk('public')->delete($product->brand_logo);
            }

            $data['brand_logo'] = $request->file('brand_logo')
                ->store('brand-logos','public');

        } else {

            unset($data['brand_logo']);
        }



        /*
        |--------------------------------------------------
        | BOOLEAN NORMALIZATION
        |--------------------------------------------------
        */

        $data['is_active']    = $request->boolean('is_active',true);
        $data['is_featured']  = $request->boolean('is_featured',false);
        $data['show_in_home'] = $request->boolean('show_in_home',false);


        $product->update($data);


        return back()
            ->with('success','Producto actualizado correctamente.');
    }



    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        if ($product->video) {
            Storage::disk('public')->delete($product->video);
        }

        if ($product->brand_logo) {
            Storage::disk('public')->delete($product->brand_logo);
        }

        $product->delete();

        return redirect('/admin/products')
            ->with('success','Producto eliminado.');
    }

}
