<?php

namespace App\Http\Controllers;

use App\Models\EntrepreneurProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    public function show(EntrepreneurProfile $profile)
    {
        return Inertia::render('catalogo/Show', [
            'perfil'    => $profile,
            'productos' => $this->publicProducts($profile),
        ]);
    }

    public function pdf(EntrepreneurProfile $profile)
    {
        $productos = $this->publicProducts($profile);

        $pdf = Pdf::loadView('pdf.ficha-comercial', [
            'perfil'    => $profile,
            'productos' => $productos,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ficha-comercial-'.$profile->slug.'.pdf');
    }

    private function publicProducts(EntrepreneurProfile $profile)
    {
        return $profile->products()
            ->where('is_public', true)
            ->with('category')
            ->orderBy('name')
            ->get();
    }
}
