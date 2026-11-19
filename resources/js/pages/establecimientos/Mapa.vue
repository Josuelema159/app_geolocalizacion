<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { List, Loader2, MapIcon, MapPin, Navigation, Search, Store, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useTeamRoutes } from '@/composables/useTeamRoutes';
import type { Category } from '@/types';

// ─── Tipo local ───────────────────────────────────────────────────────────────

type Lugar = {
    id: number | null;
    slug: string | null;
    nombre: string;
    tipo: string;
    direccion: string;
    sector: string;
    parroquia: string;
    canton: string;
    provincia: string;
    lat: number;
    lng: number;
    recepcion: string;        // recibe | no_recibe | por_confirmar
    telefono: string;
    refrigeracion: string;    // Sí | No
    frecuencia: string;
    marcas: string;
    proveedor: string;
    notas: string;
    propietario: string;
    grupo: number;
    fuente: string;           // bd | excel
};

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    establecimientos: Lugar[];
    categorias: Category[];
}>();

// ─── Routes ───────────────────────────────────────────────────────────────────

const { slug } = useTeamRoutes();

function showUrl(lugar: Lugar) {
    if (!lugar.slug) return null;
    return `/${slug.value}/establecimientos/${lugar.slug}`;
}

// ─── Vista ────────────────────────────────────────────────────────────────────

const viewMode = ref<'mapa' | 'listado'>('mapa');

// ─── Filtros ──────────────────────────────────────────────────────────────────

const searchQuery    = ref('');
const filterTipo     = ref('');
const filterRecepcion = ref('');
const filterDistancia = ref<number | ''>('');

// ─── Ubicación del usuario ────────────────────────────────────────────────────

const userLat       = ref<number | null>(null);
const userLng       = ref<number | null>(null);
const manualAddress = ref('');
const locationError = ref('');
const locatingGPS   = ref(false);
const geocodingAddr = ref(false);

