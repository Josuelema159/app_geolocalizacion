<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha comercial — {{ $perfil->business_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }
        h1 { font-size: 22px; margin: 0 0 4px; color: #0f766e; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #0f766e; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        h3 { font-size: 11px; margin: 0 0 4px; }
        p { margin: 3px 0; }
        .header { border-bottom: 3px solid #0f766e; padding-bottom: 10px; margin-bottom: 12px; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; background: #e6fffa; color: #0f766e; padding: 2px 6px; border-radius: 3px; font-size: 8px; }
        .product { border: 1px solid #e5e7eb; border-radius: 5px; padding: 10px; margin-bottom: 10px; page-break-inside: avoid; }
        .product-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .product img { max-width: 90px; max-height: 90px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td { padding: 3px 4px; vertical-align: top; }
        td.label { width: 32%; color: #6b7280; font-weight: bold; }
        .footer { margin-top: 18px; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 8px; color: #6b7280; }
        ul { margin: 3px 0 0 14px; padding: 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $perfil->business_name }}</h1>
        <p class="muted">Ficha comercial del emprendedor</p>
        @if($perfil->description)
            <p>{{ $perfil->description }}</p>
        @endif
        <p>
            @if($perfil->phone) <strong>Teléfono:</strong> {{ $perfil->phone }} &nbsp;|&nbsp; @endif
            @if($perfil->whatsapp) <strong>WhatsApp:</strong> {{ $perfil->whatsapp }} &nbsp;|&nbsp; @endif
            @if($perfil->coverage_zones) <strong>Zonas de entrega:</strong> {{ implode(', ', $perfil->coverage_zones) }} @endif
        </p>
    </div>

    <h2>Catálogo de productos ({{ $productos->count() }})</h2>

    @forelse($productos as $producto)
        <div class="product">
            <div class="product-header">
                <div>
                    <h3>{{ $producto->name }}</h3>
                    @if($producto->category) <span class="badge">{{ $producto->category->name }}</span> @endif
                    <p class="muted">{{ $producto->presentation }}</p>
                </div>
                @if($producto->photo_path && file_exists(public_path('storage/'.$producto->photo_path)))
                    <img src="{{ public_path('storage/'.$producto->photo_path) }}" alt="Foto">
                @endif
            </div>

            @if($producto->description)
                <p>{{ $producto->description }}</p>
            @endif

            <table>
                <tr>
                    <td class="label">Precio al comercio</td>
                    <td>{{ $producto->wholesale_price ? '$'.number_format($producto->wholesale_price, 2) : 'A convenir' }}</td>
                    <td class="label">Precio sugerido al consumidor</td>
                    <td>{{ $producto->suggested_retail_price ? '$'.number_format($producto->suggested_retail_price, 2) : 'A convenir' }}</td>
                </tr>
                <tr>
                    <td class="label">Pedido mínimo</td>
                    <td>{{ $producto->minimum_order ?: 'No definido' }}</td>
                    <td class="label">Capacidad semanal</td>
                    <td>{{ $producto->weekly_capacity ?: 'No definida' }}</td>
                </tr>
                <tr>
                    <td class="label">Vida útil</td>
                    <td>{{ $producto->shelf_life_days ? $producto->shelf_life_days.' días' : 'No definida' }}</td>
                    <td class="label">Conservación</td>
                    <td>
                        {{ $producto->storage_conditions ?: 'No definida' }}
                        @if($producto->required_temperature_min !== null || $producto->required_temperature_max !== null)
                            ({{ $producto->required_temperature_min ?? '?' }}°C a {{ $producto->required_temperature_max ?? '?' }}°C)
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Zonas de entrega</td>
                    <td>{{ $producto->coverage_zones ? implode(', ', $producto->coverage_zones) : 'No definidas' }}</td>
                    <td class="label">Frecuencia de entrega</td>
                    <td>{{ $producto->delivery_frequency ?: 'No definida' }}</td>
                </tr>
            </table>

            @if($producto->documentation && count($producto->documentation))
                <p><strong>Documentación disponible:</strong></p>
                <ul>
                    @foreach($producto->documentation as $documento)
                        <li>{{ $documento }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <p class="muted">Este emprendedor aún no tiene productos públicos.</p>
    @endforelse

    <div class="footer">
        Ficha generada el {{ now()->format('d/m/Y H:i') }} — Plataforma de geolocalización de emprendimientos.
    </div>
</body>
</html>
