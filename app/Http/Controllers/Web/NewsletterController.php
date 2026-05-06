<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers'
        ]);

        NewsletterSubscriber::create([
            'email' => $request->email
        ]);

        return back()->with('success','Suscripción realizada.');
    }
}