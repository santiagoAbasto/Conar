<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = Product::where('is_active', true)
            ->with(['category', 'images'])
            ->orderBy('name');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('subtitle', 'LIKE', "%{$q}%")
                    ->orWhere('brand', 'LIKE', "%{$q}%")
                    ->orWhere('short_description', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%")
                    ->orWhereHas('category', function ($q2) use ($q) {
                        $q2->where('name', 'LIKE', "%{$q}%");
                    });
            });
        }

        $results = $query->get();

        return view('web.search.index', [
            'q'       => $q,
            'results' => $results,
        ]);
    }
}
