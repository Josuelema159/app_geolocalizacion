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
        // Establecimientos registrados en la BD
        $deDB = Establishment::select(
            'id', 'name', 'type', 'address', 'sector', 'canton', 'province',
            'parish', 'latitude', 'longitude', 'reception_status', 'slug',
            'commercial_phone', 'has_refrigeration', 'supply_frequency'
        )->get()->map(fn ($e) => [
            'id'               => $e->id,
            'slug'             => $e->slug,
            'nombre'           => $e->name,
            'tipo'             => $e->type,
            'direccion'        => $e->address,
            'sector'           => $e->sector ?? '',
            'parroquia'        => $e->parish ?? '',
            'canton'           => $e->canton,
            'provincia'        => $e->province,
            'lat'              => (float) $e->latitude,
            'lng'              => (float) $e->longitude,
            'recepcion'        => $e->reception_status,
            'telefono'         => $e->commercial_phone ?? '',
            'refrigeracion'    => $e->has_refrigeration ? 'Sí' : 'No',
            'frecuencia'       => $e->supply_frequency ?? '',
            'propietario'      => '',
            'marcas'           => '',
            'proveedor'        => '',
            'notas'            => '',
            'grupo'            => 0,
            'fuente'           => 'bd',
        ]);

        // 162 establecimientos del Excel (datos estáticos)
        $delExcel = $this->datosExcel();

        // Fusionar: BD primero, luego Excel
        $todos = collect($deDB)->concat($delExcel)->values();

        return Inertia::render('establecimientos/Mapa', [
            'establecimientos' => $todos,
            'categorias'       => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function datosExcel(): array
    {
        $rows = [
            // GRUPO 1
            [1,'Minimarket Los 3 Hermanos','','minimercado','Mulalo, Calle 27 De Febrero','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7811922,-78.5753411,'recibe','','Sí','Semanal','Toni, Parmalat','Toni','NO COMPRA PRODUCTOS LACTEOS DE LA ASOCIACION DE LACTEOS PROLASE'],
            [1,'Tienda El Volcan','','tienda','Mulalo, Calle La Libertad','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7812718,-78.5760121,'no_recibe','','Sí','Semanal','Toni, Parmalat, Nutri','Toni',''],
            [1,'Panaderia La Tradicion','','panaderia','Mulalo, Calle 4 De Octubre','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7807711,-78.5762864,'recibe','','Sí','Quincenal','Toni, Parmalat','Parmalat',''],
            [1,'Micromercado Danny','','minimercado','Mulalo, Calle 4 De Octubre','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7807734,-78.5763773,'no_recibe','','Sí','Semanal','Toni, Parmalat, Nutri','Toni',''],
            [1,'Viveres Rosita','','tienda','Mulalo, Calle 4 De Octubre','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7807754,-78.576468,'recibe','','Sí','Quincenal','La Castellana','Prodalecc',''],
            [1,'La Gran Esquina','','minimercado','Mulalo, Calle 4 De Octubre','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7807611,-78.5760466,'recibe','','Sí','Semanal','Toni, Parmalat','Parmalat',''],
            [1,'Comercial San Francisco','','minimercado','Mulalo, Calle 4 De Octubre','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7808019,-78.5766688,'no_recibe','','Sí','Quincenal','Parmalat','Parmalat',''],
            [1,'Panaderia Punto Del Sabor','','panaderia','Mulalo, Calle San Francisco','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7811645,-78.5766813,'no_recibe','','Sí','Semanal','Toni, Nutri','Toni',''],
            [1,'Tienda Maria','','tienda','Mulalo, Calle Rumiñahui','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.7812819,-78.5780418,'no_recibe','','No','Quincenal','Toni','Toni','No vende lácteos porque no posee refrigeración'],
            [1,'Viveres Wendy','','tienda','Pilacoto, Av 15 De Agosto','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.8089492,-78.6441243,'no_recibe','','Sí','Semanal','Toni','Toni',''],
            [1,'Tienda Rosaura','','tienda','Pilacoto, Calle Pilacoto','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.8062438,-78.6465538,'recibe','','Sí','Semanal','Parmalat','Parmalat','Vende queso de Lácteos Carmita'],
            [1,'Viveres Junior','','tienda','Pilacoto, Calle Sin Nombre','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.8050249,-78.6449837,'no_recibe','','Sí','Quincenal','Parmalat','Parmalat',''],
            [1,'Viveres Eymi','','tienda','Pilacoto, Calle Sin Nombre','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.8050421,-78.643449,'no_recibe','','Sí','Semanal','Parmalat','Parmalat',''],
            // GRUPO 2
            [2,'Abastos Don Manuel','Maria Toapanta','tienda','Barrio Chilcapamba, via a Lasso km 4','Chilcapamba','Lasso','Latacunga','Cotopaxi',-0.682486,-78.614131,'recibe','921819600','Sí','Diario','Alpina, Chiveria, Toni','Lacteos Tanicuchi','Solicita visita de asesor comercial'],
            [2,'Bodega Las Rosas','Jose Chasi','minimercado','Barrio Centro Lasso, via a Lasso km 1','Centro Lasso','Lasso','Latacunga','Cotopaxi',-0.694897,-78.615018,'recibe','934131647','Sí','Semanal','Floralp, Kiosko, Salinerito','Lacteos Tanicuchi','Solicita muestras de productos artesanales'],
            [2,'Minimarket Nueva Esperanza','Rosa Ugsha','minimercado','Barrio San Ignacio, via a Tanicuchi km 9','San Ignacio','Tanicuchi','Latacunga','Cotopaxi',-0.773111,-78.609108,'no_recibe','949696532','No','Semanal','Kiosko, Alpina, Quesos Cotopaxi','Mercado mayorista Saquisili','Requiere entrega los fines de semana'],
            [2,'Abastos La Fortuna','Luis Quimbita','tienda','Barrio San Sebastian, via a Lasso km 10','San Sebastian','Lasso','Latacunga','Cotopaxi',-0.697944,-78.615336,'recibe','932528809','No','','','','Interesado en credito de proveedores'],
            [2,'Bodega Central','Carmen Tigasi','distribuidor','Barrio Callo, via a Tanicuchi km 9','Callo','Tanicuchi','Latacunga','Cotopaxi',-0.778168,-78.620649,'recibe','996383465','Sí','Semanal','Miraflores, Rey Leche, Alpina, Toni, La Avelina','El Ranchito',''],
            [2,'Distribuidora El Vecino','Segundo Pallo','supermercado','Barrio El Calvario, via a Tanicuchi km 8','El Calvario','Tanicuchi','Latacunga','Cotopaxi',-0.777154,-78.615282,'recibe','901065133','No','Diario','Vita, Toni, Miraflores, Floralp','Comerciante mayorista Latacunga',''],
            [2,'Minimarket San Jose','Blanca Chicaiza','minimercado','Barrio San Ignacio, via a Tanicuchi km 12','San Ignacio','Tanicuchi','Latacunga','Cotopaxi',-0.777625,-78.628859,'no_recibe','980500978','Sí','Diario','Kiosko, Vita','Productor local independiente',''],
            [2,'Tienda Santa Rosa','Manuel Cuchipe','tienda','Barrio San Sebastian, via a Lasso km 10','San Sebastian','Lasso','Latacunga','Cotopaxi',-0.697785,-78.616834,'recibe','911838425','Sí','Semanal','Vita, Nutri, Chiveria, Toni','Rey Leche','Solicita visita de asesor comercial'],
            [2,'Abastos Don Manuel 2','Dolores Yanchapaxi','tienda','Barrio Samilpamba, via a Tanicuchi km 12','Samilpamba','Tanicuchi','Latacunga','Cotopaxi',-0.779695,-78.609812,'no_recibe','978680112','No','Quincenal','Parmalat, Rey Leche, La Avelina','Rey Leche','Solicita visita de asesor comercial'],
            [2,'Comercial San Jose','Angel Tigse','tienda','Barrio El Calvario, via a Tanicuchi km 4','El Calvario','Tanicuchi','Latacunga','Cotopaxi',-0.761458,-78.629232,'no_recibe','937543303','Sí','','','','Solicita muestras de productos artesanales'],
            [2,'Tienda El Ahorro','Mercedes Chango','minimercado','Barrio San Sebastian, via a Lasso km 7','San Sebastian','Lasso','Latacunga','Cotopaxi',-0.685228,-78.619777,'recibe','955698169','Sí','Semanal','Parmalat, Alpina, Vita, Miraflores','Mercado mayorista Saquisili','Requiere entrega los fines de semana'],
            [2,'Comercial El Rosal','Pedro Umajinga','tienda','Barrio San Ignacio, via a Tanicuchi km 11','San Ignacio','Tanicuchi','Latacunga','Cotopaxi',-0.762576,-78.611349,'recibe','957773872','Sí','Diario','Rey Leche, Nutri, Lacteos Tanicuchi','Productor local independiente','Solicita visita de asesor comercial'],
            [2,'Tienda La 32','Elena Toaquiza','distribuidor','Barrio Samilpamba, via a Tanicuchi km 2','Samilpamba','Tanicuchi','Latacunga','Cotopaxi',-0.774043,-78.624059,'recibe','972788957','Sí','Diario','Chiveria, Miraflores, Floralp, Parmalat, Quesos Cotopaxi','Mercado mayorista Saquisili','Interesado en credito de proveedores'],
            [2,'Comercial El Rosal 2','Fabian Guanoluisa','supermercado','Barrio Centro Tanicuchi, via a Tanicuchi km 10','Centro Tanicuchi','Tanicuchi','Latacunga','Cotopaxi',-0.782793,-78.622902,'recibe','967054668','Sí','Quincenal','Parmalat, Kiosko, Nutri','Productor local independiente','Requiere entrega los fines de semana'],
            [2,'Distribuidora El Vecino 2','Rosario Pilatasig','minimercado','Barrio Callo, via a Tanicuchi km 2','Callo','Tanicuchi','Latacunga','Cotopaxi',-0.77448,-78.61484,'recibe','970805310','No','Diario','Alpina, Rey Leche, El Ranchito','Vita','Cliente frecuente de productores de Salcedo'],
            [2,'Abastos La Union','Wilson Iza','minimercado','Barrio Centro Tanicuchi, via a Tanicuchi km 10','Centro Tanicuchi','Tanicuchi','Latacunga','Cotopaxi',-0.763577,-78.627636,'recibe','905865185','No','Quincenal','Miraflores, Chiveria, Floralp, Rey Leche, Salinerito','Rey Leche',''],
            [2,'Distribuidora La Union','Nancy Caiza','minimercado','Barrio Galpon Loma, via a Belisario Quevedo km 9','Galpon Loma','Belisario Quevedo','Latacunga','Cotopaxi',-0.965403,-78.579273,'recibe','908313678','Sí','Semanal','Rey Leche, Chiveria, Parmalat, Alpina, El Ranchito','El Ranchito','Requiere entrega los fines de semana'],
            [2,'Distribuidora El Buen Precio','Marco Vega','tienda','Barrio Galpon Loma, via a Belisario Quevedo km 3','Galpon Loma','Belisario Quevedo','Latacunga','Cotopaxi',-0.967956,-78.591043,'recibe','940824008','Sí','Diario','Floralp, Alpina, Parmalat, La Avelina','Toni','Espacio limitado para nuevos productos'],
            [2,'Bodega La Cosecha','Gladys Chuquitarco','tienda','Barrio La Avelina, via a Lasso km 8','La Avelina','Lasso','Latacunga','Cotopaxi',-0.689797,-78.613931,'recibe','949649909','Sí','Diario','Miraflores, Vita, Quesos Cotopaxi','El Ranchito',''],
            [2,'Tienda San Jose 2','Hector Tipan','tienda','Barrio Once de Noviembre, via a Belisario Quevedo km 5','Once de Noviembre','Belisario Quevedo','Latacunga','Cotopaxi',-0.954765,-78.585199,'recibe','999471746','Sí','Semanal','Chiveria, Toni, Vita, Floralp','Vita','Cliente frecuente de productores de Salcedo'],
            [2,'Distribuidora El Progreso','Susana Molina','minimercado','Barrio Centro Tanicuchi, via a Tanicuchi km 3','Centro Tanicuchi','Tanicuchi','Latacunga','Cotopaxi',-0.766965,-78.624542,'recibe','956746807','Sí','Semanal','Rey Leche, Miraflores, Quesos Cotopaxi','El Ranchito',''],
            [2,'Comercial El Ahorro','Klever Sinchiguano','tienda','Barrio Samilpamba, via a Tanicuchi km 8','Samilpamba','Tanicuchi','Latacunga','Cotopaxi',-0.775843,-78.613095,'no_recibe','948467737','No','','','','Cliente frecuente de productores de Salcedo'],
            [2,'Bodega El Rosal','Norma Ante','minimercado','Barrio El Calvario, via a Tanicuchi km 3','El Calvario','Tanicuchi','Latacunga','Cotopaxi',-0.782204,-78.613243,'recibe','978755886','Sí','Quincenal','Kiosko, Alpina, Nutri, Rey Leche','Nutri','Requiere entrega los fines de semana'],
            [2,'Abastos La Esperanza','Patricio Casa','supermercado','Barrio Rioblanco, via a Lasso km 11','Rioblanco','Lasso','Latacunga','Cotopaxi',-0.688056,-78.613004,'recibe','986578091','Sí','Semanal','Toni, Parmalat','Productor local independiente',''],
            [2,'Distribuidora El Progreso 2','Alexandra Cando','distribuidor','Barrio Centro Lasso, via a Lasso km 8','Centro Lasso','Lasso','Latacunga','Cotopaxi',-0.68543,-78.618544,'no_recibe','948217594','No','Diario','Kiosko, Parmalat, El Ranchito','La Avelina',''],
            [2,'Abastos El Ahorro','Rodrigo Tigse','tienda','Barrio Centro Lasso, via a Lasso km 5','Centro Lasso','Lasso','Latacunga','Cotopaxi',-0.685971,-78.607262,'recibe','970952145','Sí','Diario','Chiveria, Parmalat, Vita, La Avelina','Comerciante mayorista Latacunga','Espacio limitado para nuevos productos'],
            [2,'Tienda La Union','Yolanda Panchi','distribuidor','Barrio San Sebastian, via a Lasso km 6','San Sebastian','Lasso','Latacunga','Cotopaxi',-0.697615,-78.610544,'recibe','993174612','Sí','Quincenal','Parmalat, Miraflores, Kiosko, Lacteos Tanicuchi','El Ranchito','Solicita muestras de productos artesanales'],
            [2,'Minimarket El Ahorro','Edwin Quishpe','minimercado','Barrio El Calvario, via a Tanicuchi km 2','El Calvario','Tanicuchi','Latacunga','Cotopaxi',-0.766199,-78.619374,'recibe','939005329','Sí','Quincenal','Chiveria, Nutri','Vita','Requiere entrega los fines de semana'],
            [2,'Bodega El Rosal 2','Martha Chiluisa','minimercado','Barrio Centro Lasso, via a Lasso km 8','Centro Lasso','Lasso','Latacunga','Cotopaxi',-0.689678,-78.605686,'no_recibe','983908470','Sí','Diario','Nutri, Chiveria, Salinerito','Mercado mayorista Saquisili',''],
            [2,'Comercial Nueva Esperanza','Julio Amores','supermercado','Barrio Guayrapungo, via a Belisario Quevedo km 6','Guayrapungo','Belisario Quevedo','Latacunga','Cotopaxi',-0.949711,-78.588179,'recibe','965452711','Sí','Diario','Vita, Kiosko, Parmalat, Miraflores, Quesos Cotopaxi','Toni','Solicita muestras de productos artesanales'],
            [2,'Bodega El Progreso','Sandra Toapanta','tienda','Barrio Galpon Loma, via a Belisario Quevedo km 1','Galpon Loma','Belisario Quevedo','Latacunga','Cotopaxi',-0.96006,-78.591826,'recibe','924455022','Sí','Diario','Kiosko, Floralp, Parmalat, El Ranchito','Productor local independiente',''],
            [2,'Abastos El Progreso','Franklin Guanotuna','minimercado','Barrio San Sebastian, via a Lasso km 8','San Sebastian','Lasso','Latacunga','Cotopaxi',-0.682972,-78.616975,'no_recibe','984036900','Sí','Diario','Kiosko, Nutri, Miraflores, Rey Leche, Lacteos Tanicuchi','La Avelina','Cliente frecuente de productores de Salcedo'],
            // GRUPO 3
            [3,'Tienda Los Girasoles','Alicia Quinatoa Umajinga','tienda','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-0.998381,-78.621532,'no_recibe','954667767','No','','No aplica','',''],
            [3,'Despensa Salache','Marco Chasi Iza','tienda','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-0.999676,-78.619414,'recibe','32171339','Sí','Semanal','Lacteos Guaytacama, Floralp, KiosKo, Toni','Distribuidor Multisa',''],
            [3,'Tienda San Jose Salache','Norma Iza Ashqui','tienda','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-1.004409,-78.626435,'recibe','975610036','No','Diario','La Finca Ltda., Reyogurt, Parmalat, Floralp, Toni','Pasteurizadora Quito (Vita)','Interesado en promociones por volumen'],
            [3,'Mega Supermercado Guaytacama','Rosa Umajinga Quinatoa','supermercado','La Merced, via principal, Guaytacama','La Merced','Guaytacama','Latacunga','Cotopaxi',-0.821492,-78.642591,'recibe','988074679','No','','No aplica','','Interesado en promociones por volumen'],
            [3,'El Rosal Supermercado','Alicia Pilatasig Quinatoa','supermercado','La Libertad, via principal, Guaytacama','La Libertad','Guaytacama','Latacunga','Cotopaxi',-0.822887,-78.642492,'recibe','32681446','Sí','Quincenal','Chiveria, Lacteos Marbella, Reyogurt, La Vaquita, Rey Leche','Lacteos Guaytacama','Solicita muestras gratis antes de decidir'],
            [3,'Comercial Toacaso S.A.','Teresa Toapanta Tigasi','minimercado','Toacaso Centro, via principal, Toacaso','Toacaso Centro','Toacaso','Latacunga','Cotopaxi',-0.754448,-78.687818,'no_recibe','972770212','No','Quincenal','Rey Leche, Toni, Lacteos Guaytacama, La Holandesa','Falilat','Solicita muestras gratis antes de decidir'],
            [3,'Tienda La Bendicion Salache','Gonzalo Guanoluisa Ugsha','minimercado','Salache Bajo, via principal, Salache','Salache Bajo','Salache','Latacunga','Cotopaxi',-1.000424,-78.615512,'recibe','953924747','Sí','Quincenal','Nutri, La Finca Ltda., Reyqueso, Toni, Chiveria','Distribuidor Multisa','Solicita muestras gratis'],
            [3,'Viveres Charito','Luis Chicaiza Chusin','tienda','San Vicente, via principal, Guaytacama','San Vicente','Guaytacama','Latacunga','Cotopaxi',-0.815204,-78.640606,'recibe','964304543','Sí','Semanal','Lacteos Guaytacama, Floralp, La Vaquita, Reyqueso, Chiveria','Distribuidor local de Latacunga','Espacio de refrigeracion limitado'],
            [3,'Minimarket Familiar Salache','Luz Ugsha Caiza','minimercado','Salache Bajo, via principal, Salache','Salache Bajo','Salache','Latacunga','Cotopaxi',-0.997889,-78.617048,'no_recibe','32578478','Sí','Quincenal','Nutri, Reyogurt, Falilat, KiosKo, La Vaquita','Distribuidora regional Ambato','Solicita muestras gratis'],
            [3,'El Mercado de Rosita','Edwin Chiliquinga Caiza','tienda','El Calvario, via principal, Guaytacama','El Calvario','Guaytacama','Latacunga','Cotopaxi',-0.821182,-78.642282,'recibe','32113346','Sí','Semanal','Falilat, Vita, Reyogurt','Distribuidor local de Latacunga','Le gustaria recibir productos con empaque mas pequeno'],
            [3,'Despensa Don Manuel Salache','Alicia Tigasi Cando','distribuidor','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-1.001352,-78.617563,'recibe','952978729','Sí','Semanal','Reyqueso, KiosKo, Floralp, La Vaquita, Falilat','Asociacion ASOPRAGMA',''],
            [3,'Tienda La Esperanza Salache','Susana Iza Chasi','tienda','Salache San Antonio, via principal, Salache','Salache San Antonio','Salache','Latacunga','Cotopaxi',-1.000199,-78.618892,'no_recibe','32663686','Sí','Quincenal','Rey Leche, Lacteos Marbella, La Holandesa','La Finca Ltda.','Solicita muestras gratis'],
            [3,'Viveres Nino Manuelito','Alicia Chiliquinga Tigasi','tienda','Barrio Central, via principal, Guaytacama','Barrio Central','Guaytacama','Latacunga','Cotopaxi',-0.824593,-78.642571,'no_recibe','981181679','Sí','Diario','La Holandesa, Reyqueso, Rey Leche, Floralp','Lacteos Guaytacama','Solicita muestras gratis'],
            [3,'Comercial El Agricultor Guaytacama','Elena Toapanta Pilatasig','tienda','La Libertad, via principal, Guaytacama','La Libertad','Guaytacama','Latacunga','Cotopaxi',-0.827329,-78.643809,'recibe','989196905','Sí','Quincenal','Chiveria, La Vaquita, Reyqueso, Falilat, Rey Leche','Distribuidora regional Ambato','Solicita muestras gratis'],
            [3,'Comercial Amazonas Toacaso','Teresa Toapanta Cuchipe','tienda','Barrio Central, via principal, Toacaso','Barrio Central','Toacaso','Latacunga','Cotopaxi',-0.758326,-78.687198,'no_recibe','32291773','Sí','Quincenal','Chiveria, Rey Leche, Vita, Reyqueso','Asociacion ASOPRAGMA','Interesado en promociones por volumen'],
            [3,'Tienda de Viveres Conchita','Angel Cando Pilatasig','tienda','Toacaso Centro, via principal, Toacaso','Toacaso Centro','Toacaso','Latacunga','Cotopaxi',-0.799158,-78.668728,'recibe','32870917','Sí','Semanal','Parmalat, Falilat, Reyogurt, Toni, KiosKo','Productores locales de Toacaso','Espacio de refrigeracion limitado'],
            [3,'Tienda Santa Rosa Guaytacama','Carlos Chusin Chiliquinga','minimercado','Barrio Central, via principal, Guaytacama','Barrio Central','Guaytacama','Latacunga','Cotopaxi',-0.824232,-78.645754,'no_recibe','32700646','Sí','Semanal','Lacteos Guaytacama, Reyqueso, Floralp, Chiveria','Distribuidor local de Latacunga','Solicita visita tecnica'],
            [3,'Minimarket El Ahorro Salache','Piedad Yanchapaxi Toapanta','supermercado','Salache San Antonio, via principal, Salache','Salache San Antonio','Salache','Latacunga','Cotopaxi',-0.999821,-78.620629,'recibe','32284076','No','Semanal','La Vaquita, Reyqueso, Lacteos Marbella, Chiveria','La Finca Ltda.','Espacio de refrigeracion limitado'],
            [3,'Sonia Tocte Viveres','Carmen Toapanta Caiza','tienda','Barrio Central, via principal, Toacaso','Barrio Central','Toacaso','Latacunga','Cotopaxi',-0.752864,-78.737747,'recibe','954385123','No','Semanal','KiosKo, Reyogurt, Chiveria, Rey Leche','Productores locales de Toacaso','Le gustaria recibir productos con empaque mas pequeno'],
            [3,'Viveres Dona Carmen','Teresa Cuchipe Guanoluisa','tienda','10 de Agosto, via principal, Guaytacama','10 de Agosto','Guaytacama','Latacunga','Cotopaxi',-0.826014,-78.647799,'recibe','969334391','Sí','Diario','Reyogurt, Nutri, Floralp, Rey Leche','Lacteos Marbella',''],
            [3,'Toacazo Market','Norma Tipan Cuchipe','minimercado','Toacaso Centro, via principal, Toacaso','Toacaso Centro','Toacaso','Latacunga','Cotopaxi',-0.758197,-78.685943,'recibe','32704938','Sí','Semanal','Falilat, Reyqueso, Parmalat, Toni, La Holandesa','Falilat',''],
            [3,'Tienda El Buen Precio Salache','Fabian Chusin Toapanta','distribuidor','Salache Bajo, via principal, Salache','Salache Bajo','Salache','Latacunga','Cotopaxi',-1.00485,-78.620779,'recibe','958792148','No','Semanal','La Vaquita, Lacteos Marbella, KiosKo, Vita, Toni','Asociacion ASOPRAGMA',''],
            [3,'Despensa La Economia','Angel Toapanta Molina','tienda','Salache San Antonio, via principal, Salache','Salache San Antonio','Salache','Latacunga','Cotopaxi',-0.995132,-78.623685,'recibe','32296578','Sí','Semanal','Reyogurt, Rey Leche, Parmalat, La Finca Ltda.','La Finca Ltda.','Solicita catalogo de precios de nuevos proveedores'],
            [3,'Tienda San Francisco Toacaso','Manuel Chasi Guanoluisa','tienda','Barrio Central, via principal, Toacaso','Barrio Central','Toacaso','Latacunga','Cotopaxi',-0.753431,-78.68808,'recibe','32355007','Sí','Diario','Rey Leche, Floralp, Toni, Reyogurt','Falilat','Pide facilidades de pago a credito'],
            [3,'Minimarket La Familia Toacaso','Segundo Guanoluisa Tipan','tienda','Barrio Eugenio Espejo, via principal, Toacaso','Barrio Eugenio Espejo','Toacaso','Latacunga','Cotopaxi',-0.761152,-78.68255,'recibe','992809055','Sí','Semanal','KiosKo, Floralp, Falilat, Reyqueso, Reyogurt','Productores locales de Toacaso',''],
            [3,'Tienda El Progreso Salache','Segundo Cuchipe Umajinga','tienda','Salache Bajo, via principal, Salache','Salache Bajo','Salache','Latacunga','Cotopaxi',-0.995506,-78.616165,'recibe','32651051','No','Semanal','Toni, Floralp, Parmalat, Asociacion ASOPRAGMA, Nutri','Pasteurizadora Quito (Vita)','Espacio de refrigeracion limitado'],
            [3,'Viveres Kevin','Patricio Yanchapaxi Umajinga','tienda','Salache Km 5 1/2, via principal, Salache','Salache Km 5 1/2','Salache','Latacunga','Cotopaxi',-0.995178,-78.618309,'recibe','32014594','Sí','Quincenal','Reyogurt, Rey Leche, Lacteos Guaytacama','Pasteurizadora Quito (Vita)','Espacio de refrigeracion limitado'],
            [3,'Tienda Los Andes','Norma Yanchapaxi Ashqui','tienda','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-0.994853,-78.625372,'no_recibe','32162029','Sí','Semanal','La Holandesa, Reyqueso','Asociacion ASOPRAGMA','Requiere entregas en horario de la manana'],
            [3,'Comercial El Vecino Salache','Edwin Tipan Pilatasig','tienda','Salache Km 5 1/2, via principal, Salache','Salache Km 5 1/2','Salache','Latacunga','Cotopaxi',-1.004893,-78.622998,'recibe','32581128','Sí','Semanal','La Vaquita, La Finca Ltda., Nutri','La Finca Ltda.','Espacio de refrigeracion limitado'],
            [3,'Tienda La Primavera Toacaso','Wilson Molina Quinatoa','minimercado','San Isidro, via principal, Toacaso','San Isidro','Toacaso','Latacunga','Cotopaxi',-0.756676,-78.690341,'no_recibe','32032663','Sí','Semanal','Toni, Parmalat, Lacteos Marbella, Reyqueso','Falilat','Solicita visita tecnica'],
            [3,'Minimarket Rapidito','Angel Ashqui Tigasi','tienda','Via a Sigchos, via principal, Toacaso','Via a Sigchos','Toacaso','Latacunga','Cotopaxi',-0.751443,-78.688034,'recibe','994634633','No','Quincenal','Parmalat, Reyqueso','Productores locales de Toacaso','Le gustaria recibir productos con empaque mas pequeno'],
            [3,'Despensa El Trebol','Teresa Chiliquinga Tigasi','supermercado','Via a Sigchos, via principal, Toacaso','Via a Sigchos','Toacaso','Latacunga','Cotopaxi',-0.760857,-78.686911,'recibe','32385415','Sí','Semanal','Nutri, Floralp','Distribuidora regional Ambato','Solicita visita tecnica'],
            [3,'Tienda Nueva Esperanza Salache','Piedad Toapanta Tipan','distribuidor','Sector CEYPSA, via principal, Salache','Sector CEYPSA','Salache','Latacunga','Cotopaxi',-1.000462,-78.622909,'recibe','953530518','Sí','Semanal','Asociacion ASOPRAGMA, La Vaquita, Rey Leche, Floralp','Distribuidor Multisa','Le gustaria recibir productos con empaque mas pequeno'],
            [3,'Viveres Don Segundo','Elena Yanchapaxi Chiliquinga','minimercado','El Calvario, via principal, Toacaso','El Calvario','Toacaso','Latacunga','Cotopaxi',-0.762007,-78.684036,'recibe','32700499','Sí','Quincenal','KiosKo, Floralp, Lacteos Marbella, Reyogurt, Reyqueso','Toni',''],
            [3,'Tienda La Cosecha Guaytacama','Blanca Umajinga Cuchipe','tienda','El Calvario, via principal, Guaytacama','El Calvario','Guaytacama','Latacunga','Cotopaxi',-0.827183,-78.641101,'no_recibe','959827705','Sí','Diario','Falilat, Toni, Nutri, Parmalat, La Holandesa','Lacteos Marbella','Solicita muestras gratis'],
            [3,'Minimarket 24 Horas','Pedro Cuchipe Guanoluisa','tienda','El Calvario, via principal, Toacaso','El Calvario','Toacaso','Latacunga','Cotopaxi',-0.756703,-78.683282,'recibe','954152273','Sí','Semanal','La Holandesa, Falilat, La Vaquita, Reyogurt','Falilat',''],
            [3,'Tienda El Chagra','Norma Cando Ugsha','minimercado','El Calvario, via principal, Toacaso','El Calvario','Toacaso','Latacunga','Cotopaxi',-0.755107,-78.691303,'no_recibe','32088834','Sí','Diario','Parmalat, La Finca Ltda., Floralp','Falilat',''],
            [3,'Comercial San Pedro','Rodrigo Iza Chusin','distribuidor','Centro de Guaytacama, via principal, Guaytacama','Centro de Guaytacama','Guaytacama','Latacunga','Cotopaxi',-0.826249,-78.64653,'no_recibe','995389947','No','Semanal','Parmalat, Reyogurt','Lacteos Guaytacama','Pide facilidades de pago a credito'],
            [3,'Tienda Mi Tiendita','Rosa Iza Caiza','minimercado','Via a Salache, via principal, Salache','Via a Salache','Salache','Latacunga','Cotopaxi',-0.996963,-78.620809,'recibe','32154506','No','Semanal','Parmalat, Lacteos Marbella, La Vaquita, Rey Leche','Asociacion ASOPRAGMA','Espacio de refrigeracion limitado'],
            [3,'Despensa La Ganga','Carmen Yanchapaxi Molina','tienda','San Isidro, via principal, Toacaso','San Isidro','Toacaso','Latacunga','Cotopaxi',-0.75123,-78.692772,'recibe','969334169','Sí','Semanal','Rey Leche, La Vaquita, Vita, Reyqueso','Falilat','Interesado en linea de quesos madurados'],
            [3,'Tienda El Rosal Guaytacama','Luz Caiza Chicaiza','distribuidor','San Antonio, via principal, Guaytacama','San Antonio','Guaytacama','Latacunga','Cotopaxi',-0.81762,-78.648178,'recibe','32361551','Sí','Diario','Reyogurt, Parmalat, Nutri, La Holandesa','Lacteos Guaytacama','Interesado en linea de quesos madurados'],
            [3,'Viveres La Paz','Susana Toapanta Toapanta','tienda','Via a Sigchos, via principal, Toacaso','Via a Sigchos','Toacaso','Latacunga','Cotopaxi',-0.753061,-78.690707,'recibe','974697348','Sí','Semanal','Parmalat, La Vaquita, Floralp','Falilat','Solicita visita tecnica'],
            [3,'Tienda San Jose Guaytacama','Elena Caiza Toapanta','tienda','San Vicente, via principal, Guaytacama','San Vicente','Guaytacama','Latacunga','Cotopaxi',-0.827592,-78.63826,'recibe','32425942','Sí','Quincenal','Parmalat, Lacteos Guaytacama, Reyogurt','Distribuidora regional Ambato','Solicita catalogo de precios'],
            [3,'Tienda La Bendicion La Merced','Carlos Caiza Chusin','tienda','La Merced, via principal, Guaytacama','La Merced','Guaytacama','Latacunga','Cotopaxi',-0.822111,-78.644056,'no_recibe','32299449','Sí','Semanal','Chiveria, La Holandesa, Toni, Parmalat','Lacteos Guaytacama',''],
            [3,'Minimarket Familiar La Libertad','Piedad Guanoluisa Molina','tienda','La Libertad, via principal, Guaytacama','La Libertad','Guaytacama','Latacunga','Cotopaxi',-0.820757,-78.647665,'no_recibe','32863333','Sí','Semanal','La Vaquita, KiosKo','Distribuidor local de Latacunga','Pide facilidades de pago a credito'],
            [3,'Despensa Don Manuel El Calvario','Norma Iza Tipan','tienda','El Calvario, via principal, Guaytacama','El Calvario','Guaytacama','Latacunga','Cotopaxi',-0.8206,-78.641327,'recibe','991908088','Sí','Quincenal','Chiveria, Floralp, Lacteos Marbella, Rey Leche, KiosKo','Lacteos Guaytacama',''],
            [3,'Tienda La Esperanza Salache 2','Alicia Tigasi Toapanta','supermercado','Salache Bajo, via principal, Salache','Salache Bajo','Salache','Latacunga','Cotopaxi',-0.993796,-78.625431,'recibe','967949720','Sí','Diario','Chiveria, Rey Leche, Parmalat','Asociacion ASOPRAGMA','Espacio de refrigeracion limitado'],
            [3,'Comercial El Agricultor La Libertad','Carmen Caiza Umajinga','tienda','La Libertad, via principal, Guaytacama','La Libertad','Guaytacama','Latacunga','Cotopaxi',-0.824671,-78.648602,'recibe','976882577','Sí','Semanal','Chiveria, Lacteos Marbella, La Vaquita, Floralp','Toni',''],
            [3,'Tienda Santa Rosa La Libertad','Gladys Toapanta Iza','tienda','La Libertad, via principal, Guaytacama','La Libertad','Guaytacama','Latacunga','Cotopaxi',-0.826313,-78.637982,'recibe','32775232','Sí','Semanal','Parmalat, Reyqueso, Asociacion ASOPRAGMA','Distribuidora regional Ambato','Solicita visita tecnica'],
            [3,'Minimarket El Ahorro Toacaso','Wilson Tigasi Pilatasig','tienda','Toacaso Centro, via principal, Toacaso','Toacaso Centro','Toacaso','Latacunga','Cotopaxi',-0.75224,-78.690094,'no_recibe','978103870','Sí','Semanal','Rey Leche, Falilat, Parmalat, La Holandesa, La Vaquita','Pasteurizadora Quito (Vita)','Solicita visita tecnica'],
            // GRUPO 4
            [4,'VIVERES D&E','Karina Iza','minimercado','TANICUCHI CENTRO CALLE ELOY ALFARO','Tanicuchi Centro','Tanicuchi','Latacunga','Cotopaxi',-0.780351,-78.638054,'recibe','0982000121','Sí','Semanal','Rey leche, Vita, Produleche','Vita',''],
            [4,'Panaderia Jose Caiza','Jose Caiza','panaderia','Leonidas Plaza, Tanicuchi','Santa Clara','Tanicuchi','Latacunga','Cotopaxi',-0.776753,-78.63813,'recibe','0989904232','Sí','Diario','El Ranchito','Ranchito',''],
            [4,'Viveres Paolita','Jesica Almachi','tienda','SAN ANDRES, Tanicuchi','San Andres','Tanicuchi','Latacunga','Cotopaxi',-0.776528,-78.638092,'recibe','0995484991','Sí','Diario','Ranchito','Ranchito',''],
            [4,'Mini Market la Economia','Maria Toapanta','minimercado','Sanpedro 15 de agosto, Tanicuchi','San Pedro San Andres','Tanicuchi','Latacunga','Cotopaxi',-0.800219,-78.643036,'recibe','0983510895','Sí','Semanal','Ranchito, Maribella, Toni, Lacteos Leo','Ranchito, Toni','Que visiten semanal y que cambien el producto en caducidad'],
            [4,'Viveres Gaby','Martha Patricia Toapanta Yugcha','tienda','Barrio San Pedro de Tanicuchi, Calle Amazonas','San Pedro','Tanicuchi','Latacunga','Cotopaxi',-0.800219,-78.643036,'recibe','0996091386','Sí','Semanal','Toni y Tanilact','Tanilact','Calidad y Precio'],
            [4,'Viveres Glorita','Gloria Vilca','tienda','Calle Amazonas y Continental, Guaytacama','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.809542,-78.641891,'recibe','0987372737','Sí','Semanal','Lacteos La Victoria','Lacteos La Victoria',''],
            [4,'Tradicional Ambatena','Maria Flores','tienda','Belisario Quevedo Centro','Barrio Centro Belisario Quevedo','Belisario Quevedo','Latacunga','Cotopaxi',-0.986088,-78.582108,'recibe','','Sí','Semanal','Lacteos La Victoria','Queso',''],
            [4,'Justo AKI','Maria Trinidad Chicaiza','tienda','Ciudadela Leonidas Plaza, Tanicuchi','Leonidas Plaza','Tanicuchi','Latacunga','Cotopaxi',-0.7776,-78.638199,'recibe','','Sí','Semanal','Regia, Bonella, Toni, Rey Leche, Andina','Rey Leche','Necesidad de proveedores con menos costos'],
            [4,'Viveres Marianita','Lisbeth Quinatoa','supermercado','Calle Santa Clara, Tanicuchi','Centro de Tanicuchi','Tanicuchi','Latacunga','Cotopaxi',-0.777116,-78.638153,'no_recibe','993922729','Sí','Diario','Rey leche, Vita, Parmalat, Tony','Rey Leche, Tony',''],
            [4,'Viveres Karlita','Mariana Yugcha','tienda','Calle Leonidas Plaza, Tanicuchi','Santa Clara','Tanicuchi','Latacunga','Cotopaxi',-0.776528,-78.638092,'no_recibe','999075985','Sí','Semanal','Toni, Vitaleche, Ranchito, Rey Leche','Rey Leche, Ranchito','Los productos locales no son tan aceptados por los clientes'],
            [4,'Viveres Brayansito','Efrain Viracocha','tienda','Calle Amazonas Barrio Pilacoto, Guaytacama','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.80891,-78.641876,'no_recibe','','Sí','Mensual','Ranchito, La Victoria','Ranchito','Los productos artesanales son aceptables pero con codigo de barras'],
            [4,'Panaderia Alpina','Rosa Montachana','panaderia','A dos cuadras del parque, Belisario Quevedo','Sector Belisario Quevedo','Belisario Quevedo','Latacunga','Cotopaxi',-0.986182,-78.582756,'recibe','0998598254','Sí','Semanal','Lacteos Javilac','Productores lecheros y queseras artesanales de Belisario Quevedo',''],
            [4,'Viveres La Bendicion Tanicuchi','Jose Toaquiza','tienda','Barrio Tanicuchi Centro, Tanicuchi','Tanicuchi Centro','Tanicuchi','Latacunga','Cotopaxi',-0.772947,-78.63081,'recibe','0982462819','Sí','Diario','Alpina, Rey Leche','Productor local independiente',''],
            [4,'Tienda Santa Marianita Tanicuchi','Yolanda Molina','minimercado','Barrio San Pedro, Tanicuchi','San Pedro','Tanicuchi','Latacunga','Cotopaxi',-0.776228,-78.617385,'recibe','0986252760','Sí','Semanal','Quesos Cotopaxi, La Avelina, Parmalat','Rey Leche','Interesado en credito de proveedores'],
            [4,'Viveres El Condor','Patricia Tipan','supermercado','Barrio El Calvario, Tanicuchi','El Calvario','Tanicuchi','Latacunga','Cotopaxi',-0.772338,-78.620151,'recibe','0923667127','No','Semanal','Toni, Rey Leche, Salinerito','Vita','Solicita visita de asesor comercial'],
            [4,'Comercial La Bendicion Tanicuchi','Alexandra Amores','distribuidor','Barrio Samilpamba, Tanicuchi','Samilpamba','Tanicuchi','Latacunga','Cotopaxi',-0.783988,-78.611379,'recibe','0978666617','Sí','Diario','Parmalat, Toni','Productor local independiente','Solicita visita de asesor comercial'],
            [4,'Abastos El Buen Precio Tanicuchi','Blanca Pilatasig','tienda','Barrio San Andres, Tanicuchi','San Andres','Tanicuchi','Latacunga','Cotopaxi',-0.775286,-78.608532,'recibe','0941215472','Sí','Diario','Tanilact, Floralp, Vita, Parmalat','Rey Leche','Requiere entrega los fines de semana'],
            [4,'Viveres Santa Rosa Tanicuchi','Jose Casa','tienda','Barrio Samilpamba, Tanicuchi','Samilpamba','Tanicuchi','Latacunga','Cotopaxi',-0.766709,-78.619558,'recibe','0938885393','No','Semanal','Rey Leche','Rey Leche','Solicita visita de asesor comercial'],
            [4,'Minimarket Santa Rosa Tanicuchi','Angel Umajinga','minimercado','Barrio Tanicuchi Centro, Tanicuchi','Tanicuchi Centro','Tanicuchi','Latacunga','Cotopaxi',-0.767594,-78.614645,'recibe','0913137353','No','Diario','Parmalat, Toni','Ranchito','Espacio limitado para nuevos productos'],
            [4,'Comercial El Buen Precio Tanicuchi','Patricia Quishpe','supermercado','Barrio Santa Clara, Tanicuchi','Santa Clara','Tanicuchi','Latacunga','Cotopaxi',-0.771259,-78.617322,'recibe','0952882001','No','Diario','Miraflores, Vita, La Avelina','Rey Leche','Espacio limitado para nuevos productos'],
            [4,'Distribuidora San Isidro Tanicuchi','Blanca Chiluisa','distribuidor','Barrio San Pedro, Tanicuchi','San Pedro','Tanicuchi','Latacunga','Cotopaxi',-0.783356,-78.619747,'recibe','0929022279','No','Semanal','Quesos Cotopaxi, Lacteos La Victoria, Toni','Toni','Interesado en credito de proveedores'],
            [4,'Comercial San Vicente Guaytacama','Klever Caiza','supermercado','Barrio San Agustin de Guaytacama, Guaytacama','San Agustin de Guaytacama','Guaytacama','Latacunga','Cotopaxi',-0.825204,-78.634023,'recibe','0984837261','Sí','Quincenal','Floralp, Rey Leche','Lacteos Tanicuchi (Tanilac)',''],
            [4,'Minimarket La Primavera Guaytacama','Manuel Guanoluisa','supermercado','Barrio Pilacoto, Guaytacama','Pilacoto','Guaytacama','Latacunga','Cotopaxi',-0.827709,-78.643289,'no_recibe','0915058770','No','Semanal','Salinerito, Toni, Tanilact, Kiosko','Rey Leche','Requiere entrega los fines de semana'],
            [4,'Distribuidora La Merced Guaytacama','Maria Quishpe','tienda','Barrio Guaytacama Centro, Guaytacama','Guaytacama Centro','Guaytacama','Latacunga','Cotopaxi',-0.819261,-78.633725,'recibe','0962889751','No','Semanal','Toni','Rey Leche','Espacio limitado para nuevos productos'],
            [4,'Minimarket La Victoria Guaytacama','Norma Toapanta','tienda','Barrio El Tejar, Guaytacama','El Tejar','Guaytacama','Latacunga','Cotopaxi',-0.832558,-78.645912,'recibe','0934782450','Sí','Semanal','Ranchito, Nutri, Rey Leche','Lacteos Tanicuchi (Tanilac)',''],
            [4,'Bodega La Merced Guaytacama','Klever Vega','supermercado','Barrio Guaytacama Centro, Guaytacama','Guaytacama Centro','Guaytacama','Latacunga','Cotopaxi',-0.817312,-78.653058,'no_recibe','0962016849','No','Diario','Lacteos La Victoria, Floralp, Quesos Cotopaxi, Alpina','Toni',''],
            [4,'Abastos San Francisco Guaytacama','Segundo Pallo','minimercado','Barrio Cuicuno, Guaytacama','Cuicuno','Guaytacama','Latacunga','Cotopaxi',-0.817791,-78.649947,'recibe','0944319829','Sí','Semanal','Vita, Kiosko','Vita',''],
            [4,'Bodega San Francisco Guaytacama','Jose Tigse','minimercado','Barrio El Tejar, Guaytacama','El Tejar','Guaytacama','Latacunga','Cotopaxi',-0.828743,-78.650882,'recibe','0983740718','Sí','Quincenal','Tanilact, Kiosko, Floralp, La Avelina','Mercado mayorista Saquisili',''],
            [4,'Bodega Santa Marianita Belisario','Angel Yanchapaxi','supermercado','Barrio Belisario Quevedo Centro, Belisario Quevedo','Belisario Quevedo Centro','Belisario Quevedo','Latacunga','Cotopaxi',-0.955422,-78.585341,'no_recibe','0937484777','Sí','Quincenal','Toni, Nutri, Miraflores','Productor local independiente','Interesado en credito de proveedores'],
            [4,'Viveres San Francisco Belisario','Angel Toapanta','tienda','Barrio Once de Noviembre, Belisario Quevedo','Once de Noviembre','Belisario Quevedo','Latacunga','Cotopaxi',-0.945973,-78.587542,'recibe','0977642656','Sí','Semanal','Toni, Chiveria','Toni',''],
            [4,'Viveres San Vicente Belisario','Marco Chango','tienda','Barrio Guayrapungo, Belisario Quevedo','Guayrapungo','Belisario Quevedo','Latacunga','Cotopaxi',-0.951113,-78.581761,'no_recibe','0946853560','No','Quincenal','Floralp, Parmalat, Vita','Vita',''],
            [4,'Viveres La Victoria Belisario','Carmen Cando','tienda','Barrio Guayrapungo, Belisario Quevedo','Guayrapungo','Belisario Quevedo','Latacunga','Cotopaxi',-0.962984,-78.59312,'recibe','0978375762','Sí','Semanal','Tanilact, Toni','Mercado mayorista Saquisili',''],
            [4,'Abastos San Isidro Belisario','Gladys Vega','tienda','Barrio Belisario Quevedo Centro, Belisario Quevedo','Belisario Quevedo Centro','Belisario Quevedo','Latacunga','Cotopaxi',-0.960495,-78.591037,'recibe','0964020679','No','Diario','Quesos Cotopaxi, Salinerito, Floralp','Productor local independiente','Espacio limitado para nuevos productos'],
            [4,'Tienda San Pedro Belisario','Klever Amores','distribuidor','Barrio Guayrapungo, Belisario Quevedo','Guayrapungo','Belisario Quevedo','Latacunga','Cotopaxi',-0.943675,-78.594387,'no_recibe','0900239042','No','Semanal','Toni, Rey Leche','Vita',''],
            [4,'Bodega La Familia Belisario','Maria Quimbita','distribuidor','Barrio Belisario Quevedo Centro, Belisario Quevedo','Belisario Quevedo Centro','Belisario Quevedo','Latacunga','Cotopaxi',-0.962341,-78.575764,'recibe','0914365370','Sí','Diario','Rey Leche, Lacteos La Victoria, Floralp, Tanilact','Rey Leche','Interesado en credito de proveedores'],
            // GRUPO 5
            [5,'Abastos Don Manuel Mulalo','Maria Toapanta','tienda','Barrio San Agustin de Callo, via a Mulalo km 4','San Agustin de Callo','Mulalo','Latacunga','Cotopaxi',-0.842666,-78.625211,'recibe','921819600','Sí','Diario','Alpina, Chiveria, Toni','Lacteos Tanicuchi','Solicita visita de asesor comercial'],
            [5,'Bodega Las Rosas Mulalo','Jose Chasi','minimercado','Barrio Mulalo Centro, via a Mulalo km 1','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.855077,-78.626098,'recibe','934131647','Sí','Semanal','Floralp, Kiosko, Salinerito','Lacteos Tanicuchi','Solicita muestras de productos artesanales'],
            [5,'Minimarket Nueva Esperanza Lasso','Rosa Ugsha','minimercado','Barrio Illuchi, via a Lasso km 9','Illuchi','Lasso','Latacunga','Cotopaxi',-0.716751,-78.606878,'no_recibe','949696532','No','Semanal','Kiosko, Alpina, Quesos Cotopaxi','Mercado mayorista Saquisili','Requiere entrega los fines de semana'],
            [5,'Abastos La Fortuna Verde Cruz','Luis Quimbita','tienda','Barrio Verde Cruz, via a Mulalo km 10','Verde Cruz','Mulalo','Latacunga','Cotopaxi',-0.858124,-78.626416,'recibe','932528809','No','','','','Interesado en credito de proveedores'],
            [5,'Bodega Central Lasso','Carmen Tigasi','distribuidor','Barrio Coba Loma, via a Lasso km 9','Coba Loma','Lasso','Latacunga','Cotopaxi',-0.721808,-78.618419,'recibe','996383465','Sí','Semanal','Miraflores, Rey Leche, Alpina, Toni, La Avelina','El Ranchito',''],
            [5,'Distribuidora El Vecino El Tejar','Segundo Pallo','supermercado','Barrio El Tejar, via a Lasso km 8','El Tejar','Lasso','Latacunga','Cotopaxi',-0.720794,-78.613052,'recibe','901065133','No','Diario','Vita, Toni, Miraflores, Floralp','Comerciante mayorista Latacunga',''],
            [5,'Minimarket San Jose Lasso','Blanca Chicaiza','minimercado','Barrio Illuchi, via a Lasso km 12','Illuchi','Lasso','Latacunga','Cotopaxi',-0.721265,-78.626629,'no_recibe','980500978','Sí','Diario','Kiosko, Vita','Productor local independiente',''],
            [5,'Tienda Santa Rosa Verde Cruz','Manuel Cuchipe','tienda','Barrio Verde Cruz, via a Mulalo km 10','Verde Cruz','Mulalo','Latacunga','Cotopaxi',-0.857965,-78.627914,'recibe','911838425','Sí','Semanal','Vita, Nutri, Chiveria, Toni','Rey Leche','Solicita visita de asesor comercial'],
            [5,'Abastos Don Manuel Santa Ana','Dolores Yanchapaxi','tienda','Barrio Santa Ana, via a Lasso km 12','Santa Ana','Lasso','Latacunga','Cotopaxi',-0.723335,-78.607582,'no_recibe','978680112','No','Quincenal','Parmalat, Rey Leche, La Avelina','Rey Leche','Solicita visita de asesor comercial'],
            [5,'Comercial San Jose El Tejar','Angel Tigse','tienda','Barrio El Tejar, via a Lasso km 4','El Tejar','Lasso','Latacunga','Cotopaxi',-0.705098,-78.627002,'no_recibe','937543303','Sí','','','','Solicita muestras de productos artesanales'],
            [5,'Tienda El Ahorro Verde Cruz','Mercedes Chango','minimercado','Barrio Verde Cruz, via a Mulalo km 7','Verde Cruz','Mulalo','Latacunga','Cotopaxi',-0.845408,-78.630857,'recibe','955698169','Sí','Semanal','Parmalat, Alpina, Vita, Miraflores','Mercado mayorista Saquisili','Requiere entrega los fines de semana'],
            [5,'Comercial El Rosal Illuchi','Pedro Umajinga','tienda','Barrio Illuchi, via a Lasso km 11','Illuchi','Lasso','Latacunga','Cotopaxi',-0.706216,-78.609119,'recibe','957773872','Sí','Diario','Rey Leche, Nutri, Lacteos Tanicuchi','Productor local independiente','Solicita visita de asesor comercial'],
            [5,'Tienda La 32 Santa Ana','Elena Toaquiza','distribuidor','Barrio Santa Ana, via a Lasso km 2','Santa Ana','Lasso','Latacunga','Cotopaxi',-0.717683,-78.621829,'recibe','972788957','Sí','Diario','Chiveria, Miraflores, Floralp, Parmalat, Quesos Cotopaxi','Mercado mayorista Saquisili','Interesado en credito de proveedores'],
            [5,'Comercial El Rosal Guanailin','Fabian Guanoluisa','supermercado','Barrio Guanailin, via a Lasso km 10','Guanailin','Lasso','Latacunga','Cotopaxi',-0.726433,-78.620672,'recibe','967054668','Sí','Quincenal','Parmalat, Kiosko, Nutri','Productor local independiente','Requiere entrega los fines de semana'],
            [5,'Distribuidora El Vecino Coba Loma','Rosario Pilatasig','minimercado','Barrio Coba Loma, via a Lasso km 2','Coba Loma','Lasso','Latacunga','Cotopaxi',-0.71812,-78.61261,'recibe','970805310','No','Diario','Alpina, Rey Leche, El Ranchito','Vita','Cliente frecuente de productores de Salcedo'],
            [5,'Abastos La Union Guanailin','Wilson Iza','minimercado','Barrio Guanailin, via a Lasso km 10','Guanailin','Lasso','Latacunga','Cotopaxi',-0.707217,-78.625406,'recibe','905865185','No','Quincenal','Miraflores, Chiveria, Floralp, Rey Leche, Salinerito','Rey Leche',''],
            [5,'Distribuidora La Union Pastocalle','Nancy Caiza','minimercado','Barrio Poalo, via a Pastocalle km 9','Poalo','Pastocalle','Latacunga','Cotopaxi',-0.655823,-78.642173,'recibe','908313678','Sí','Semanal','Rey Leche, Chiveria, Parmalat, Alpina, El Ranchito','El Ranchito','Requiere entrega los fines de semana'],
            [5,'Distribuidora El Buen Precio Poalo','Marco Vega','tienda','Barrio Poalo, via a Pastocalle km 3','Poalo','Pastocalle','Latacunga','Cotopaxi',-0.658376,-78.653943,'recibe','940824008','Sí','Diario','Floralp, Alpina, Parmalat, La Avelina','Toni','Espacio limitado para nuevos productos'],
            [5,'Bodega La Cosecha Chan','Gladys Chuquitarco','tienda','Barrio Chan, via a Mulalo km 8','Chan','Mulalo','Latacunga','Cotopaxi',-0.849977,-78.625011,'recibe','949649909','Sí','Diario','Miraflores, Vita, Quesos Cotopaxi','El Ranchito',''],
            [5,'Tienda San Jose Yanayacu','Hector Tipan','tienda','Barrio Yanayacu, via a Pastocalle km 5','Yanayacu','Pastocalle','Latacunga','Cotopaxi',-0.645185,-78.648099,'recibe','999471746','Sí','Semanal','Chiveria, Toni, Vita, Floralp','Vita','Cliente frecuente de productores de Salcedo'],
            [5,'Distribuidora El Progreso Guanailin','Susana Molina','minimercado','Barrio Guanailin, via a Lasso km 3','Guanailin','Lasso','Latacunga','Cotopaxi',-0.710605,-78.622312,'recibe','956746807','Sí','Semanal','Rey Leche, Miraflores, Quesos Cotopaxi','El Ranchito',''],
            [5,'Comercial El Ahorro Santa Ana','Klever Sinchiguano','tienda','Barrio Santa Ana, via a Lasso km 8','Santa Ana','Lasso','Latacunga','Cotopaxi',-0.719483,-78.610865,'no_recibe','948467737','No','','','','Cliente frecuente de productores de Salcedo'],
            [5,'Bodega El Rosal El Tejar Lasso','Norma Ante','minimercado','Barrio El Tejar, via a Lasso km 3','El Tejar','Lasso','Latacunga','Cotopaxi',-0.725844,-78.611013,'recibe','978755886','Sí','Quincenal','Kiosko, Alpina, Nutri, Rey Leche','Nutri','Requiere entrega los fines de semana'],
            [5,'Abastos La Esperanza Chasqui','Patricio Casa','supermercado','Barrio Chasqui, via a Mulalo km 11','Chasqui','Mulalo','Latacunga','Cotopaxi',-0.848236,-78.624084,'recibe','986578091','Sí','Semanal','Toni, Parmalat','Productor local independiente',''],
            [5,'Distribuidora El Progreso Mulalo','Alexandra Cando','distribuidor','Barrio Mulalo Centro, via a Mulalo km 8','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.84561,-78.629624,'no_recibe','948217594','No','Diario','Kiosko, Parmalat, El Ranchito','La Avelina',''],
            [5,'Abastos El Ahorro Mulalo','Rodrigo Tigse','tienda','Barrio Mulalo Centro, via a Mulalo km 5','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.846151,-78.618342,'recibe','970952145','Sí','Diario','Chiveria, Parmalat, Vita, La Avelina','Comerciante mayorista Latacunga','Espacio limitado para nuevos productos'],
            [5,'Tienda La Union Verde Cruz','Yolanda Panchi','distribuidor','Barrio Verde Cruz, via a Mulalo km 6','Verde Cruz','Mulalo','Latacunga','Cotopaxi',-0.857795,-78.621624,'recibe','993174612','Sí','Quincenal','Parmalat, Miraflores, Kiosko, Lacteos Tanicuchi','El Ranchito','Solicita muestras de productos artesanales'],
            [5,'Minimarket El Ahorro El Tejar 2','Edwin Quishpe','minimercado','Barrio El Tejar, via a Lasso km 2','El Tejar','Lasso','Latacunga','Cotopaxi',-0.709839,-78.617144,'recibe','939005329','Sí','Quincenal','Chiveria, Nutri','Vita','Requiere entrega los fines de semana'],
            [5,'Bodega El Rosal Mulalo','Martha Chiluisa','minimercado','Barrio Mulalo Centro, via a Mulalo km 8','Mulalo Centro','Mulalo','Latacunga','Cotopaxi',-0.849858,-78.616766,'no_recibe','983908470','Sí','Diario','Nutri, Chiveria, Salinerito','Mercado mayorista Saquisili',''],
            [5,'Comercial Nueva Esperanza Potrerillos','Julio Amores','supermercado','Barrio Potrerillos, via a Pastocalle km 6','Potrerillos','Pastocalle','Latacunga','Cotopaxi',-0.640131,-78.651079,'recibe','965452711','Sí','Diario','Vita, Kiosko, Parmalat, Miraflores, Quesos Cotopaxi','Toni','Solicita muestras de productos artesanales'],
            [5,'Bodega El Progreso Poalo','Sandra Toapanta','tienda','Barrio Poalo, via a Pastocalle km 1','Poalo','Pastocalle','Latacunga','Cotopaxi',-0.65048,-78.654726,'recibe','924455022','Sí','Diario','Kiosko, Floralp, Parmalat, El Ranchito','Productor local independiente',''],
            [5,'Abastos El Progreso Verde Cruz','Franklin Guanotuna','minimercado','Barrio Verde Cruz, via a Mulalo km 8','Verde Cruz','Mulalo','Latacunga','Cotopaxi',-0.843152,-78.628055,'no_recibe','984036900','Sí','Diario','Kiosko, Nutri, Miraflores, Rey Leche, Lacteos Tanicuchi','La Avelina','Cliente frecuente de productores de Salcedo'],
        ];

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'            => null,
                'slug'          => null,
                'nombre'        => $r[1],
                'tipo'          => $r[2] === '' ? 'tienda' : $r[2],
                'direccion'     => $r[4],
                'sector'        => $r[5],
                'parroquia'     => $r[6],
                'canton'        => $r[7],
                'provincia'     => $r[8],
                'lat'           => (float) $r[9],
                'lng'           => (float) $r[10],
                'recepcion'     => $r[11],
                'telefono'      => $r[12],
                'refrigeracion' => $r[13],
                'frecuencia'    => $r[14],
                'marcas'        => $r[15],
                'proveedor'     => $r[16],
                'notas'         => $r[17],
                'propietario'   => $r[3],
                'grupo'         => $r[0],
                'fuente'        => 'excel',
            ];
        }
        return $result;
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
