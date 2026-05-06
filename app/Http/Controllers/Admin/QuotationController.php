<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Quotations/Index', [
            'quotations' => QuoteRequest::latest()->paginate(20)->through(fn($q) => [
                'id'      => $q->id,
                'name'    => $q->name,
                'email'   => $q->email,
                'company' => $q->company,
                'is_read' => $q->is_read,
                'created_at' => $q->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function show(QuoteRequest $quotation)
    {
        if (!$quotation->is_read) {
            $quotation->update(['is_read' => true]);
        }

        return Inertia::render('Admin/Quotations/Show', [
            'quotation' => $quotation->toArray(),
        ]);
    }

    public function destroy(QuoteRequest $quotation)
    {
        $quotation->delete();
        return redirect('/admin/comercial/quotations')
            ->with('success', 'Cotización eliminada.');
    }
}
