<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;

class PropuestaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'message'        => 'required|string',
            'products'       => 'nullable|array',
            'products.*.product_id'    => 'required_with:products|exists:products,id',
            'products.*.quantity'      => 'nullable|numeric|min:1',
            'products.*.quoted_price'  => 'nullable|numeric|min:0',
        ]);

        $proposal = Proposal::create([
            'opportunity_id' => $data['opportunity_id'],
            'message'        => $data['message'],
            'sent_by'        => $request->user()->id,
            'status'         => 'enviada',
        ]);

        if (!empty($data['products'])) {
            $syncData = [];
            foreach ($data['products'] as $item) {
                $syncData[$item['product_id']] = [
                    'quantity'     => $item['quantity'] ?? null,
                    'quoted_price' => $item['quoted_price'] ?? null,
                ];
            }
            $proposal->products()->sync($syncData);
        }

        return redirect()->back()->with('success', 'Propuesta enviada correctamente.');
    }

    public function update(Request $request, $currentTeam, Proposal $propuesta)
    {
        $data = $request->validate([
            'merchant_response' => 'required|in:' . implode(',', Proposal::MERCHANT_RESPONSES),
            'status'            => 'nullable|string|max:50',
        ]);

        $updateData = ['merchant_response' => $data['merchant_response']];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $updateData['responded_at'] = now();

        $propuesta->update($updateData);

        return redirect()->back()->with('success', 'Respuesta registrada correctamente.');
    }
}
