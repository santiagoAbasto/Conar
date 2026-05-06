<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Industry;
use App\Models\Post;
use App\Models\QuoteRequest;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'products'     => Product::count(),
                'categories'   => ProductCategory::count(),
                'industries'   => Industry::count(),
                'posts'        => Post::count(),
                'quotes'       => QuoteRequest::count(),
                'unread_quotes'=> QuoteRequest::where('is_read', 0)->count(),
                'messages'     => ContactMessage::count(),
                'unread_messages' => ContactMessage::where('is_read', 0)->count(),
                'subscribers'  => NewsletterSubscriber::count(),
            ],
            'recent_quotes'   => QuoteRequest::latest()->take(5)->get(),
            'recent_messages' => ContactMessage::latest()->take(5)->get(),
        ]);
    }
}
