<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MapPin, Package, Store, TrendingUp } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import { useTeamRoutes } from '@/composables/useTeamRoutes';
import type { DashboardInvitation, Team } from '@/types';

defineProps<{
    pendingInvitations?: DashboardInvitation[];
    totalEstablecimientos?: number;
    totalProductos?: number;
    totalOportunidades?: number;
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [{ title: 'Dashboard', href: '/' }],
    }),
});

const { mapa, establecimientos, productos, oportunidades } = useTeamRoutes();
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Bienvenido</h1>
            <p class="text-sm text-muted-foreground">Plataforma de geolocalización de establecimientos lácteos</p>
        </div>

        <!-- Tarjetas de resumen -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link :href="establecimientos.value">
                <Card class="cursor-pointer transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Establecimientos</CardTitle>
                        <Store class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ totalEstablecimientos ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground mt-1">registrados en el sistema</p>
                    </CardContent>
                </Card>
            </Link>

            <Link :href="mapa.value">
                <Card class="cursor-pointer transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Mapa de oportunidades</CardTitle>
                        <MapPin class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">Ver</p>
                        <p class="text-xs text-muted-foreground mt-1">establecimientos cercanos</p>
                    </CardContent>
                </Card>
            </Link>

            <Link :href="productos.value">
                <Card class="cursor-pointer transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Mis productos</CardTitle>
                        <Package class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ totalProductos ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground mt-1">en tu catálogo</p>
                    </CardContent>
                </Card>
            </Link>

            <Link :href="oportunidades.value">
                <Card class="cursor-pointer transition-shadow hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Oportunidades</CardTitle>
                        <TrendingUp class="h-5 w-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold">{{ totalOportunidades ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground mt-1">en seguimiento</p>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <!-- Accesos rápidos -->
        <div>
            <h2 class="mb-3 text-sm font-medium text-muted-foreground uppercase tracking-wide">Accesos rápidos</h2>
            <div class="flex flex-wrap gap-3">
                <Link
                    :href="mapa.value"
                    class="rounded-lg border bg-card px-4 py-3 text-sm font-medium hover:bg-muted transition-colors flex items-center gap-2"
                >
                    <MapPin class="h-4 w-4" /> Ver mapa
                </Link>
                <Link
                    :href="establecimientos.value + '/create'"
                    class="rounded-lg border bg-card px-4 py-3 text-sm font-medium hover:bg-muted transition-colors flex items-center gap-2"
                >
                    <Store class="h-4 w-4" /> Registrar establecimiento
                </Link>
                <Link
                    :href="productos.value + '/create'"
                    class="rounded-lg border bg-card px-4 py-3 text-sm font-medium hover:bg-muted transition-colors flex items-center gap-2"
                >
                    <Package class="h-4 w-4" /> Agregar producto
                </Link>
            </div>
        </div>
    </div>
</template>
