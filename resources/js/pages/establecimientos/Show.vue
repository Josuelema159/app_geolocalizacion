<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarClock,
    ClipboardList,
    ExternalLink,
    FileText,
    Mail,
    MapPin,
    Pencil,
    Phone,
    PlusCircle,
    ShoppingBasket,
    Store,
    Thermometer,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Establishment } from '@/types';
import { useTeamRoutes } from '@/composables/useTeamRoutes';

const { establecimientos: establecimientosUrl, oportunidades: oportunidadesUrl, establecimiento: establishmentEditBase, slug } = useTeamRoutes();

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
    recibe: 'Recibe propuestas',
    no_recibe: 'No recibe por ahora',
    por_confirmar: 'Por confirmar',
};

const supermercadoLabel: Record<string, string> = {
    sucursal: 'La decisión de compra se realiza en la sucursal',
    oficina_central: 'La decisión de compra se realiza en la oficina central',
};

function photoUrl(path: string | null): string {
    if (!path) return '';
    return path.startsWith('http') ? path : `/storage/${path}`;
}

function formatDate(value: string | null): string {
    if (!value) return 'Sin registrar';
    return new Date(value).toLocaleDateString('es-EC', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function openGoogleMaps() {
    const { latitude, longitude } = props.establishment;
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`, '_blank');
}

function saveOpportunity() {
    router.post(oportunidadesUrl.value, { establishment_id: props.establishment.id });
}

function verify() {
    router.patch(`/${slug.value}/establecimientos/${props.establishment.slug}/verify`);
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
    try {
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
    } catch {
        // El mapa es opcional; el resto de la ficha sigue funcionando.
    }
});

onUnmounted(() => {
    miniMap = null;
});
</script>

<template>
    <Head :title="establishment.name" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Encabezado -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-4">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="establecimientosUrl">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Volver
                    </Link>
                </Button>

                <img
                    v-if="establishment.photo_path"
                    :src="photoUrl(establishment.photo_path)"
                    :alt="establishment.name"
                    class="h-24 w-24 rounded-xl border object-cover"
                />
                <div
                    v-else
                    class="flex h-24 w-24 items-center justify-center rounded-xl border bg-muted"
                >
                    <Store class="h-10 w-10 text-muted-foreground" />
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
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
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="establishment.verification_status === 'verificado'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700'"
                        >
                            {{ establishment.verification_status === 'verificado' ? 'Información verificada' : 'Información pendiente' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ establishment.address }}</p>
                    <p class="text-xs text-muted-foreground">
                        Última actualización: {{ formatDate(establishment.updated_at) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button @click="saveOpportunity">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Guardar oportunidad
                </Button>
                <Button v-if="canEdit" variant="outline" @click="verify">
                    <BadgeCheck class="mr-2 h-4 w-4" />
                    Marcar verificado
                </Button>
                <Button v-if="canEdit" variant="outline" as-child>
                    <Link :href="`${establishmentEditBase(establishment.slug).value}/edit`">
                        <Pencil class="mr-2 h-4 w-4" />
                        Editar
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
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
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Dirección</p>
                                <p class="text-sm">{{ establishment.address }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Provincia / Cantón</p>
                                <p class="text-sm">{{ establishment.canton }}, {{ establishment.province }}</p>
                            </div>
                            <div v-if="establishment.parish">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Parroquia</p>
                                <p class="text-sm">{{ establishment.parish }}</p>
                            </div>
                            <div v-if="establishment.sector">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Sector</p>
                                <p class="text-sm">{{ establishment.sector }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Punto exacto (coordenadas)</p>
                                <p class="font-mono text-sm">{{ establishment.latitude }}, {{ establishment.longitude }}</p>
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
                            v-if="establishment.commercial_contact_name || establishment.commercial_phone || establishment.commercial_email || establishment.authorized_channel"
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div v-if="establishment.commercial_contact_name">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Nombre o área responsable</p>
                                <p class="text-sm">{{ establishment.commercial_contact_name }}</p>
                            </div>
                            <div v-if="establishment.commercial_phone">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Teléfono</p>
                                <p class="text-sm">
                                    <a :href="`tel:${establishment.commercial_phone}`" class="text-primary hover:underline">
                                        <Phone class="mr-1 inline h-3 w-3" />{{ establishment.commercial_phone }}
                                    </a>
                                </p>
                            </div>
                            <div v-if="establishment.commercial_email">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Correo</p>
                                <p class="text-sm">
                                    <a :href="`mailto:${establishment.commercial_email}`" class="text-primary hover:underline">
                                        <Mail class="mr-1 inline h-3 w-3" />{{ establishment.commercial_email }}
                                    </a>
                                </p>
                            </div>
                            <div v-if="establishment.authorized_channel">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Medio autorizado de contacto</p>
                                <p class="text-sm">{{ establishment.authorized_channel }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Sin información de contacto registrada.</p>
                    </CardContent>
                </Card>

                <!-- Categorías -->
                <Card v-if="establishment.categories && establishment.categories.length">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ShoppingBasket class="h-4 w-4" />
                            Categorías que vende
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="cat in establishment.categories" :key="cat.id" variant="outline">
                                {{ cat.name }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Productos que busca -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ShoppingBasket class="h-4 w-4" />
                            Productos que busca
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul v-if="establishment.needs && establishment.needs.length" class="space-y-2">
                            <li
                                v-for="need in establishment.needs"
                                :key="need.id"
                                class="rounded-lg border p-3 text-sm"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-medium">{{ need.product_name }}</span>
                                    <Badge variant="outline">{{ need.status.replace('_', ' ') }}</Badge>
                                </div>
                                <p v-if="need.category" class="text-xs text-muted-foreground">Categoría: {{ need.category.name }}</p>
                                <p v-if="need.quantity" class="text-xs text-muted-foreground">Cantidad: {{ need.quantity }}</p>
                                <p v-if="need.frequency" class="text-xs text-muted-foreground">Frecuencia: {{ need.frequency }}</p>
                                <p v-if="need.notes" class="mt-1 text-sm">{{ need.notes }}</p>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">Este comercio aún no ha publicado necesidades.</p>
                    </CardContent>
                </Card>

                <!-- Horarios -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <CalendarClock class="h-4 w-4" />
                            Horarios
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Atención al público</p>
                            <p v-if="establishment.attention_hours?.length">
                                <span v-for="(line, index) in establishment.attention_hours" :key="index" class="block">{{ line }}</span>
                            </p>
                            <p v-else class="text-muted-foreground">Sin registrar</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Atención a proveedores</p>
                            <p v-if="establishment.supplier_attention_hours?.length">
                                <span v-for="(line, index) in establishment.supplier_attention_hours" :key="index" class="block">{{ line }}</span>
                            </p>
                            <p v-else class="text-muted-foreground">Sin registrar</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Requisitos de ingreso -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <ClipboardList class="h-4 w-4" />
                            Requisitos de ingreso
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul v-if="establishment.entry_requirements?.length" class="list-inside list-disc space-y-1 text-sm">
                            <li v-for="(line, index) in establishment.entry_requirements" :key="index">{{ line }}</li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">No se han registrado requisitos.</p>
                    </CardContent>
                </Card>

                <!-- Condiciones de compra -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileText class="h-4 w-4" />
                            Condiciones de compra
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Forma de pago</p>
                            <p>{{ establishment.purchase_conditions?.payment_mode ?? 'Sin registrar' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Pedido inicial</p>
                            <p>{{ establishment.purchase_conditions?.initial_order ?? 'Sin registrar' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Devoluciones</p>
                            <p>{{ establishment.purchase_conditions?.returns ?? 'Sin registrar' }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Supermercado -->
                <Card v-if="establishment.type === 'supermercado'">
                    <CardHeader>
                        <CardTitle class="text-base">Decisión de compra (supermercado)</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p class="font-medium">
                            {{ supermercadoLabel[establishment.supermarket_purchase_mode ?? ''] ?? 'Modo de decisión sin registrar' }}
                        </p>
                        <div v-if="establishment.central_office_contact">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Contacto de oficina central</p>
                            <p>{{ establishment.central_office_contact }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Datos adicionales -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Thermometer class="h-4 w-4" />
                            Datos adicionales
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Refrigeración</p>
                            <p>{{ establishment.has_refrigeration ? 'Sí' : 'No' }}</p>
                            <p v-if="establishment.refrigeration_capacity" class="text-muted-foreground">{{ establishment.refrigeration_capacity }}</p>
                        </div>
                        <div v-if="establishment.weekly_purchase_volume">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Volumen semanal de compra</p>
                            <p>{{ establishment.weekly_purchase_volume }}</p>
                        </div>
                        <div v-if="establishment.supply_frequency">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Frecuencia de abastecimiento</p>
                            <p>{{ establishment.supply_frequency }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Columna lateral -->
            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Ubicación en el mapa</CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-hidden rounded-b-xl p-0">
                        <div ref="miniMapContainer" style="height: 300px; z-index: 0" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Verificación</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p>
                            <span class="font-medium">Estado:</span>
                            {{ establishment.verification_status === 'verificado' ? 'Verificado' : 'Pendiente de verificación' }}
                        </p>
                        <p><span class="font-medium">Fecha de verificación:</span> {{ formatDate(establishment.verified_at) }}</p>
                        <p><span class="font-medium">Última verificación:</span> {{ formatDate(establishment.last_verified_at) }}</p>
                        <p><span class="font-medium">Última actualización:</span> {{ formatDate(establishment.updated_at) }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
