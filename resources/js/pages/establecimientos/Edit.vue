<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Locate } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { Category, Establishment } from '@/types';

const props = defineProps<{
    establishment: Establishment;
    categorias: Category[];
}>();

const geoLoading = ref(false);
const geoError = ref('');

const form = useForm({
    name: props.establishment.name,
    type: props.establishment.type,
    address: props.establishment.address,
    province: props.establishment.province,
    canton: props.establishment.canton,
    parish: props.establishment.parish ?? '',
    sector: props.establishment.sector ?? '',
    latitude: props.establishment.latitude as string | number,
    longitude: props.establishment.longitude as string | number,
    reception_status: props.establishment.reception_status,
    commercial_contact_name: props.establishment.commercial_contact_name ?? '',
    commercial_phone: props.establishment.commercial_phone ?? '',
    commercial_email: props.establishment.commercial_email ?? '',
    has_refrigeration: props.establishment.has_refrigeration,
    categories: props.establishment.categories?.map((c) => c.id) ?? ([] as number[]),
});

function useCurrentLocation() {
    if (!navigator.geolocation) {
        geoError.value = 'Tu navegador no soporta geolocalización.';
        return;
    }
    geoLoading.value = true;
    geoError.value = '';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude;
            form.longitude = pos.coords.longitude;
            geoLoading.value = false;
        },
        () => {
            geoError.value = 'No se pudo obtener la ubicación. Verifica los permisos.';
            geoLoading.value = false;
        },
    );
}

function toggleCategory(id: number) {
    const idx = form.categories.indexOf(id);
    if (idx === -1) {
        form.categories.push(id);
    } else {
        form.categories.splice(idx, 1);
    }
}

function submit() {
    form.patch(`/establecimientos/${props.establishment.slug}`);
}
</script>

<template>
    <Head :title="`Editar: ${establishment.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/establecimientos/${establishment.slug}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Volver
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">Editar establecimiento</h1>
                <p class="text-sm text-muted-foreground">{{ establishment.name }}</p>
            </div>
        </div>

        <form class="mx-auto w-full max-w-2xl space-y-6" @submit.prevent="submit">
            <!-- Datos básicos -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Datos básicos</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="name">Nombre del establecimiento *</Label>
                        <Input id="name" v-model="form.name" placeholder="Ej: Tienda Don Juan" required />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="type">Tipo *</Label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            required
                        >
                            <option value="tienda">Tienda</option>
                            <option value="minimercado">Minimercado</option>
                            <option value="supermercado">Supermercado</option>
                        </select>
                        <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="reception_status">Estado de recepción</Label>
                        <select
                            id="reception_status"
                            v-model="form.reception_status"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="recibe">Recibe proveedores</option>
                            <option value="no_recibe">No recibe</option>
                            <option value="por_confirmar">Por confirmar</option>
                        </select>
                        <p v-if="form.errors.reception_status" class="text-xs text-destructive">{{ form.errors.reception_status }}</p>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Ubicación</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="address">Dirección *</Label>
                        <Input id="address" v-model="form.address" placeholder="Ej: Av. Amazonas y Calle 5" required />
                        <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="province">Provincia</Label>
                        <Input id="province" v-model="form.province" />
                        <p v-if="form.errors.province" class="text-xs text-destructive">{{ form.errors.province }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="canton">Cantón</Label>
                        <Input id="canton" v-model="form.canton" />
                        <p v-if="form.errors.canton" class="text-xs text-destructive">{{ form.errors.canton }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="parish">Parroquia</Label>
                        <Input id="parish" v-model="form.parish" placeholder="Opcional" />
                        <p v-if="form.errors.parish" class="text-xs text-destructive">{{ form.errors.parish }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="sector">Sector</Label>
                        <Input id="sector" v-model="form.sector" placeholder="Opcional" />
                        <p v-if="form.errors.sector" class="text-xs text-destructive">{{ form.errors.sector }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="latitude">Latitud</Label>
                        <Input id="latitude" v-model="form.latitude" type="number" step="any" placeholder="-0.9333" />
                        <p v-if="form.errors.latitude" class="text-xs text-destructive">{{ form.errors.latitude }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="longitude">Longitud</Label>
                        <Input id="longitude" v-model="form.longitude" type="number" step="any" placeholder="-78.6167" />
                        <p v-if="form.errors.longitude" class="text-xs text-destructive">{{ form.errors.longitude }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <Button type="button" variant="outline" size="sm" :disabled="geoLoading" @click="useCurrentLocation">
                            <Locate class="mr-2 h-4 w-4" />
                            {{ geoLoading ? 'Obteniendo ubicación...' : 'Usar mi ubicación' }}
                        </Button>
                        <p v-if="geoError" class="mt-1 text-xs text-destructive">{{ geoError }}</p>
                    </div>
                </div>
            </div>

            <!-- Contacto comercial -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Contacto comercial</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="commercial_contact_name">Nombre del contacto</Label>
                        <Input id="commercial_contact_name" v-model="form.commercial_contact_name" placeholder="Opcional" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="commercial_phone">Teléfono</Label>
                        <Input id="commercial_phone" v-model="form.commercial_phone" placeholder="Opcional" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="commercial_email">Correo electrónico</Label>
                        <Input id="commercial_email" v-model="form.commercial_email" type="email" placeholder="Opcional" />
                    </div>
                </div>
            </div>

            <!-- Condiciones -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Condiciones y categorías</h2>
                <Separator />

                <div class="flex items-center gap-2">
                    <Checkbox id="has_refrigeration" :checked="form.has_refrigeration" @update:checked="form.has_refrigeration = $event" />
                    <Label for="has_refrigeration">Tiene refrigeración</Label>
                </div>

                <div v-if="categorias.length > 0" class="space-y-2">
                    <Label>Categorías que maneja</Label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div v-for="cat in categorias" :key="cat.id" class="flex items-center gap-2">
                            <Checkbox
                                :id="`cat-${cat.id}`"
                                :checked="form.categories.includes(cat.id)"
                                @update:checked="toggleCategory(cat.id)"
                            />
                            <Label :for="`cat-${cat.id}`">{{ cat.name }}</Label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" as-child>
                    <Link :href="`/establecimientos/${establishment.slug}`">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                </Button>
            </div>
        </form>
    </div>
</template>
