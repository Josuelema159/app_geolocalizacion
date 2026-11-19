<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Establishment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EstablecimientoController extends Controller
{
    public function index(Request $request)
    {
        $query = Establishment::with('categories')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('type', $tipo);
        }

        if ($recepcion = $request->get('recepcion')) {
            $query->where('reception_status', $recepcion);
        }

        return Inertia::render('establecimientos/Index', [
            'establecimientos' => $query->paginate(15)->withQueryString(),
            'filters'          => $request->only('search', 'tipo', 'recepcion'),
            'categorias'       => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('establecimientos/Create', [
            'categorias' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'type'                    => 'required|in:tienda,minimercado,supermercado',
            'address'                 => 'required|string|max:500',
            'province'                => 'required|string|max:100',
            'canton'                  => 'required|string|max:100',
            'parish'                  => 'nullable|string|max:100',
            'sector'                  => 'nullable|string|max:100',
            'latitude'                => 'required|numeric|between:-90,90',
            'longitude'               => 'required|numeric|between:-180,180',
            'reception_status'        => 'required|in:recibe,no_recibe,por_confirmar',
            'commercial_contact_name' => 'nullable|string|max:255',
            'commercial_phone'        => 'nullable|string|max:20',
            'commercial_email'        => 'nullable|email',
            'authorized_channel'      => 'nullable|string|max:100',
            'has_refrigeration'       => 'boolean',
            'categories'              => 'nullable|array',
            'categories.*'            => 'exists:categories,id',
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);

        $categories = $data['categories'] ?? [];
        unset($data['categories']);

        $establishment = Establishment::create($data);
        $establishment->categories()->sync($categories);

        return redirect()->route('establecimientos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Establecimiento creado correctamente.');
    }

    public function show(Establishment $establishment)
    {
        return Inertia::render('establecimientos/Show', [
            'establecimiento' => $establishment->load('categories', 'reports'),
        ]);
    }

    public function edit(Establishment $establishment)
    {
        return Inertia::render('establecimientos/Edit', [
            'establecimiento' => $establishment->load('categories'),
            'categorias'      => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Establishment $establishment)
    {
        $data = $request->validate([
            'name'                    => 'required|string|max:255',
            'type'                    => 'required|in:tienda,minimercado,supermercado',
            'address'                 => 'required|string|max:500',
            'province'                => 'required|string|max:100',
            'canton'                  => 'required|string|max:100',
            'parish'                  => 'nullable|string|max:100',
            'sector'                  => 'nullable|string|max:100',
            'latitude'                => 'required|numeric|between:-90,90',
            'longitude'               => 'required|numeric|between:-180,180',
            'reception_status'        => 'required|in:recibe,no_recibe,por_confirmar',
            'commercial_contact_name' => 'nullable|string|max:255',
            'commercial_phone'        => 'nullable|string|max:20',
            'commercial_email'        => 'nullable|email',
            'authorized_channel'      => 'nullable|string|max:100',
            'has_refrigeration'       => 'boolean',
            'categories'              => 'nullable|array',
            'categories.*'            => 'exists:categories,id',
        ]);

        $categories = $data['categories'] ?? [];
        unset($data['categories']);

        $establishment->update($data);
        $establishment->categories()->sync($categories);

        return redirect()->route('establecimientos.show', [
            'current_team'    => $request->route('current_team'),
            'establecimiento' => $establishment,
        ])->with('success', 'Establecimiento actualizado correctamente.');
    }

    public function mapa()
    {
        $establecimientos = Establishment::select(
            'id', 'name', 'type', 'latitude', 'longitude', 'reception_status', 'slug'
        )->get();

        return Inertia::render('establecimientos/Mapa', [
            'establecimientos' => $establecimientos,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Establishment::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
