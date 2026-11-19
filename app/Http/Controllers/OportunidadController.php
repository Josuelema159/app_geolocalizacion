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
            'establishment_id'  => 'required|exists:establishments,id',
            'status'            => 'sometimes|in:' . implode(',', Opportunity::STATUSES),
            'private_notes'     => 'nullable|string',
            'notes'             => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
            'agreed_conditions' => 'nullable|string',
        ]);

        $data['status'] = $data['status'] ?? Opportunity::STATUSES[0];
        if (isset($data['notes'])) {
            $data['private_notes'] = $data['private_notes'] ?? $data['notes'];
            unset($data['notes']);
        }
        $data['entrepreneur_profile_id'] = $request->user()->entrepreneurProfile->id;

        Opportunity::create($data);

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad creada correctamente.');
    }

    public function update(Request $request, $currentTeam, Opportunity $oportunidad)
    {
        $data = $request->validate([
            'status'            => 'sometimes|in:' . implode(',', Opportunity::STATUSES),
            'private_notes'     => 'nullable|string',
            'notes'             => 'nullable|string',
            'next_follow_up_at' => 'nullable|date',
            'agreed_conditions' => 'nullable|string',
        ]);

        if (isset($data['notes'])) {
            $data['private_notes'] = $data['private_notes'] ?? $data['notes'];
            unset($data['notes']);
        }

        $oportunidad->update($data);

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad actualizada correctamente.');
    }

    public function destroy(Request $request, $currentTeam, Opportunity $oportunidad)
    {
        $oportunidad->delete();

        return redirect()->route('oportunidades.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Oportunidad eliminada correctamente.');
    }
}
