<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Mail, MapPin, Pencil, Phone, PlusCircle, Thermometer } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Establishment } from '@/types';
import { useTeamRoutes } from '@/composables/useTeamRoutes';

const { establecimientos: establecimientosUrl, oportunidades: oportunidadesUrl, establecimiento } = useTeamRoutes();

const props = defineProps<{
    establishment: Establishment;
    canEdit?: boolean;
}>();

const miniMapContainer = ref<HTMLElement | null>(null);
let miniMap: google.maps.Map | null = null;

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

function openGoogleMaps() {
    const { latitude, longitude } = props.establishment;
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`, '_blank');
}

function saveOpportunity() {
    router.post(oportunidadesUrl.value, { establishment_id: props.establishment.id });
}

function loadGoogleMapsScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (typeof google !== 'undefined' && google.maps) { resolve(); return; }
        const existing = document.getElementById('google-maps-script');
        if (existing) { existing.addEventListener('load', () => resolve()); return; }
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

onMounted(async () => {
    await loadGoogleMapsScript();
    if (!miniMapContainer.value) return;

    const { latitude, longitude } = props.establishment;
    const { Map } = await google.maps.importLibrary('maps') as google.maps.MapsLibrary;
    const { AdvancedMarkerElement } = await google.maps.importLibrary('marker') as google.maps.MarkerLibrary;

    miniMap = new Map(miniMapContainer.value, {
        center: { lat: latitude, lng: longitude },
        zoom: 15,
        mapId: 'mini-map',
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        zoomControl: true,
        scrollwheel: false,
    });

    new AdvancedMarkerElement({
        map: miniMap,
        position: { lat: latitude, lng: longitude },
        title: props.establishment.name,
    });
});

onUnmounted(() => {
    miniMap = null;
});
</script>

<template>
    <Head :title="establishment.name" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="establecimientosUrl.value">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold">{{ establishment.name }}</h1>
                        <Badge variant="secondary">{{ tipoLabel[establishment.type] ?? establishment.type }}</Badge>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-700': establishment.reception_status === 'recibe',
                                'bg-red-100 text-red-700': establishment.reception_status === 'no_recibe',
                                'bg-yellow-100 text-yellow-700': establishment.reception_status === 'por_confirmar',
                            }"
                        >
                            {{ recepcionLabel[establishment.reception_status] }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ establishment.address }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button @click="saveOpportunity">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Guardar oportunidad
                </Button>
                <Button v-if="canEdit" variant="outline" as-child>
                    <Link :href="`/establecimientos/${establishment.slug}/edit`">
                        <Pencil class="mr-2 h-4 w-4" />
                        Editar
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Columna principal -->
            <div class="space-y-4 lg:col-span-2">
                <!-- Ubicación -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <MapPin class="h-4 w-4" />
                            Ubicación
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Dirección</p>
                                <p class="text-sm">{{ establishment.address }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Provincia / Cantón</p>
                                <p class="text-sm">{{ establishment.canton }}, {{ establishment.province }}</p>
                            </div>
                            <div v-if="establishment.parish">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Parroquia</p>
                                <p class="text-sm">{{ establishment.parish }}</p>
                            </div>
                            <div v-if="establishment.sector">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Sector</p>
                                <p class="text-sm">{{ establishment.sector }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Coordenadas</p>
                                <p class="text-sm font-mono">{{ establishment.latitude }}, {{ establishment.longitude }}</p>
                            </div>
                        </div>
                        <Separator />
                        <Button variant="outline" size="sm" @click="openGoogleMaps">
                            <ExternalLink class="mr-2 h-4 w-4" />
                            Cómo llegar (Google Maps)
                        </Button>
                    </CardContent>
                </Card>

                <!-- Contacto comercial -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Phone class="h-4 w-4" />
                            Contacto comercial
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="establishment.commercial_contact_name || establishment.commercial_phone || establishment.commercial_email"
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div v-if="establishment.commercial_contact_name">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Contacto</p>
                                <p class="text-sm">{{ establishment.commercial_contact_name }}</p>
                            </div>
                            <div v-if="establishment.commercial_phone">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Teléfono</p>
                                <p class="text-sm">
                                    <a :href="`tel:${establishment.commercial_phone}`" class="text-primary hover:underline">
                                        <Phone class="mr-1 inline h-3 w-3" />{{ establishment.commercial_phone }}
                                    </a>
                                </p>
                            </div>
                            <div v-if="establishment.commercial_email">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Correo</p>
                                <p class="text-sm">
                                    <a :href="`mailto:${establishment.commercial_email}`" class="text-primary hover:underline">
                                        <Mail class="mr-1 inline h-3 w-3" />{{ establishment.commercial_email }}
                                    </a>
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Sin información de contacto registrada.</p>
                    </CardContent>
                </Card>

                <!-- Categorías -->
                <Card v-if="establishment.categories && establishment.categories.length > 0">
                    <CardHeader>
                        <CardTitle class="text-base">Categorías que maneja</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="cat in establishment.categories" :key="cat.id" variant="outline">
                                {{ cat.name }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Requisitos y condiciones -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Thermometer class="h-4 w-4" />
                            Requisitos y condiciones
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="establishment.has_refrigeration ? 'bg-green-500' : 'bg-muted-foreground'"
                            />
                            {{ establishment.has_refrigeration ? 'Cuenta con refrigeración' : 'Sin refrigeración' }}
                        </div>
                        <div v-if="establishment.authorized_channel" class="text-sm">
                            <span class="font-medium">Canal autorizado:</span> {{ establishment.authorized_channel }}
                        </div>
                        <p v-if="!establishment.has_refrigeration && !establishment.authorized_channel" class="text-sm text-muted-foreground">
                            Sin condiciones especiales registradas.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Mapa mini -->
            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Ubicación en el mapa</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 overflow-hidden rounded-b-xl">
                        <div ref="miniMapContainer" style="height: 300px; z-index: 0" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>


