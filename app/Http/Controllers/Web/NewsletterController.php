<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterSubscriptionNotificationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'Ingresá un email válido.',
        ]);

        $email = strtolower(trim($data['email']));

        if (NewsletterSubscriber::where('email', $email)->exists()) {
            $message = 'Ya estás suscrito al newsletter.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'status' => 'already_subscribed']);
            }

            return back()->with('success', $message);
        }

        $subscriber = NewsletterSubscriber::create([
            'email' => $email
        ]);

        try {
            Mail::to(config('mail.to.address'), config('mail.to.name'))
                ->send(new NewsletterSubscriptionNotificationMail($subscriber));
        } catch (\Exception $e) {
            logger()->error('Error enviando notificación de newsletter: ' . $e->getMessage());
        }

        $message = '¡Te suscribiste correctamente!';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'status' => 'subscribed']);
        }

        return back()->with('success', $message);
    }
}