function haversineKm(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLng = ((lng2 - lng1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function requestGPS() {
    locationError.value = '';
    locatingGPS.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userLat.value = pos.coords.latitude;
            userLng.value = pos.coords.longitude;
            manualAddress.value = '';
            locatingGPS.value = false;
            centerMapOnUser();
        },
        () => { locationError.value = 'No se pudo obtener ubicación GPS.'; locatingGPS.value = false; },
        { timeout: 10000 },
    );
}

async function geocodeAddress() {
    if (!manualAddress.value.trim()) return;
    locationError.value = '';
    geocodingAddr.value = true;
    try {
        const { Geocoder } = await google.maps.importLibrary('geocoding') as google.maps.GeocodingLibrary;
        const gc = new Geocoder();
        const result = await gc.geocode({ address: manualAddress.value });
        if (result.results.length) {
            userLat.value = result.results[0].geometry.location.lat();
            userLng.value = result.results[0].geometry.location.lng();
            centerMapOnUser();
        } else {
            locationError.value = 'No se encontró esa dirección.';
        }
    } catch { locationError.value = 'Error al buscar la dirección.'; }
    finally   { geocodingAddr.value = false; }
}

// ─── Etiquetas y estilos ──────────────────────────────────────────────────────

const tipoLabel: Record<string, string> = {
    tienda: 'Tienda',
    minimercado: 'Minimercado',
    supermercado: 'Supermercado',
    panaderia: 'Panadería',
    distribuidor: 'Distribuidor',
};

const recepcionLabel: Record<string, string> = {
    recibe: 'Recibe proveedores',
    no_recibe: 'No recibe',
    por_confirmar: 'Por confirmar',
};

const recepcionBadge: Record<string, string> = {
    recibe:        'bg-green-100 text-green-700',
    no_recibe:     'bg-red-100 text-red-700',
    por_confirmar: 'bg-yellow-100 text-yellow-700',
};

type MarkerStyle = { bg: string; border: string; shape: string };
const tipoStyle: Record<string, MarkerStyle> = {
    tienda:       { bg: '#2563eb', border: '#1d4ed8', shape: 'circle' },
    minimercado:  { bg: '#f97316', border: '#ea580c', shape: 'diamond' },
    supermercado: { bg: '#7c3aed', border: '#6d28d9', shape: 'square' },
    panaderia:    { bg: '#db2777', border: '#be185d', shape: 'circle' },
    distribuidor: { bg: '#0891b2', border: '#0e7490', shape: 'diamond' },
};

// ─── Lista filtrada ───────────────────────────────────────────────────────────

const filteredLugares = computed(() =>
    props.establecimientos.filter((e) => {
        const q = searchQuery.value.toLowerCase();
        const matchSearch = !q ||
            e.nombre.toLowerCase().includes(q) ||
            e.direccion.toLowerCase().includes(q) ||
            e.sector.toLowerCase().includes(q) ||
            e.canton.toLowerCase().includes(q) ||
            e.parroquia.toLowerCase().includes(q);

        const matchTipo      = !filterTipo.value      || e.tipo === filterTipo.value;
        const matchRecepcion = !filterRecepcion.value  || e.recepcion === filterRecepcion.value;

        let matchDist = true;
        if (filterDistancia.value !== '' && userLat.value !== null && userLng.value !== null) {
            matchDist = haversineKm(userLat.value, userLng.value, e.lat, e.lng) <= Number(filterDistancia.value);
        }

        return matchSearch && matchTipo && matchRecepcion && matchDist;
    }),
);

function distKm(e: Lugar): number | null {
    if (userLat.value === null || userLng.value === null) return null;
    return haversineKm(userLat.value, userLng.value, e.lat, e.lng);
}

const hasFilters = computed(() =>
    searchQuery.value || filterTipo.value || filterRecepcion.value || filterDistancia.value !== '',
);

function clearFilters() {
    searchQuery.value = '';
    filterTipo.value = '';
    filterRecepcion.value = '';
    filterDistancia.value = '';
}

// ─── Google Maps ──────────────────────────────────────────────────────────────

const mapContainer = ref<HTMLElement | null>(null);
const selectedNombre = ref<string | null>(null);

type GMMap        = google.maps.Map;
type GMMarker     = google.maps.marker.AdvancedMarkerElement;
type GMInfoWindow = google.maps.InfoWindow;

let gmap:       GMMap        | null = null;
let infoWindow: GMInfoWindow | null = null;
let userMarker: GMMarker     | null = null;
const markerMap = new Map<string, GMMarker>();

function loadGoogleMapsScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (typeof google !== 'undefined' && google.maps) { resolve(); return; }
        const existing = document.getElementById('google-maps-script');
        if (existing) { existing.addEventListener('load', () => resolve()); return; }
        const script = document.createElement('script');
        script.id = 'google-maps-script';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_KEY}&libraries=marker,geocoding&v=beta`;
        script.async = true; script.defer = true;
        script.onload  = () => resolve();
        script.onerror = () => reject(new Error('Error cargando Google Maps'));
        document.head.appendChild(script);
    });
}

function markerKey(e: Lugar) { return `${e.fuente}-${e.id ?? e.nombre}`; }

function createMarkerEl(e: Lugar): HTMLElement {
    const style = tipoStyle[e.tipo] ?? { bg: '#6b7280', border: '#4b5563', shape: 'circle' };
    const el = document.createElement('div');
    const size = 20;
    if (style.shape === 'circle') {
        el.style.cssText = `width:${size}px;height:${size}px;border-radius:50%;background:${style.bg};border:2.5px solid ${style.border};box-shadow:0 2px 6px rgba(0,0,0,0.4);cursor:pointer;`;
    } else if (style.shape === 'diamond') {
        el.style.cssText = `width:${size}px;height:${size}px;background:${style.bg};border:2.5px solid ${style.border};box-shadow:0 2px 6px rgba(0,0,0,0.4);cursor:pointer;transform:rotate(45deg);`;
    } else {
        el.style.cssText = `width:${size}px;height:${size}px;border-radius:4px;background:${style.bg};border:2.5px solid ${style.border};box-shadow:0 2px 6px rgba(0,0,0,0.4);cursor:pointer;`;
    }
    return el;
}

function createUserMarkerEl(): HTMLElement {
    const el = document.createElement('div');
    el.style.cssText = `width:18px;height:18px;border-radius:50%;background:#0ea5e9;border:3px solid white;box-shadow:0 0 0 4px rgba(14,165,233,0.3);`;
    return el;
}

