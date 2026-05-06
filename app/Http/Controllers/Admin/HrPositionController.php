<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrPosition;
use App\Models\HrSector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HrPositionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/HR/Positions/Index', [
            'positions' => HrPosition::with('sector')->withCount('applications')->orderBy('order')->get()->map(fn($p) => [
                'id'                 => $p->id,
                'title'              => $p->title,
                'sector'             => $p->sector ? ['id' => $p->sector->id, 'name' => $p->sector->name] : null,
                'is_active'          => $p->is_active,
                'order'              => $p->order,
                'applications_count' => $p->applications_count,
            ]),
            'sectors' => HrSector::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/HR/Positions/Create', [
            'sectors' => HrSector::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hr_sector_id' => 'nullable|exists:hr_sectors,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'requirements' => 'nullable|string',
            'order'        => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        HrPosition::create($data);

        return redirect('/admin/hr/positions')
            ->with('success', 'Puesto creado correctamente.');
    }

    public function edit(HrPosition $hrPosition)
    {
        return Inertia::render('Admin/HR/Positions/Edit', [
            'position' => [
                'id'           => $hrPosition->id,
                'hr_sector_id' => $hrPosition->hr_sector_id,
                'title'        => $hrPosition->title,
                'description'  => $hrPosition->description,
                'requirements' => $hrPosition->requirements,
                'is_active'    => $hrPosition->is_active,
                'order'        => $hrPosition->order,
            ],
            'sectors' => HrSector::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, HrPosition $hrPosition)
    {
        $data = $request->validate([
            'hr_sector_id' => 'nullable|exists:hr_sectors,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'requirements' => 'nullable|string',
            'order'        => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $hrPosition->update($data);

        return redirect('/admin/hr/positions')
            ->with('success', 'Puesto actualizado correctamente.');
    }

    public function destroy(HrPosition $hrPosition)
    {
        $hrPosition->delete();
        return redirect('/admin/hr/positions')
            ->with('success', 'Puesto eliminado.');
    }
}
