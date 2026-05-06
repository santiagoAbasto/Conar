<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDownloadController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Downloads/Index', [
            'downloads' => Download::orderBy('order')->orderBy('name')->get()->map(fn($d) => [
                'id'          => $d->id,
                'name'        => $d->name,
                'category'    => $d->category,
                'description' => $d->description,
                'size'        => $d->size,
                'file_url'    => $d->file_url,
                'is_active'   => $d->is_active,
                'order'       => $d->order,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Downloads/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
            'file'        => 'required|file|max:20480',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file'] = $file->store('downloads', 'public');
            $data['size'] = $this->formatBytes($file->getSize());
        }

        $data['is_active'] = $request->boolean('is_active', true);
        Download::create($data);

        return redirect('/admin/downloads')
            ->with('success', 'Descarga creada correctamente.');
    }

    public function edit(Download $download)
    {
        return Inertia::render('Admin/Downloads/Edit', [
            'download' => [
                'id'          => $download->id,
                'name'        => $download->name,
                'category'    => $download->category,
                'description' => $download->description,
                'size'        => $download->size,
                'file_url'    => $download->file_url,
                'is_active'   => $download->is_active,
                'order'       => $download->order,
            ],
        ]);
    }

    public function update(Request $request, Download $download)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
            'file'        => 'nullable|file|max:20480',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file'] = $file->store('downloads', 'public');
            $data['size'] = $this->formatBytes($file->getSize());
        } else {
            unset($data['file']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $download->update($data);

        return redirect('/admin/downloads')
            ->with('success', 'Descarga actualizada correctamente.');
    }

    public function destroy(Download $download)
    {
        $download->delete();
        return redirect('/admin/downloads')
            ->with('success', 'Descarga eliminada.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