function infoContent(e: Lugar): string {
    const style = tipoStyle[e.tipo] ?? { bg: '#6b7280' };
    const recColor = e.recepcion === 'recibe' ? '#16a34a' : e.recepcion === 'no_recibe' ? '#dc2626' : '#d97706';
    const dist = distKm(e);
    const distStr = dist !== null ? `<span style="font-size:11px;color:#6b7280">${dist.toFixed(1)} km de ti</span><br>` : '';
    const fichaBtn = e.slug
        ? `<a href="/${slug.value}/establecimientos/${e.slug}" style="font-size:12px;padding:4px 10px;background:#1e293b;color:white;border-radius:5px;text-decoration:none">Ver ficha</a>`
        : '';
    const propStr = e.propietario ? `<span style="font-size:11px;color:#6b7280">Propietario: ${e.propietario}</span><br>` : '';
    const telStr  = e.telefono    ? `<span style="font-size:11px;color:#6b7280">Tel: ${e.telefono}</span><br>` : '';
    const marcStr = e.marcas      ? `<span style="font-size:11px;color:#6b7280">Marcas: ${e.marcas}</span><br>` : '';
    const provStr = e.proveedor   ? `<span style="font-size:11px;color:#6b7280">Proveedor: ${e.proveedor}</span><br>` : '';
    const refStr  = e.refrigeracion ? `<span style="font-size:11px;color:#6b7280">Refrigeración: ${e.refrigeracion}</span><br>` : '';
    const notaStr = e.notas ? `<span style="font-size:11px;color:#9ca3af;font-style:italic">${e.notas}</span><br>` : '';

    return `
        <div style="min-width:230px;font-family:system-ui,sans-serif;padding:6px 2px;line-height:1.6;max-width:280px">
            <strong style="font-size:14px">${e.nombre}</strong><br>
            <span style="font-size:12px;color:#6b7280">${tipoLabel[e.tipo] ?? e.tipo} · ${e.canton}</span><br>
            <span style="font-size:12px">${e.direccion}</span><br>
            ${propStr}${telStr}${distStr}
            <span style="font-size:12px;color:${recColor};font-weight:600;display:block;margin:2px 0">
                ${recepcionLabel[e.recepcion] ?? e.recepcion}
            </span>
            ${marcStr}${provStr}${refStr}${notaStr}
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                ${fichaBtn}
                <a href="https://www.google.com/maps/dir/?api=1&destination=${e.lat},${e.lng}" target="_blank"
                   style="font-size:12px;padding:4px 10px;background:#0ea5e9;color:white;border-radius:5px;text-decoration:none">
                    Cómo llegar
                </a>
            </div>
        </div>`;
}

