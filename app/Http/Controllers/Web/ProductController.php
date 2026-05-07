<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;

class ProductController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | CATALOGO GENERAL
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $first = ProductCategory::where('is_active',true)
            ->orderBy('order')
            ->first();

        if ($first) {
            return redirect()->route('products.category', $first->slug);
        }

        return view('web.products.index',['categories' => collect()]);

    }



    /*
    |--------------------------------------------------------------------------
    | CATEGORIA
    |--------------------------------------------------------------------------
    */

    public function category($slug)
    {

        $category = ProductCategory::where('slug',$slug)
            ->where('is_active',true)
            ->firstOrFail();

        $subcategories = ProductSubcategory::where('category_id',$category->id)
            ->where('is_active',true)
            ->orderBy('order')
            ->get();

        $categories = ProductCategory::where('is_active',true)
            ->orderBy('order')
            ->get();

        return view('web.products.category',[
            'category' => $category,
            'subcategories' => $subcategories,
            'categories' => $categories
        ]);

    }



    /*
    |--------------------------------------------------------------------------
    | SUBCATEGORIA
    |--------------------------------------------------------------------------
    */

    public function subcategory($category,$slug)
    {

        $subcategory = ProductSubcategory::where('slug',$slug)
            ->where('is_active',true)
            ->firstOrFail();

        $products = Product::where('subcategory_id',$subcategory->id)
            ->where('is_active',true)
            ->with(['category', 'images'])
            ->get();

        $categories = ProductCategory::where('is_active',true)
            ->orderBy('order')
            ->get();

        return view('web.products.subcategory',[
            'subcategory' => $subcategory,
            'products' => $products,
            'categories' => $categories
        ]);

    }



    /*
    |--------------------------------------------------------------------------
    | PRODUCTO
    |--------------------------------------------------------------------------
    */

    public function show($slug)
    {

        $product = Product::where('slug',$slug)
            ->with([
                'category',
                'subcategory',
                'images',
                'specifications',
                'downloads'
            ])
            ->firstOrFail();

        $related = Product::where('subcategory_id',$product->subcategory_id)
            ->where('id','!=',$product->id)
            ->with(['category', 'images'])
            ->limit(3)
            ->get();

        return view('web.products.show',[
            'product'=>$product,
            'related'=>$related
        ]);

    }

}
