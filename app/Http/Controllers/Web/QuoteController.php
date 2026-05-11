<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\QuoteRequestNotificationMail;
use App\Models\QuoteRequest;
use App\Models\Product;
use App\Models\ProductSubcategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | FORMULARIO PRESUPUESTO
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $instrumentals = ProductSubcategory::where('is_active', 1)
            ->orderBy('name')
            ->get();

        // Carga subcategoría con sus campos técnicos para el configurador JS
        $solutions = Product::where('is_active', true)
            ->with('subcategory.technicalFields')
            ->orderBy('name')
            ->get();

        // Pre-procesar para JS: evita closures complejos en @json() de Blade
        $solutionsJson = $solutions->map(function ($p) {
            $fields = [];
            if ($p->subcategory) {
                foreach ($p->subcategory->technicalFields as $f) {
                    $fields[] = [
                        'label' => $f->label,
                        'name'  => $f->name,
                        'type'  => $f->type,
                    ];
                }
            }
            return [
                'id'               => $p->id,
                'name'             => $p->name,
                'subcategory_id'   => $p->subcategory_id,
                'technical_fields' => $fields,
            ];
        })->values()->all();

        // Preselección desde botón "Consultar" del producto
        $preselect = null;
        if ($request->filled('product_id')) {
            $pre = Product::find((int) $request->product_id);
            if ($pre && $pre->is_active) {
                $preselect = [
                    'subcategory_id' => $pre->subcategory_id,
                    'product_name'   => $pre->name,
                ];
            }
        }

        return view('web.presupuesto.index', compact(
            'instrumentals',
            'solutions',
            'solutionsJson',
            'preselect'
        ));
    }


    /*
    |--------------------------------------------------------------------------
    | GUARDAR SOLICITUD
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'instrumental'    => 'required|string|max:120',
            'solution'        => 'required|string|max:120',
            'quantity'        => 'nullable|integer|min:1',

            // Campos técnicos dinámicos
            'process_data'    => 'nullable|array',
            'process_data.*'  => 'nullable|string|max:500',

            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:120',
            'phone'   => 'required|string|max:50',
            'company' => 'nullable|string|max:120',
            'message' => 'required|string|max:2000',

            // Múltiples archivos
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,dwg,dxf|max:10240',

        ]);


        /*
        |--------------------------------------------------------------------------
        | GUARDAR ARCHIVOS
        |--------------------------------------------------------------------------
        */

        $files = [];

        if ($request->hasFile('attachments')) {

            foreach ($request->file('attachments') as $file) {

                $path = $file->store('quotes', 'public');

                $files[] = $path;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | GUARDAR SOLICITUD
        |--------------------------------------------------------------------------
        */

        $quoteRequest = QuoteRequest::create([

            'instrumental' => $request->instrumental,
            'solution'     => $request->solution,
            'quantity'     => $request->quantity,

            'attachment'   => json_encode($files),
            'process_data' => $request->input('process_data', []),

            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'company' => $request->company,
            'message' => $request->message,

            'is_read' => 0,

        ]);

        try {
            Mail::to($this->notificationRecipients())
                ->send(new QuoteRequestNotificationMail($quoteRequest, $files));
        } catch (\Exception $e) {
            logger()->error('Error enviando notificación de presupuesto: ' . $e->getMessage());
        }

        return back()->with('success', 'Solicitud enviada correctamente.');
    }

    private function notificationRecipients(): array
    {
        $footer = json_decode(Setting::where('key', 'footer')->value('value') ?? '{}', true);
        $configured = $footer['mail_recipients'] ?? '';
        $recipients = array_filter(array_map('trim', explode(',', $configured)));

        return $recipients ?: config('mail.to.addresses', [config('mail.to.address')]);
    }

}
