<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class LatacungaDataSeeder extends Seeder
{
    public function run(): void
    {
        $merchantRole = Role::findByName('merchant');
        $entrepreneurRole = Role::findByName('entrepreneur');

        // nombre, tipo, latitud, longitud, parroquia, recepción, verificado, necesidades
        $establishments = [
            ['MINISUPER EL CENTRO', 'tienda', -0.9339, -78.6145, 'La Matriz', 'recibe', true, ['Yogurt natural', 'Pan de yuca']],
            ['SUPERMERCADO SAN FRANCISCO', 'supermercado', -0.9351, -78.6162, 'La Matriz', 'por_confirmar', false, ['Productos lácteos', 'Frutas y verduras']],
            ['MINIMERCADO LOS ANDES', 'minimercado', -0.9402, -78.6221, 'San Buena Ventura', 'recibe', true, ['Bebidas naturales', 'Snacks']],
            ['PANADERIA DON JUAN', 'tienda', -0.9324, -78.6118, 'El Rosal', 'recibe', false, ['Harina', 'Azúcar']],
            ['TIENDA EL PARAISO', 'tienda', -0.9278, -78.6189, 'San Sebastian', 'no_recibe', false, []],
            ['MINISUPER LA ESPERANZA', 'tienda', -0.9443, -78.6097, 'Eloy Alfaro', 'por_confirmar', false, ['Aceite', 'Arroz']],
            ['DISTRIBUIDORA ECUADOR', 'minimercado', -0.9491, -78.6254, 'Ignacio Flores', 'recibe', true, ['Lácteos', 'Bebidas']],
            ['SUPERMERCADO EL SOL', 'supermercado', -0.9305, -78.6092, 'La Matriz', 'recibe', true, ['Productos orgánicos', 'Mermeladas']],
        ];

        foreach ($establishments as $index => [$name, $type, $latitude, $longitude, $parish, $reception, $verified, $needs]) {
            $merchant = \App\Models\User::query()->firstOrCreate(
                ['email' => 'merchant'.($index + 1).'@latacunga.test'],
                [
                    'name' => 'Responsable '.$name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $merchantRole->id,
                    'model_type' => \App\Models\User::class,
                    'model_id' => $merchant->id,
                ],
            );

            $categoryLacteos = DB::table('categories')->where('slug', 'lacteos')->first();

            DB::table('establishments')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'merchant_id' => $merchant->id,
                    'name' => $name,
                    'type' => $type,
                    'address' => 'Centro comercial, sector '.$parish,
                    'province' => 'Cotopaxi',
                    'canton' => 'Latacunga',
                    'parish' => $parish,
                    'sector' => $parish,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'reception_status' => $reception,
                    'verification_status' => $verified ? 'verificado' : 'pendiente',
                    'verified_at' => $verified ? now() : null,
                    'last_verified_at' => $verified ? now() : null,
                    'commercial_contact_name' => 'Área de Compras',
                    'commercial_phone' => '09'.str_pad((string) (12345678 + $index), 8, '0', STR_PAD_LEFT),
                    'authorized_channel' => 'whatsapp',
                    'attention_hours' => json_encode(['Lunes a viernes: 08:00 - 18:00', 'Sábados: 09:00 - 13:00']),
                    'supplier_attention_hours' => json_encode(['Martes y jueves: 09:00 - 11:00']),
                    'entry_requirements' => json_encode(['RUC actualizado', 'Notificación sanitaria', 'Muestra del producto']),
                    'purchase_conditions' => json_encode([
                        'payment_mode' => 'Contado a 15 días',
                        'initial_order' => '12 unidades',
                        'returns' => 'Devolución en 5 días si el producto no cumple especificaciones',
                    ]),
                    'supermarket_purchase_mode' => $type === 'supermercado' ? 'oficina_central' : null,
                    'central_office_contact' => $type === 'supermercado' ? 'compras@supermercado.test / 03 280 0000' : null,
                    'has_refrigeration' => in_array($type, ['supermercado', 'minimercado', 'tienda'], true),
                    'refrigeration_capacity' => $type === 'supermercado' ? 'Cámara de 12 m³' : null,
                    'weekly_purchase_volume' => 100 + ($index * 25),
                    'supply_frequency' => 'Semanal',
                    'privacy_consent_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $establishment = DB::table('establishments')->where('slug', Str::slug($name))->first();

            DB::table('establishment_needs')->where('establishment_id', $establishment->id)->delete();
            foreach ($needs as $needName) {
                DB::table('establishment_needs')->insert([
                    'establishment_id' => $establishment->id,
                    'category_id' => $categoryLacteos?->id,
                    'product_name' => $needName,
                    'quantity' => '20 unidades por semana',
                    'frequency' => 'Semanal',
                    'notes' => 'Buscamos proveedores locales de Latacunga.',
                    'status' => 'buscando',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $entrepreneur = \App\Models\User::query()->firstOrCreate(
            ['email' => 'emprendedor@latacunga.test'],
            [
                'name' => 'Emprendedor Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'privacy_consent_at' => now(),
            ],
        );
        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $entrepreneurRole->id,
                'model_type' => \App\Models\User::class,
                'model_id' => $entrepreneur->id,
            ],
        );

        $category = DB::table('categories')->where('slug', 'lacteos')->first();
        if ($category) {
            DB::table('entrepreneur_profiles')->updateOrInsert(
                ['user_id' => $entrepreneur->id],
                [
                    'business_name' => 'Sabores Cotopaxi',
                    'slug' => 'sabores-cotopaxi',
                    'description' => 'Producción artesanal de alimentos para comercios locales de Latacunga.',
                    'phone' => '0987654321',
                    'whatsapp' => '0987654321',
                    'coverage_zones' => json_encode(['Latacunga', 'San Buena Ventura', 'La Matriz']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            $profile = DB::table('entrepreneur_profiles')->where('user_id', $entrepreneur->id)->first();

            DB::table('products')->updateOrInsert(
                ['slug' => 'yogurt-artesanal-natural'],
                [
                    'entrepreneur_profile_id' => $profile->id,
                    'category_id' => $category->id,
                    'name' => 'Yogurt artesanal natural',
                    'description' => 'Yogurt natural elaborado con leche fresca de ganado de Cotopaxi. Sin conservantes.',
                    'presentation' => 'Botella de 500 ml',
                    'wholesale_price' => 1.25,
                    'suggested_retail_price' => 1.80,
                    'minimum_order' => 24,
                    'weekly_capacity' => 300,
                    'shelf_life_days' => 15,
                    'storage_conditions' => 'Refrigeración de 2 °C a 6 °C',
                    'required_temperature_min' => 2,
                    'required_temperature_max' => 6,
                    'coverage_zones' => json_encode(['Latacunga', 'Salcedo', 'Pujilí']),
                    'delivery_frequency' => 'Semanal',
                    'documentation' => json_encode(['RUC', 'Notificación sanitaria', 'Registro de establecimiento']),
                    'is_public' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
