<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Establishment;
use App\Models\EstablishmentNeed;
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
        $data = $this->validateEstablishment($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $categories = $data['categories'] ?? [];
        $needs = $data['needs'] ?? [];
        unset($data['categories'], $data['needs']);

        $data = $this->applyPhoto($request, $data);
        $data = $this->normalizeFields($data);

        $establishment = Establishment::create($data);
        $establishment->categories()->sync($categories);
        $this->syncNeeds($establishment, $needs);

        return redirect()->route('establecimientos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Establecimiento creado correctamente.');
    }

    public function show($currentTeam, Establishment $establishment)
    {
        $establishment->load('categories', 'reports', 'needs.category');

        return Inertia::render('establecimientos/Show', [
            'establecimiento' => $establishment,
            'establishment'   => $establishment,
            'canEdit'         => true,
        ]);
    }

    public function edit($currentTeam, Establishment $establishment)
    {
        return Inertia::render('establecimientos/Edit', [
            'establecimiento' => $establishment->load('categories', 'needs'),
            'categorias'      => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $currentTeam, Establishment $establishment)
    {
        $data = $this->validateEstablishment($request);

        $categories = $data['categories'] ?? [];
        $needs = $data['needs'] ?? [];
        unset($data['categories'], $data['needs']);

        $data = $this->applyPhoto($request, $data);
        $data = $this->normalizeFields($data);

        $establishment->update($data);
        $establishment->categories()->sync($categories);
        $this->syncNeeds($establishment, $needs);

        return redirect()->route('establecimientos.show', [
            'current_team'  => $request->route('current_team'),
            'establishment' => $establishment,
        ])->with('success', 'Establecimiento actualizado correctamente.');
    }

    public function verify(Request $request, $currentTeam, Establishment $establishment)
    {
        $establishment->update([
            'verification_status' => 'verificado',
            'verified_at'         => now(),
            'last_verified_at'    => now(),
        ]);

        return back()->with('success', 'Establecimiento marcado como verificado.');
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

    private function validateEstablishment(Request $request): array
    {
        return $request->validate([
            'name'                      => 'required|string|max:255',
            'type'                      => 'required|in:tienda,minimercado,supermercado',
            'address'                   => 'required|string|max:500',
            'province'                  => 'required|string|max:100',
            'canton'                    => 'required|string|max:100',
            'parish'                    => 'nullable|string|max:100',
            'sector'                    => 'nullable|string|max:100',
            'latitude'                  => 'required|numeric|between:-90,90',
            'longitude'                 => 'required|numeric|between:-180,180',
            'reception_status'          => 'required|in:recibe,no_recibe,por_confirmar',
            'commercial_contact_name'   => 'nullable|string|max:255',
            'commercial_phone'          => 'nullable|string|max:20',
            'commercial_email'          => 'nullable|email',
            'authorized_channel'        => 'nullable|string|max:100',
            'has_refrigeration'         => 'boolean',
            'refrigeration_capacity'    => 'nullable|string|max:255',
            'weekly_purchase_volume'    => 'nullable|numeric|min:0',
            'supply_frequency'          => 'nullable|string|max:100',
            'attention_hours'           => 'nullable',
            'supplier_attention_hours'  => 'nullable',
            'entry_requirements'        => 'nullable',
            'purchase_conditions'       => 'nullable|array',
            'supermarket_purchase_mode' => 'nullable|in:sucursal,oficina_central',
            'central_office_contact'    => 'nullable|string|max:500',
            'photo'                     => 'nullable|image|max:2048',
            'categories'                => 'nullable|array',
            'categories.*'              => 'exists:categories,id',
            'needs'                     => 'nullable|array',
            'needs.*.product_name'      => 'required_with:needs|string|max:255',
            'needs.*.category_id'       => 'nullable|exists:categories,id',
            'needs.*.quantity'          => 'nullable|string|max:100',
            'needs.*.frequency'         => 'nullable|string|max:100',
            'needs.*.notes'             => 'nullable|string',
        ]);
    }

    private function normalizeFields(array $data): array
    {
        foreach (['attention_hours', 'supplier_attention_hours', 'entry_requirements'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->linesToArray($data[$field]);
            }
        }

        if (isset($data['purchase_conditions'])) {
            $conditions = $data['purchase_conditions'];
            if (! is_array($conditions)) {
                $conditions = ['payment_mode' => (string) $conditions];
            }
            $data['purchase_conditions'] = array_filter([
                'payment_mode'  => $conditions['payment_mode'] ?? null,
                'initial_order' => $conditions['initial_order'] ?? null,
                'returns'       => $conditions['returns'] ?? null,
            ], fn ($value) => filled($value));
        }

        if (($data['type'] ?? null) !== 'supermercado') {
            $data['supermarket_purchase_mode'] = null;
            $data['central_office_contact'] = null;
        }

        unset($data['photo']);

        return $data;
    }

    private function linesToArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    private function applyPhoto(Request $request, array $data): array
    {
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('establishments', 'public');
        }

        return $data;
    }

    private function syncNeeds(Establishment $establishment, array $needs): void
    {
        $keepIds = [];

        foreach ($needs as $need) {
            if (empty($need['product_name'])) {
                continue;
            }

            $record = isset($need['id'])
                ? $establishment->needs()->whereKey($need['id'])->first()
                : null;

            $payload = [
                'product_name' => $need['product_name'],
                'category_id'  => $need['category_id'] ?: null,
                'quantity'     => $need['quantity'] ?? null,
                'frequency'    => $need['frequency'] ?? null,
                'notes'        => $need['notes'] ?? null,
            ];

            if ($record) {
                $record->update($payload);
            } else {
                $record = $establishment->needs()->create($payload);
            }

            $keepIds[] = $record->id;
        }

        $establishment->needs()->whereNotIn('id', $keepIds)->delete();
    }

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
