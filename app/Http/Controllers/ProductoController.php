<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->entrepreneurProfile;

        $productos = $profile
            ? Product::where('entrepreneur_profile_id', $profile->id)
                ->with('category')
                ->paginate(12)
            : collect([]);

        return Inertia::render('productos/Index', [
            'productos' => $productos,
            'perfil'    => $profile,
            'categorias' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('productos/Create', [
            'categorias' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'category_id'            => 'required|exists:categories,id',
            'description'            => 'nullable|string',
            'presentation'           => 'required|string|max:255',
            'wholesale_price'        => 'nullable|numeric|min:0',
            'suggested_retail_price' => 'nullable|numeric|min:0',
            'minimum_order'          => 'required|numeric|min:1',
            'weekly_capacity'        => 'nullable|numeric|min:0',
            'shelf_life_days'           => 'nullable|integer|min:1',
            'storage_conditions'        => 'nullable|string|max:255',
            'required_temperature_min'  => 'nullable|numeric',
            'required_temperature_max'  => 'nullable|numeric',
            'coverage_zones'            => 'nullable|string',
            'delivery_frequency'        => 'nullable|string|max:100',
            'documentation'             => 'nullable',
            'photo'                     => 'nullable|image|max:2048',
            'is_public'                 => 'boolean',
        ]);

        $data['entrepreneur_profile_id'] = $request->user()->entrepreneurProfile->id;
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data = $this->prepareProductData($request, $data);

        Product::create($data);

        return redirect()->route('productos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Producto creado correctamente.');
    }

    public function show($currentTeam, Product $product)
    {
        return Inertia::render('productos/Show', [
            'producto' => $product->load('category'),
            'product'  => $product->load('category'),
            'canEdit'  => true,
        ]);
    }

    public function edit($currentTeam, Product $product)
    {
        return Inertia::render('productos/Edit', [
            'product'    => $product->load('category'),
            'producto'   => $product->load('category'),
            'categorias' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $currentTeam, Product $product)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'category_id'            => 'required|exists:categories,id',
            'description'            => 'nullable|string',
            'presentation'           => 'required|string|max:255',
            'wholesale_price'        => 'nullable|numeric|min:0',
            'suggested_retail_price' => 'nullable|numeric|min:0',
            'minimum_order'          => 'required|numeric|min:1',
            'weekly_capacity'        => 'nullable|numeric|min:0',
            'shelf_life_days'           => 'nullable|integer|min:1',
            'storage_conditions'        => 'nullable|string|max:255',
            'required_temperature_min'  => 'nullable|numeric',
            'required_temperature_max'  => 'nullable|numeric',
            'coverage_zones'            => 'nullable|string',
            'delivery_frequency'        => 'nullable|string|max:100',
            'documentation'             => 'nullable',
            'photo'                     => 'nullable|image|max:2048',
            'is_public'                 => 'boolean',
        ]);

        $data = $this->prepareProductData($request, $data);

        $product->update($data);

        return redirect()->route('productos.show', [
            'current_team' => $request->route('current_team'),
            'product'      => $product,
        ])->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, $currentTeam, Product $product)
    {
        $product->delete();

        return redirect()->route('productos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Producto eliminado correctamente.');
    }

    private function prepareProductData(Request $request, array $data): array
    {
        if (isset($data['coverage_zones']) && is_string($data['coverage_zones'])) {
            $data['coverage_zones'] = array_values(array_filter(array_map(
                'trim',
                explode(',', $data['coverage_zones'])
            )));
        }

        if (isset($data['documentation'])) {
            $documentation = $data['documentation'];
            if (is_string($documentation)) {
                $documentation = preg_split('/\r\n|\r|\n|,/', $documentation);
            }
            $data['documentation'] = array_values(array_filter(array_map('trim', (array) $documentation)));
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        unset($data['photo']);

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
