<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AdminNewsletterController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Newsletter/Index', [
            'subscribers' => NewsletterSubscriber::latest()->paginate(50)->through(fn($s) => [
                'id'         => $s->id,
                'email'      => $s->email,
                'is_active'  => $s->is_active,
                'created_at' => $s->created_at->format('d/m/Y H:i'),
            ]),
            'total'        => NewsletterSubscriber::count(),
            'total_active' => NewsletterSubscriber::where('is_active', true)->count(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:200',
            'body'    => 'required|string',
        ]);

        $subscribers = NewsletterSubscriber::where('is_active', true)->get();

        if ($subscribers->isEmpty()) {
            return back()->with('error', 'No hay suscriptores activos para enviar.');
        }

        $sent = 0;
        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)
                    ->send(new NewsletterCampaignMail($data['subject'], $data['body']));
                $sent++;
            } catch (\Exception $e) {
                logger()->error("Error enviando newsletter a {$subscriber->email}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Email enviado a {$sent} suscriptor(es) correctamente.");
    }

    public function destroy(NewsletterSubscriber $newsletter)
    {
        $newsletter->delete();
        return redirect('/admin/newsletter')
            ->with('success', 'Suscriptor eliminado.');
    }
}
