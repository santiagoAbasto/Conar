<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Page;

class AboutController extends Controller
{
    public function index()
    {
        $page = Page::where('slug','nosotros')
            ->with(['sections' => function ($q) {
                $q->where('is_active', true)
                  ->orderBy('order');
            }])
            ->firstOrFail();

        return view('web.about.index', compact('page'));
    }
}