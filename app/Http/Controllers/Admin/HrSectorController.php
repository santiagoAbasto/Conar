<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrSector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HrSectorController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/HR/Sectors/Index', [
            'sectors' => HrSector::withCount('positions')->orderBy('order')->get()->map(fn($s) => [
                'id'              => $s->id,
                'name'            => $s->name,
                'description'     => $s->description,
                'is_active'       => $s->is_active,
                'order'           => $s->order,
                'positions_count' => $s->positions_count,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/HR/Sectors/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        HrSector::create($data);

        return redirect('/admin/hr/sectors')
            ->with('success', 'Sector RRHH creado correctamente.');
    }

    public function edit(HrSector $hrSector)
    {
        return Inertia::render('Admin/HR/Sectors/Edit', [
            'sector' => $hrSector->toArray(),
        ]);
    }

    public function update(Request $request, HrSector $hrSector)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $hrSector->update($data);

        return redirect('/admin/hr/sectors')
            ->with('success', 'Sector RRHH actualizado correctamente.');
    }

    public function destroy(HrSector $hrSector)
    {
        $hrSector->delete();
        return redirect('/admin/hr/sectors')
            ->with('success', 'Sector RRHH eliminado.');
    }
}
