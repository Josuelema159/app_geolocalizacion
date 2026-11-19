<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntrepreneurProfileController;
use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\OportunidadController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PropuestaController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // Mapa de establecimientos
        Route::get('mapa', [EstablecimientoController::class, 'mapa'])->name('establecimientos.mapa');

        // CRUD establecimientos
        Route::resource('establecimientos', EstablecimientoController::class)->names([
            'index'   => 'establecimientos.index',
            'create'  => 'establecimientos.create',
            'store'   => 'establecimientos.store',
            'show'    => 'establecimientos.show',
            'edit'    => 'establecimientos.edit',
            'update'  => 'establecimientos.update',
            'destroy' => 'establecimientos.destroy',
        ]);

        // Productos
        Route::resource('productos', ProductoController::class)->names([
            'index'   => 'productos.index',
            'create'  => 'productos.create',
            'store'   => 'productos.store',
            'show'    => 'productos.show',
            'edit'    => 'productos.edit',
            'update'  => 'productos.update',
            'destroy' => 'productos.destroy',
        ]);

        // Oportunidades
        Route::get('oportunidades', [OportunidadController::class, 'index'])->name('oportunidades.index');
        Route::post('oportunidades', [OportunidadController::class, 'store'])->name('oportunidades.store');
        Route::patch('oportunidades/{oportunidad}', [OportunidadController::class, 'update'])->name('oportunidades.update');
        Route::delete('oportunidades/{oportunidad}', [OportunidadController::class, 'destroy'])->name('oportunidades.destroy');

        // Propuestas
        Route::post('propuestas', [PropuestaController::class, 'store'])->name('propuestas.store');
        Route::patch('propuestas/{propuesta}', [PropuestaController::class, 'update'])->name('propuestas.update');

        // Perfil emprendedor
        Route::get('mi-perfil', [EntrepreneurProfileController::class, 'show'])->name('emprendedor.perfil');
        Route::post('mi-perfil', [EntrepreneurProfileController::class, 'store'])->name('emprendedor.perfil.store');
        Route::patch('mi-perfil', [EntrepreneurProfileController::class, 'update'])->name('emprendedor.perfil.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
