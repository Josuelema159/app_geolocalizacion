<?php

namespace App\Http\Controllers;

use App\Models\EntrepreneurProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'business_name'  => 'required|string|max:255',
            'description'    => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'whatsapp'       => 'nullable|string|max:20',
            'coverage_zones' => 'nullable',
        ]);

        $data['user_id'] = $user->id;
        $data['slug'] = $this->uniqueSlug($data['business_name']);
        $data['coverage_zones'] = $this->parseZones($data['coverage_zones'] ?? null);

        $user->entrepreneurProfile()->create($data);

        return redirect()->route('emprendedor.perfil', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Perfil creado correctamente.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_name'  => 'required|string|max:255',
            'description'    => 'nullable|string',
            'phone'          => 'nullable|string|max:20',
            'whatsapp'       => 'nullable|string|max:20',
            'coverage_zones' => 'nullable',
        ]);

        if (array_key_exists('coverage_zones', $data)) {
            $data['coverage_zones'] = $this->parseZones($data['coverage_zones']);
        }

        $request->user()->entrepreneurProfile()->update($data);

        return redirect()->route('emprendedor.perfil', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Perfil actualizado correctamente.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (EntrepreneurProfile::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function parseZones(mixed $zones): array
    {
        if (is_array($zones)) {
            return array_values(array_filter(array_map('trim', $zones)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $zones))));
    }
}
