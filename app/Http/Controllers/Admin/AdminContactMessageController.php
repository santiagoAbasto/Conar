<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Inertia\Inertia;

class AdminContactMessageController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => ContactMessage::latest()->paginate(20)->through(fn($m) => [
                'id'         => $m->id,
                'name'       => $m->name,
                'email'      => $m->email,
                'company'    => $m->company,
                'is_read'    => $m->is_read,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function show(ContactMessage $contactMessage)
    {
        if (!$contactMessage->is_read) {
            $contactMessage->update(['is_read' => true]);
        }

        return Inertia::render('Admin/ContactMessages/Show', [
            'message' => $contactMessage->toArray(),
        ]);
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();
        return redirect('/admin/contact-messages')
            ->with('success', 'Mensaje eliminado.');
    }
}
