<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user  = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code'        => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team'        => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $profile = $user->entrepreneurProfile;

        return Inertia::render('Dashboard', [
            'pendingInvitations'    => $pendingInvitations,
            'totalEstablecimientos' => Establishment::count(),
            'totalProductos'        => $profile
                ? Product::where('entrepreneur_profile_id', $profile->id)->count()
                : 0,
            'totalOportunidades'    => $profile
                ? Opportunity::where('entrepreneur_profile_id', $profile->id)
                    ->whereNotIn('status', ['no_concretada'])
                    ->count()
                : 0,
        ]);
    }
}
