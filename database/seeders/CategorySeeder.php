<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos',
            'Lacteos',
            'Bebidas',
            'Limpieza',
            'Cuidado personal',
            'Panaderia',
            'Conservas',
            'Snacks',
        ];

        foreach ($categories as $name) {
            DB::table('categories')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
