<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    /**
     * El usuario principal es el que tiene el ID más bajo (el primero creado).
     * No se puede eliminar, solo actualizar su correo y contraseña.
     */
    private function getPrincipalId(): int
    {
        return (int) User::min('id');
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Lista de usuarios
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $principalId = $this->getPrincipalId();

        $users = User::orderBy('id')
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'created_at' => $u->created_at?->format('d/m/Y'),
                'is_principal' => $u->id === $principalId,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users'       => $users,
            'principalId' => $principalId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — Crear nuevo usuario admin
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo es obligatorio.',
            'email.email'        => 'Ingresá un correo válido.',
            'email.unique'       => 'Ese correo ya está en uso.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE — Actualizar correo y/o contraseña
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, User $user)
    {
        $rules = [
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ];

        $messages = [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'Ingresá un correo válido.',
            'email.unique'   => 'Ese correo ya está en uso.',
        ];

        // Contraseña es opcional al editar
        if ($request->filled('password')) {
            $rules['password']              = ['required', 'confirmed', Password::min(8)];
            $messages['password.confirmed'] = 'Las contraseñas no coinciden.';
            $messages['password.min']       = 'La contraseña debe tener al menos 8 caracteres.';
        }

        $request->validate($rules, $messages);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY — Eliminar usuario (no permitido para el principal)
    |--------------------------------------------------------------------------
    */

    public function destroy(User $user)
    {
        if ($user->id === $this->getPrincipalId()) {
            return back()->with('error', 'El usuario principal no puede eliminarse.');
        }

        // No permitir eliminarse a uno mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No podés eliminar tu propio usuario mientras estás activo.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
