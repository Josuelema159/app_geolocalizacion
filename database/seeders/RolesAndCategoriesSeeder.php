<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['emprendedor', 'comercio', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Categorías
        $categories = [
            'Lácteos',
            'Bebidas',
            'Alimentos Secos',
            'Snacks',
            'Panadería',
            'Carnes y Embutidos',
            'Limpieza',
            'Cuidado Personal',
            'Congelados',
            'Otros',
        ];

        foreach ($categories as $name) {
            DB::table('categories')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'name'       => $name,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
