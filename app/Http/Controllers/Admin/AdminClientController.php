<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminClientController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Clients/Index', [
            'clients' => Client::orderBy('order')->orderBy('name')->get()->map(fn($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'logo_url' => $c->logo_url,
                'website'  => $c->website,
                'is_active'=> $c->is_active,
                'order'    => $c->order,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Clients/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:200',
            'website'   => 'nullable|url|max:500',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'logo'      => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('clients', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        Client::create($data);

        return redirect('/admin/clients')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Client $client)
    {
        return Inertia::render('Admin/Clients/Edit', [
            'client' => [
                'id'        => $client->id,
                'name'      => $client->name,
                'logo_url'  => $client->logo_url,
                'website'   => $client->website,
                'is_active' => $client->is_active,
                'order'     => $client->order,
            ],
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:200',
            'website'   => 'nullable|url|max:500',
            'order'     => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'logo'      => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('clients', 'public');
        } else {
            unset($data['logo']);
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $client->update($data);

        return redirect('/admin/clients')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect('/admin/clients')
            ->with('success', 'Cliente eliminado.');
    }
}
