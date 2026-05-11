<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDownload;
use Illuminate\Http\Request;
use App\Support\Translation;

class ProductDownloadController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'file' => 'required|file|max:20480',
            'type' => 'nullable|string|max:50',
        ]);

        $file = $request->file('file');

        $path = $file->store('product-downloads', 'public');

        $size = $this->formatBytes($file->getSize());

        // Obtener último orden
        $lastOrder = $product->downloads()->max('order');

        // Orden seguro (evita error string + int)
        $order = is_numeric($lastOrder) ? $lastOrder + 1 : 1;

        ProductDownload::create([
            'product_id'   => $product->id,
            'name'         => Translation::input($request->input('name')),
            'file'         => $path,
            'type'         => $request->input('type') ?: strtoupper($file->getClientOriginalExtension()),
            'size'         => $size,
            'order'        => $order,
            'is_active'    => true,
            'is_principal' => false,
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function togglePrincipal(Product $product, ProductDownload $download)
    {
        // Si ya es principal, lo desactiva
        if ($download->is_principal) {

            $download->update([
                'is_principal' => false
            ]);

        } else {

            // Desactiva cualquier otro principal
            $product->downloads()->update([
                'is_principal' => false
            ]);

            $download->update([
                'is_principal' => true
            ]);
        }

        return back()->with('success', 'Archivo principal actualizado.');
    }

    public function destroy(Product $product, ProductDownload $download)
    {
        $download->delete();

        return back()->with('success', 'Archivo eliminado.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
