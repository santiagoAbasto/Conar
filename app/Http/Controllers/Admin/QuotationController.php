<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'quotation' => array_merge($quotation->toArray(), [
                'process_data' => $this->normalizeProcessData($quotation),
                'attachments' => $this->mapAttachments($quotation),
                'reply_mailto' => $this->buildReplyMailto($quotation),
            ]),
        ]);
    }

    public function destroy(QuoteRequest $quotation)
    {
        $quotation->delete();
        return redirect('/admin/comercial/quotations')
            ->with('success', 'Cotización eliminada.');
    }

    protected function mapAttachments(QuoteRequest $quotation): array
    {
        $paths = json_decode((string) $quotation->attachment, true);

        if (!is_array($paths)) {
            $paths = array_filter([$quotation->attachment]);
        }

        return collect($paths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(function (string $path) {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                return [
                    'name' => basename($path),
                    'path' => $path,
                    'url' => Storage::url($path),
                    'extension' => $extension,
                    'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true),
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeProcessData(QuoteRequest $quotation): array
    {
        $data = $quotation->process_data;

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        return is_array($data) ? $data : [];
    }

    protected function buildReplyMailto(QuoteRequest $quotation): string
    {
        $subject = 'Respuesta a solicitud de presupuesto CONAR';
        $body = trim(implode("\n", [
            "Hola {$quotation->name},",
            '',
            'Gracias por contactarte con CONAR. Te escribimos por tu solicitud de presupuesto.',
            '',
            "Producto/Solución: {$quotation->solution}",
            '',
            'Saludos,',
            'CONAR',
        ]));

        return 'mailto:' . rawurlencode($quotation->email)
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }
}
