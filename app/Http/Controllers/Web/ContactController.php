<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ContactNotificationMail;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('web.contact.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:255',
            'phone'   => 'required|string|max:50',
            'company' => 'nullable|string|max:120',
            'message' => 'required|string|max:2000',
        ]);

        $message = ContactMessage::create($validated);

        // Notificación al admin via Mailtrap
        try {
            Mail::to(config('mail.from.address'))
                ->send(new ContactNotificationMail($message));
        } catch (\Exception $e) {
            // No interrumpir el flujo si el mail falla
            logger()->error('Error enviando notificación de contacto: ' . $e->getMessage());
        }

        return back()->with('success', 'Mensaje enviado correctamente.');
    }
}
