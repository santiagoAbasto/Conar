<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Industry;

class IndustryController extends Controller
{
    public function index()
    {
        $industries = Industry::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('web.industries.index', [
            'industries' => $industries
        ]);
    }

    public function show($slug)
    {
        $industry = Industry::where('slug', $slug)
            ->with('products')
            ->firstOrFail();

        return view('web.industries.show', [
            'industry' => $industry
        ]);
    }
}