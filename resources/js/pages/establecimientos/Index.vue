<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Map, Plus, Search, Store } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTeamRoutes } from '@/composables/useTeamRoutes';
import type { Category, Establishment, PaginatedData } from '@/types';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    establecimientos: PaginatedData<Establishment>;
    filters: { search?: string; tipo?: string; recepcion?: string };
    categorias: Category[];
}>();

const { mapa, establecimientos: establecimientosUrl, establecimientoCreate, establecimiento } = useTeamRoutes();

const search = ref(props.filters.search ?? '');
const filterTipo = ref(props.filters.tipo ?? '');
const filterRecepcion = ref(props.filters.recepcion ?? '');

const tipoLabel: Record<string, string> = {
    tienda: 'Tienda',
    minimercado: 'Minimercado',
    supermercado: 'Supermercado',
};

const recepcionLabel: Record<string, string> = {
    recibe: 'Recibe proveedores',
    no_recibe: 'No recibe',
    por_confirmar: 'Por confirmar',
};

function applyFilters() {
    router.get(
        establecimientosUrl.value,
        {
            search: search.value || undefined,
            tipo: filterTipo.value || undefined,
            recepcion: filterRecepcion.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch([filterTipo, filterRecepcion], applyFilters);

function getLinks(): PaginationLink[] {
    return props.establecimientos.links ?? [];
}
</script>

<template>
    <Head title="Establecimientos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Establecimientos</h1>
                <p class="text-sm text-muted-foreground">Gestiona los puntos de venta del territorio</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="mapa">
                        <Map class="mr-2 h-4 w-4" />
                        Ver mapa
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="establecimientoCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Registrar establecimiento
                    </Link>
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <Search class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Buscar nombre o dirección..." class="w-64 pl-9" />
            </div>
            <select v-model="filterTipo" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                <option value="">Todos los tipos</option>
                <option value="tienda">Tienda</option>
                <option value="minimercado">Minimercado</option>
                <option value="supermercado">Supermercado</option>
            </select>
            <select v-model="filterRecepcion" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                <option value="">Todos los estados</option>
                <option value="recibe">Recibe proveedores</option>
                <option value="no_recibe">No recibe</option>
                <option value="por_confirmar">Por confirmar</option>
            </select>
            <span class="ml-auto text-sm text-muted-foreground">
                {{ establecimientos.total ?? establecimientos.data.length }} registros
            </span>
        </div>

        <div class="rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Ubicación</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Recepción</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="establecimientos.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                <Store class="mx-auto mb-2 h-8 w-8 text-muted-foreground/40" />
                                No se encontraron establecimientos
                            </td>
                        </tr>
                        <tr
                            v-for="est in establecimientos.data"
                            :key="est.id"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ est.name }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">{{ tipoLabel[est.type] ?? est.type }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ est.canton }}, {{ est.province }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-700': est.reception_status === 'recibe',
                                        'bg-red-100 text-red-700': est.reception_status === 'no_recibe',
                                        'bg-yellow-100 text-yellow-700': est.reception_status === 'por_confirmar',
                                    }"
                                >
                                    {{ recepcionLabel[est.reception_status] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="establecimiento(est.slug).value">Ver</Link>
                                    </Button>
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="establecimiento(est.slug).value + '/edit'">Editar</Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="getLinks().length > 3" class="flex items-center justify-center gap-1">
            <template v-for="link in getLinks()" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-muted"
                    :class="link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : ''"
                    preserve-state
                    v-html="link.label"
                />
                <span
                    v-else
                    class="cursor-default rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
