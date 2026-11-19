<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OportunidadController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->entrepreneurProfile;

        $oportunidades = $profile
            ? Opportunity::where('entrepreneur_profile_id', $profile->id)
                ->with('establishment', 'proposals')
                ->paginate(15)
            : collect([]);

        return Inertia::render('oportunidades/Index', [
            'oportunidades' => $oportunidades,
            'estados'       => Opportunity::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'establishment_id' => 'required|exists:establishments,id',
            'status'           => 'required|in:' . implode(',', Opportunity::STATUSES),
            'notes'            => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
            'agreed_conditions' => 'nullable|string',
        ]);

        $data['entrepreneur_profile_id'] = $request->user()->entrepreneurProfile->id;

        Opportunity::create($data);

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad creada correctamente.');
    }

    public function update(Request $request, Opportunity $oportunidad)
    {
        $data = $request->validate([
            'status'            => 'sometimes|in:' . implode(',', Opportunity::STATUSES),
            'notes'             => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
            'agreed_conditions' => 'nullable|string',
        ]);

        $oportunidad->update($data);

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad actualizada correctamente.');
    }

    public function destroy(Request $request, Opportunity $oportunidad)
    {
        $oportunidad->delete();

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad eliminada correctamente.');
    }
}
