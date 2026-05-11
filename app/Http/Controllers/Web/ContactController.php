<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ContactNotificationMail;
use App\Models\ContactMessage;
use App\Models\Setting;
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

        // Notificación al destinatario configurado para producción.
        try {
            Mail::to($this->notificationRecipients())
                ->send(new ContactNotificationMail($message));
        } catch (\Exception $e) {
            // No interrumpir el flujo si el mail falla
            logger()->error('Error enviando notificación de contacto: ' . $e->getMessage());
        }

        return back()->with('success', 'Mensaje enviado correctamente.');
    }

    private function notificationRecipients(): array
    {
        $contactPage = json_decode(Setting::where('key', 'contact_page')->value('value') ?? '{}', true);
        $footer = json_decode(Setting::where('key', 'footer')->value('value') ?? '{}', true);
        $contactPage = is_array($contactPage) ? $contactPage : [];
        $footer = is_array($footer) ? $footer : [];
        $configured = $contactPage['mail_recipients'] ?? ($footer['mail_recipients'] ?? '');
        $recipients = array_filter(array_map('trim', explode(',', $configured)));

        return $recipients ?: config('mail.to.addresses', [config('mail.to.address')]);
    }
}
