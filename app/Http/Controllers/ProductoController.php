<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
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
            'shelf_life_days'        => 'nullable|integer|min:1',
            'storage_conditions'     => 'nullable|string|max:255',
            'coverage_zones'         => 'nullable|string',
            'delivery_frequency'     => 'nullable|string|max:100',
            'is_public'              => 'boolean',
        ]);

        $data['entrepreneur_profile_id'] = $request->user()->entrepreneurProfile->id;

        // Guardar coverage_zones como array JSON
        if (isset($data['coverage_zones']) && is_string($data['coverage_zones'])) {
            $data['coverage_zones'] = array_map(
                'trim',
                array_filter(explode(',', $data['coverage_zones']))
            );
        }

        Product::create($data);

        return redirect()->route('productos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product)
    {
        return Inertia::render('productos/Show', [
            'producto' => $product->load('category'),
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('productos/Edit', [
            'producto'   => $product->load('category'),
            'categorias' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
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
            'shelf_life_days'        => 'nullable|integer|min:1',
            'storage_conditions'     => 'nullable|string|max:255',
            'coverage_zones'         => 'nullable|string',
            'delivery_frequency'     => 'nullable|string|max:100',
            'is_public'              => 'boolean',
        ]);

        if (isset($data['coverage_zones']) && is_string($data['coverage_zones'])) {
            $data['coverage_zones'] = array_map(
                'trim',
                array_filter(explode(',', $data['coverage_zones']))
            );
        }

        $product->update($data);

        return redirect()->route('productos.show', [
            'current_team' => $request->route('current_team'),
            'producto'     => $product,
        ])->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, Product $product)
    {
        $product->delete();

        return redirect()->route('productos.index', [
            'current_team' => $request->route('current_team'),
        ])->with('success', 'Producto eliminado correctamente.');
    }
}
