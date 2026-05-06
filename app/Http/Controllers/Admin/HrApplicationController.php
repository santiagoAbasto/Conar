<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrApplication;
use App\Models\HrPosition;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HrApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = HrApplication::with('position')->latest();

        if ($request->filled('position')) {
            $query->where('hr_position_id', $request->position);
        }

        return Inertia::render('Admin/HR/Applications/Index', [
            'applications' => $query->paginate(20)->through(fn($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'email'      => $a->email,
                'position'   => $a->position ? ['id' => $a->position->id, 'title' => $a->position->title] : null,
                'is_read'    => $a->is_read,
                'created_at' => $a->created_at->format('d/m/Y H:i'),
            ]),
            'positions' => HrPosition::orderBy('title')->get(['id', 'title']),
            'filters'   => $request->only(['position']),
        ]);
    }

    public function show(HrApplication $hrApplication)
    {
        if (!$hrApplication->is_read) {
            $hrApplication->update(['is_read' => true]);
        }

        return Inertia::render('Admin/HR/Applications/Show', [
            'application' => [
                'id'         => $hrApplication->id,
                'name'       => $hrApplication->name,
                'email'      => $hrApplication->email,
                'phone'      => $hrApplication->phone,
                'message'    => $hrApplication->message,
                'cv_url'     => $hrApplication->cv_url,
                'is_read'    => $hrApplication->is_read,
                'created_at' => $hrApplication->created_at->format('d/m/Y H:i'),
                'position'   => $hrApplication->position ? [
                    'id'    => $hrApplication->position->id,
                    'title' => $hrApplication->position->title,
                ] : null,
            ],
        ]);
    }

    public function destroy(HrApplication $hrApplication)
    {
        $hrApplication->delete();
        return redirect('/admin/hr/applications')
            ->with('success', 'Postulación eliminada.');
    }
}
