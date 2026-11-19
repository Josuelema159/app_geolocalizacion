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

        $establishments = [
            ['MINISUPER EL CENTRO', 'tienda', -0.9339000, -78.6145000, 'La Matriz', 'recibe_propuestas', true],
            ['SUPERMERCADO SAN FRANCISCO', 'supermercado', -0.9351000, -78.6162000, 'La Matriz', 'por_confirmar', false],
            ['MINIMARKET LOS ANDES', 'minimarket', -0.9402000, -78.6221000, 'San Buena Ventura', 'recibe_propuestas', true],
            ['PANADERIA DON JUAN', 'panaderia', -0.9324000, -78.6118000, 'El Rosal', 'recibe_propuestas', false],
            ['TIENDA EL PARAISO', 'tienda', -0.9278000, -78.6189000, 'San Sebastian', 'no_recibe_por_ahora', false],
            ['MINISUPER LA ESPERANZA', 'tienda', -0.9443000, -78.6097000, 'Eloy Alfaro', 'por_confirmar', false],
            ['DISTRIBUIDORA ECUADOR', 'distribuidor', -0.9491000, -78.6254000, 'Ignacio Flores', 'recibe_propuestas', true],
            ['SUPERMERCADO EL SOL', 'supermercado', -0.9305000, -78.6092000, 'La Matriz', 'recibe_propuestas', true],
        ];

        foreach ($establishments as $index => [$name, $type, $latitude, $longitude, $parish, $reception, $verified]) {
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

            DB::table('establishments')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'merchant_id' => $type === 'tienda' ? null : $merchant->id,
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
                    'commercial_phone' => '09'.str_pad((string) (12345678 + $index), 8, '0', STR_PAD_LEFT),
                    'authorized_channel' => 'whatsapp',
                    'has_refrigeration' => in_array($type, ['supermercado', 'minimarket', 'tienda'], true),
                    'privacy_consent_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
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
                    'description' => 'Produccion artesanal de alimentos para comercios locales.',
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
                    'presentation' => 'Botella de 500 ml',
                    'wholesale_price' => 1.25,
                    'suggested_retail_price' => 1.80,
                    'minimum_order' => 24,
                    'weekly_capacity' => 300,
                    'shelf_life_days' => 15,
                    'storage_conditions' => 'Refrigeracion',
                    'required_temperature_min' => 2,
                    'required_temperature_max' => 6,
                    'coverage_zones' => json_encode(['Latacunga']),
                    'delivery_frequency' => 'Semanal',
                    'documentation' => json_encode(['RUC', 'Notificacion sanitaria']),
                    'is_public' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
