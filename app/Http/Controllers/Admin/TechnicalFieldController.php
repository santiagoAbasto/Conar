<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicalField;
use App\Models\ProductSubcategory;
use Illuminate\Http\Request;

class TechnicalFieldController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $fields = TechnicalField::with('subcategory')
            ->orderBy('subcategory_id')
            ->orderBy('order')
            ->get()
            ->groupBy('subcategory_id');

        $subcategories = ProductSubcategory::where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        return view('admin.technical-fields.index', compact('fields', 'subcategories'));
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $subcategories = ProductSubcategory::where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('admin.technical-fields.create', compact('subcategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subcategory_id' => 'required|exists:product_subcategories,id',
            'label'          => 'required|string|max:120',
            'name'           => 'required|string|max:80|regex:/^[a-z0-9_]+$/',
            'type'           => 'required|in:text,number,select',
            'order'          => 'nullable|integer|min:0',
        ]);

        TechnicalField::create([
            'subcategory_id' => $request->subcategory_id,
            'label'          => $request->label,
            'name'           => $request->name,
            'type'           => $request->type,
            'order'          => $request->order ?? 0,
        ]);

        return redirect('/admin/technical-fields')
            ->with('success', 'Campo técnico creado.');
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */

    public function edit(TechnicalField $technicalField)
    {
        $subcategories = ProductSubcategory::where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('admin.technical-fields.edit', compact('technicalField', 'subcategories'));
    }

    public function update(Request $request, TechnicalField $technicalField)
    {
        $request->validate([
            'subcategory_id' => 'required|exists:product_subcategories,id',
            'label'          => 'required|string|max:120',
            'name'           => 'required|string|max:80|regex:/^[a-z0-9_]+$/',
            'type'           => 'required|in:text,number,select',
            'order'          => 'nullable|integer|min:0',
        ]);

        $technicalField->update([
            'subcategory_id' => $request->subcategory_id,
            'label'          => $request->label,
            'name'           => $request->name,
            'type'           => $request->type,
            'order'          => $request->order ?? 0,
        ]);

        return redirect('/admin/technical-fields')
            ->with('success', 'Campo técnico actualizado.');
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function destroy(TechnicalField $technicalField)
    {
        $technicalField->delete();

        return redirect('/admin/technical-fields')
            ->with('success', 'Campo técnico eliminado.');
    }

}
