<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EntrepreneurProfileController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('emprendedor/Perfil', [
            'perfil' => $request->user()->entrepreneurProfile,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->entrepreneurProfile) {
            return redirect()->route('emprendedor.perfil', [
                'current_team' => $request->route('current_team'),
            ])->with('error', 'Ya tienes un perfil de emprendedor.');
        }

        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'phone'         => 'nullable|string|max:20',
            'whatsapp'      => 'nullable|string|max:20',
        ]);

        $data['user_id'] = $user->id;

        $user->entrepreneurProfile()->create($data);

        return redirect()->route('emprendedor.perfil', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Perfil creado correctamente.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'phone'         => 'nullable|string|max:20',
            'whatsapp'      => 'nullable|string|max:20',
        ]);

        $request->user()->entrepreneurProfile()->update($data);

        return redirect()->route('emprendedor.perfil', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Perfil actualizado correctamente.');
    }
}
