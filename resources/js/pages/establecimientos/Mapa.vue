<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useTeamRoutes } from '@/composables/useTeamRoutes';
import type { Establishment } from '@/types';

const { establecimientos: establecimientosUrl } = useTeamRoutes();

const props = defineProps<{
    establecimientos: Establishment[];
}>();

const mapContainer = ref<HTMLElement | null>(null);
const selectedEstablishment = ref<Establishment | null>(null);
const searchQuery = ref('');
const filterTipo = ref('');
const filterRecepcion = ref('');

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

const recepcionColor: Record<string, string> = {
    recibe: '#16a34a',
    no_recibe: '#dc2626',
    por_confirmar: '#d97706',
};

const filteredEstablishments = computed(() =>
    props.establecimientos.filter((e) => {
        const matchSearch =
            !searchQuery.value ||
            e.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            e.address.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchTipo = !filterTipo.value || e.type === filterTipo.value;
        const matchRecepcion = !filterRecepcion.value || e.reception_status === filterRecepcion.value;
        return matchSearch && matchTipo && matchRecepcion;
    }),
);

// Google Maps types
type GMMap = google.maps.Map;
type GMMarker = google.maps.marker.AdvancedMarkerElement;
type GMInfoWindow = google.maps.InfoWindow;

let gmap: GMMap | null = null;
const markerMap = new Map<number, GMMarker>();
let infoWindow: GMInfoWindow | null = null;

function loadGoogleMapsScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (typeof google !== 'undefined' && google.maps) {
            resolve();
            return;
        }
        const existing = document.getElementById('google-maps-script');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            return;
        }
        const script = document.createElement('script');
        script.id = 'google-maps-script';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_KEY}&libraries=marker&v=beta`;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Error cargando Google Maps'));
        document.head.appendChild(script);
    });
}

function createMarkerElement(color: string): HTMLElement {
    const el = document.createElement('div');
    el.style.cssText = `
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: ${color};
        border: 2px solid white;
        box-shadow: 0 1px 4px rgba(0,0,0,0.5);
        cursor: pointer;
    `;
    return el;
}

onMounted(async () => {
    await loadGoogleMapsScript();
    if (!mapContainer.value) return;

    const { Map } = await google.maps.importLibrary('maps') as google.maps.MapsLibrary;
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker') as google.maps.MarkerLibrary;

    gmap = new Map(mapContainer.value, {
        center: { lat: -0.9333, lng: -78.6167 },
        zoom: 13,
        mapId: 'geolocalizacion-map',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    infoWindow = new google.maps.InfoWindow();

    props.establecimientos.forEach((est) => {
        const color = recepcionColor[est.reception_status] ?? '#6b7280';
        const markerEl = createMarkerElement(color);

        const marker = new AdvancedMarkerElement({
            map: gmap!,
            position: { lat: est.latitude, lng: est.longitude },
            title: est.name,
            content: markerEl,
        });

        marker.addListener('click', () => {
            selectedEstablishment.value = est;
            infoWindow!.setContent(`
                <div style="min-width:200px;font-family:sans-serif;padding:4px 2px">
                    <strong style="font-size:14px">${est.name}</strong><br>
                    <span style="font-size:12px;color:#6b7280">${tipoLabel[est.type] ?? est.type}</span><br>
                    <span style="font-size:12px">${est.address}</span><br>
                    <span style="font-size:12px;color:${color};font-weight:600;margin-top:4px;display:block">
                        ${recepcionLabel[est.reception_status]}
                    </span>
                </div>
            `);
            infoWindow!.open(gmap!, marker);
        });

        markerMap.set(est.id, marker);
    });
});

onUnmounted(() => {
    markerMap.forEach((m) => (m.map = null));
    markerMap.clear();
});

function flyTo(est: Establishment) {
    selectedEstablishment.value = est;
    gmap?.panTo({ lat: est.latitude, lng: est.longitude });
    gmap?.setZoom(16);
    const marker = markerMap.get(est.id);
    if (marker && infoWindow) {
        const color = recepcionColor[est.reception_status] ?? '#6b7280';
        infoWindow.setContent(`
            <div style="min-width:200px;font-family:sans-serif;padding:4px 2px">
                <strong style="font-size:14px">${est.name}</strong><br>
                <span style="font-size:12px;color:#6b7280">${tipoLabel[est.type] ?? est.type}</span><br>
                <span style="font-size:12px">${est.address}</span><br>
                <span style="font-size:12px;color:${color};font-weight:600;margin-top:4px;display:block">
                    ${recepcionLabel[est.reception_status]}
                </span>
            </div>
        `);
        infoWindow.open(gmap!, marker);
    }
}
</script>

<template>
    <Head title="Mapa de oportunidades" />
    <div class="flex h-[calc(100vh-4rem)] flex-col">
        <!-- Barra de filtros -->
        <div class="flex flex-wrap items-center gap-3 border-b bg-background px-4 py-3">
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar establecimiento o dirección..."
                class="h-9 w-64 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <select v-model="filterTipo" class="h-9 rounded-md border border-input bg-background px-3 text-sm bg-background">
                <option value="">Todos los tipos</option>
                <option value="tienda">Tienda</option>
                <option value="minimercado">Minimercado</option>
                <option value="supermercado">Supermercado</option>
            </select>
            <select v-model="filterRecepcion" class="h-9 rounded-md border border-input bg-background px-3 text-sm bg-background">
                <option value="">Todos</option>
                <option value="recibe">Recibe proveedores</option>
                <option value="no_recibe">No recibe</option>
                <option value="por_confirmar">Por confirmar</option>
            </select>
            <span class="ml-auto text-sm text-muted-foreground">{{ filteredEstablishments.length }} establecimientos</span>
            <Link
                :href="establecimientosUrl"
                class="rounded-md bg-primary px-3 py-1.5 text-sm text-primary-foreground hover:bg-primary/90"
            >
                Ver listado
            </Link>
        </div>

        <div class="flex flex-1 overflow-hidden">
            <!-- Lista lateral -->
            <div class="hidden w-72 flex-shrink-0 overflow-y-auto border-r bg-background lg:block">
                <div
                    v-for="est in filteredEstablishments"
                    :key="est.id"
                    class="cursor-pointer border-b px-4 py-3 hover:bg-muted/50"
                    :class="{ 'bg-muted': selectedEstablishment?.id === est.id }"
                    @click="flyTo(est)"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-medium">{{ est.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ tipoLabel[est.type] }}</p>
                            <p class="text-xs text-muted-foreground">{{ est.address }}</p>
                        </div>
                        <span
                            class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-700': est.reception_status === 'recibe',
                                'bg-red-100 text-red-700': est.reception_status === 'no_recibe',
                                'bg-yellow-100 text-yellow-700': est.reception_status === 'por_confirmar',
                            }"
                        >
                            {{ recepcionLabel[est.reception_status] }}
                        </span>
                    </div>
                </div>
                <div v-if="filteredEstablishments.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                    Sin resultados
                </div>
            </div>

            <!-- Mapa Google Maps -->
            <div ref="mapContainer" class="flex-1" />
        </div>
    </div>
</template>