async function initMap() {
    await loadGoogleMapsScript();
    if (!mapContainer.value) return;

    const { Map } = await google.maps.importLibrary('maps') as google.maps.MapsLibrary;
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker') as google.maps.MarkerLibrary;

    gmap = new Map(mapContainer.value, {
        center: { lat: -0.82, lng: -78.62 },
        zoom: 11,
        mapId: 'geolocalizacion-map',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    infoWindow = new google.maps.InfoWindow();

    props.establecimientos.forEach((e) => {
        const marker = new AdvancedMarkerElement({
            map: gmap!,
            position: { lat: e.lat, lng: e.lng },
            title: e.nombre,
            content: createMarkerEl(e),
        });

        marker.addListener('click', () => {
            selectedNombre.value = e.nombre;
            infoWindow!.setContent(infoContent(e));
            infoWindow!.open(gmap!, marker);
        });

        markerMap.set(markerKey(e), marker);
    });

    watch(filteredLugares, (visible) => {
        const visibleKeys = new Set(visible.map(markerKey));
        markerMap.forEach((marker, key) => {
            marker.map = visibleKeys.has(key) ? gmap : null;
        });
    }, { immediate: true });
}

function centerMapOnUser() {
    if (!gmap || userLat.value === null || userLng.value === null) return;
    gmap.panTo({ lat: userLat.value, lng: userLng.value });
    gmap.setZoom(14);
    placeUserMarker();
}

async function placeUserMarker() {
    if (userLat.value === null || userLng.value === null || !gmap) return;
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker') as google.maps.MarkerLibrary;
    if (userMarker) userMarker.map = null;
    userMarker = new AdvancedMarkerElement({
        map: gmap,
        position: { lat: userLat.value, lng: userLng.value },
        title: 'Tu ubicación',
        content: createUserMarkerEl(),
    });
}

function flyTo(e: Lugar) {
    selectedNombre.value = e.nombre;
    gmap?.panTo({ lat: e.lat, lng: e.lng });
    gmap?.setZoom(16);
    const marker = markerMap.get(markerKey(e));
    if (marker && infoWindow) {
        infoWindow.setContent(infoContent(e));
        infoWindow.open(gmap!, marker);
    }
    if (window.innerWidth < 1024) viewMode.value = 'mapa';
}

function openDirections(e: Lugar) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${e.lat},${e.lng}`, '_blank');
}

onMounted(() => {
    initMap();
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => { userLat.value = pos.coords.latitude; userLng.value = pos.coords.longitude; centerMapOnUser(); },
            () => {},
            { timeout: 8000 },
        );
    }
});

onUnmounted(() => {
    markerMap.forEach((m) => (m.map = null));
    markerMap.clear();
    if (userMarker) userMarker.map = null;
});
</script>

<template>
    <Head title="Mapa de oportunidades" />

    <div class="flex h-[calc(100vh-4rem)] flex-col">
        <!-- ── Barra superior ─────────────────────────────────────────── -->
        <div class="border-b bg-background">
            <!-- Fila 1: búsqueda + toggle -->
            <div class="flex flex-wrap items-center gap-2 px-4 pt-3 pb-2">
                <div class="relative flex-1 min-w-[180px] max-w-xs">
                    <Search class="absolute left-2.5 top-2 h-4 w-4 text-muted-foreground" />
                    <input v-model="searchQuery" type="text" placeholder="Ciudad, sector o establecimiento…"
                        class="h-8 w-full rounded-md border border-input bg-background pl-8 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
                    <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2 top-1.5 text-muted-foreground hover:text-foreground">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="flex rounded-md border overflow-hidden ml-auto">
                    <button @click="viewMode = 'mapa'"
                        :class="['flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium transition-colors',
                                 viewMode === 'mapa' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted']">
                        <MapIcon class="h-3.5 w-3.5" /> Mapa
                    </button>
                    <button @click="viewMode = 'listado'"
                        :class="['flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium transition-colors border-l',
                                 viewMode === 'listado' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted']">
                        <List class="h-3.5 w-3.5" /> Listado
                    </button>
                </div>
            </div>

            <!-- Fila 2: filtros -->
            <div class="flex flex-wrap items-center gap-2 px-4 pb-2">
                <select v-model="filterTipo" class="h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none">
                    <option value="">Todos los tipos</option>
                    <option value="tienda">🔵 Tienda</option>
                    <option value="minimercado">🟠 Minimercado</option>
                    <option value="supermercado">🟣 Supermercado</option>
                    <option value="panaderia">🩷 Panadería</option>
                    <option value="distribuidor">🩵 Distribuidor</option>
                </select>
                <select v-model="filterRecepcion" class="h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none">
                    <option value="">Todos</option>
                    <option value="recibe">✅ Recibe proveedores</option>
                    <option value="no_recibe">❌ No recibe</option>
                    <option value="por_confirmar">⏳ Por confirmar</option>
                </select>
                <select v-model="filterDistancia" class="h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none">
                    <option value="">Cualquier distancia</option>
                    <option value="1">≤ 1 km</option>
                    <option value="3">≤ 3 km</option>
                    <option value="5">≤ 5 km</option>
                    <option value="10">≤ 10 km</option>
                    <option value="25">≤ 25 km</option>
                    <option value="50">≤ 50 km</option>
                </select>
                <button v-if="hasFilters" @click="clearFilters" class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
                    <X class="h-3.5 w-3.5" /> Limpiar
                </button>
                <span class="ml-auto text-xs text-muted-foreground">
                    {{ filteredLugares.length }} de {{ establecimientos.length }}
                </span>
            </div>

            <!-- Fila 3: ubicación -->
            <div class="flex flex-wrap items-center gap-2 border-t px-4 py-2 bg-muted/30">
                <span class="text-xs text-muted-foreground font-medium">Tu ubicación:</span>
                <button @click="requestGPS" :disabled="locatingGPS"
                    class="flex items-center gap-1.5 rounded-md border bg-background px-2.5 py-1 text-xs hover:bg-muted disabled:opacity-60">
                    <Loader2 v-if="locatingGPS" class="h-3.5 w-3.5 animate-spin" />
                    <Navigation v-else class="h-3.5 w-3.5 text-sky-500" />
                    Usar GPS
                </button>
                <div class="flex flex-1 min-w-[200px] max-w-sm items-center gap-1.5">
                    <div class="relative flex-1">
                        <MapPin class="absolute left-2 top-1.5 h-3.5 w-3.5 text-muted-foreground" />
                        <input v-model="manualAddress" type="text" placeholder="O ingresa una dirección…"
                            class="h-7 w-full rounded-md border border-input bg-background pl-7 pr-2 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
                            @keydown.enter="geocodeAddress" />
                    </div>
                    <button @click="geocodeAddress" :disabled="geocodingAddr || !manualAddress.trim()"
                        class="flex items-center gap-1 rounded-md border bg-background px-2 py-1 text-xs hover:bg-muted disabled:opacity-50">
                        <Loader2 v-if="geocodingAddr" class="h-3 w-3 animate-spin" />
                        <span v-else>Buscar</span>
                    </button>
                </div>
                <span v-if="userLat !== null" class="text-xs text-sky-600 font-medium">
                    <Navigation class="inline h-3 w-3" /> Ubicación activa
                </span>
                <span v-if="locationError" class="text-xs text-red-500">{{ locationError }}</span>
            </div>
        </div>

        <!-- ── Contenido ──────────────────────────────────────────────── -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Lista lateral -->
            <div :class="['flex-shrink-0 overflow-y-auto border-r bg-background',
                          viewMode === 'listado' ? 'flex flex-col w-full lg:w-80' : 'hidden lg:flex lg:flex-col lg:w-80']">
                <div v-for="e in filteredLugares" :key="markerKey(e)"
                    class="cursor-pointer border-b px-4 py-3 hover:bg-muted/50 transition-colors"
                    :class="{ 'bg-muted': selectedNombre === e.nombre }"
                    @click="flyTo(e)">
                    <div class="flex items-start gap-2">
                        <!-- Indicador de tipo -->
                        <span class="mt-1 flex-shrink-0 h-3 w-3 inline-block"
                            :style="{
                                borderRadius: ['tienda','panaderia'].includes(e.tipo) ? '50%' : e.tipo === 'supermercado' ? '2px' : '0',
                                transform: ['minimercado','distribuidor'].includes(e.tipo) ? 'rotate(45deg)' : 'none',
                                background: tipoStyle[e.tipo]?.bg ?? '#6b7280',
                            }" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium leading-tight truncate">{{ e.nombre }}</p>
                            <p class="text-xs text-muted-foreground">{{ tipoLabel[e.tipo] ?? e.tipo }} · {{ e.canton }}</p>
                            <p class="text-xs text-muted-foreground truncate">{{ e.direccion }}</p>
                            <p v-if="e.propietario" class="text-xs text-muted-foreground truncate">{{ e.propietario }}</p>
                            <p v-if="distKm(e) !== null" class="text-xs text-sky-600 mt-0.5">{{ distKm(e)!.toFixed(1) }} km</p>
                        </div>
                        <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                            :class="recepcionBadge[e.recepcion]">
                            {{ e.recepcion === 'recibe' ? 'Recibe' : e.recepcion === 'no_recibe' ? 'No recibe' : 'Por confirmar' }}
                        </span>
                    </div>

                    <!-- Botones en modo listado -->
                    <div v-if="viewMode === 'listado'" class="mt-2 flex flex-wrap gap-2">
                        <Link v-if="e.slug" :href="showUrl(e)!" @click.stop
                            class="flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium hover:bg-muted">
                            <Store class="h-3 w-3" /> Ver ficha
                        </Link>
                        <button @click.stop="openDirections(e)"
                            class="flex items-center gap-1 rounded-md border border-sky-300 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 hover:bg-sky-100">
                            <Navigation class="h-3 w-3" /> Cómo llegar
                        </button>
                        <span v-if="e.telefono" class="text-xs text-muted-foreground self-center">📞 {{ e.telefono }}</span>
                    </div>
                </div>

                <div v-if="filteredLugares.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">
                    <MapPin class="mx-auto mb-2 h-8 w-8 opacity-30" />
                    Sin resultados con los filtros actuales.
                </div>
            </div>

            <!-- Mapa -->
            <div :class="['relative flex-1', viewMode === 'listado' ? 'hidden lg:block' : 'block']">
                <div ref="mapContainer" class="absolute inset-0" />

                <!-- Leyenda -->
                <div class="absolute bottom-6 left-3 z-10 rounded-lg border bg-background/90 p-2.5 text-xs shadow-md backdrop-blur-sm space-y-1.5">
                    <p class="font-semibold text-muted-foreground mb-1 uppercase tracking-wide text-[10px]">Tipo</p>
                    <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-full bg-blue-500 border border-blue-700 flex-shrink-0" /> Tienda</div>
                    <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 bg-orange-500 border border-orange-700 flex-shrink-0" style="transform:rotate(45deg)" /> Minimercado</div>
                    <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-violet-600 border border-violet-800 flex-shrink-0" /> Supermercado</div>
                    <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-full bg-pink-600 border border-pink-800 flex-shrink-0" /> Panadería</div>
                    <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 bg-cyan-600 border border-cyan-800 flex-shrink-0" style="transform:rotate(45deg)" /> Distribuidor</div>
                    <div class="flex items-center gap-2 mt-1 pt-1 border-t"><span class="inline-block h-3 w-3 rounded-full bg-sky-400 border-2 border-white flex-shrink-0 shadow" /> Tu ubicación</div>
                </div>
            </div>
        </div>
    </div>
</template>
