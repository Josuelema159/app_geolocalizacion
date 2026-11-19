<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeRequisitosTest extends TestCase
{
    use RefreshDatabase;

    public function test_rutas_principales_responden(): void
    {
        $this->seed();

        $user = User::where('email', 'emprendedor@latacunga.test')->firstOrFail();
        $team = $user->currentTeam;

        $this->actingAs($user)
            ->get("/{$team->slug}/dashboard")
            ->assertOk();

        foreach (['mapa', 'establecimientos', 'establecimientos/create', 'productos', 'productos/create', 'mi-perfil', 'oportunidades'] as $path) {
            $this->actingAs($user)
                ->get("/{$team->slug}/{$path}")
                ->assertOk();
        }

        $this->actingAs($user)
            ->get("/{$team->slug}/establecimientos/minisuper-el-centro")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('establecimientos/Show')
                ->has('establishment.needs', 2)
                ->has('establishment.supplier_attention_hours')
                ->has('establishment.purchase_conditions'));

        $this->actingAs($user)
            ->get("/{$team->slug}/productos/yogurt-artesanal-natural")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('productos/Show')
                ->has('product.documentation')
                ->has('product.required_temperature_min'));
    }

    public function test_catalogo_publico_y_pdf(): void
    {
        $this->seed();

        $this->get('/catalogo/sabores-cotopaxi')->assertOk();
        $this->get('/catalogo/sabores-cotopaxi/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
