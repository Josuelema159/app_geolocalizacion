<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Locate, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useTeamRoutes } from '@/composables/useTeamRoutes';
import type { Category, Establishment } from '@/types';

const props = defineProps<{
    establishment: Establishment;
    categorias: Category[];
}>();

const { establecimiento: establishmentUrl, establecimientos: establecimientosUrl } = useTeamRoutes();

const geoLoading = ref(false);
const geoError = ref('');
const photoPreview = ref('');

const lines = (value: string[] | null | undefined) => value?.join('\n') ?? '';

const form = useForm({
    _method: 'PATCH',
    name: props.establishment.name,
    type: props.establishment.type,
    photo: null as File | null,
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
    authorized_channel: props.establishment.authorized_channel ?? '',
    attention_hours: lines(props.establishment.attention_hours),
    supplier_attention_hours: lines(props.establishment.supplier_attention_hours),
    entry_requirements: lines(props.establishment.entry_requirements),
    purchase_conditions: {
        payment_mode: props.establishment.purchase_conditions?.payment_mode ?? '',
        initial_order: props.establishment.purchase_conditions?.initial_order ?? '',
        returns: props.establishment.purchase_conditions?.returns ?? '',
    },
    supermarket_purchase_mode: props.establishment.supermarket_purchase_mode ?? '' as '' | 'sucursal' | 'oficina_central',
    central_office_contact: props.establishment.central_office_contact ?? '',
    has_refrigeration: props.establishment.has_refrigeration,
    refrigeration_capacity: props.establishment.refrigeration_capacity ?? '',
    weekly_purchase_volume: props.establishment.weekly_purchase_volume ?? '' as string | number,
    supply_frequency: props.establishment.supply_frequency ?? '',
    categories: props.establishment.categories?.map((c) => c.id) ?? ([] as number[]),
    needs: (props.establishment.needs ?? []).map((need) => ({
        id: need.id,
        product_name: need.product_name,
        category_id: need.category?.id ?? '' as string | number,
        quantity: need.quantity ?? '',
        frequency: need.frequency ?? '',
        notes: need.notes ?? '',
    })),
});

function onPhotoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : '';
}

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

function addNeed() {
    form.needs.push({ id: 0, product_name: '', category_id: '', quantity: '', frequency: '', notes: '' });
}

function removeNeed(index: number) {
    form.needs.splice(index, 1);
}

function submit() {
    form.post(establishmentUrl(props.establishment.slug).value, { forceFormData: true });
}
</script>

<template>
    <Head :title="`Editar: ${establishment.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="establishmentUrl(establishment.slug).value">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Volver
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">Editar establecimiento</h1>
                <p class="text-sm text-muted-foreground">{{ establishment.name }}</p>
            </div>
        </div>

        <form class="mx-auto w-full max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Datos básicos</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="name">Nombre del establecimiento *</Label>
                        <Input id="name" v-model="form.name" required />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="type">Tipo de comercio *</Label>
                        <select id="type" v-model="form.type" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm" required>
                            <option value="tienda">Tienda</option>
                            <option value="minimercado">Minimercado</option>
                            <option value="supermercado">Supermercado</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="reception_status">Recepción de proveedores</Label>
                        <select id="reception_status" v-model="form.reception_status" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="recibe">Recibe propuestas</option>
                            <option value="no_recibe">No recibe por ahora</option>
                            <option value="por_confirmar">Por confirmar</option>
                        </select>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="photo">Fotografía</Label>
                        <Input id="photo" type="file" accept="image/*" @change="onPhotoChange" />
                        <img v-if="establishment.photo_path && !photoPreview" :src="establishment.photo_path.startsWith('http') ? establishment.photo_path : `/storage/${establishment.photo_path}`" alt="Foto actual" class="mt-2 h-32 w-32 rounded-lg border object-cover" />
                        <img v-if="photoPreview" :src="photoPreview" alt="Vista previa" class="mt-2 h-32 w-32 rounded-lg border object-cover" />
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Ubicación</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="address">Dirección *</Label>
                        <Input id="address" v-model="form.address" required />
                    </div>
                    <div class="space-y-1.5"><Label for="province">Provincia</Label><Input id="province" v-model="form.province" /></div>
                    <div class="space-y-1.5"><Label for="canton">Cantón</Label><Input id="canton" v-model="form.canton" /></div>
                    <div class="space-y-1.5"><Label for="parish">Parroquia</Label><Input id="parish" v-model="form.parish" /></div>
                    <div class="space-y-1.5"><Label for="sector">Sector</Label><Input id="sector" v-model="form.sector" /></div>
                    <div class="space-y-1.5"><Label for="latitude">Latitud</Label><Input id="latitude" v-model="form.latitude" type="number" step="any" /></div>
                    <div class="space-y-1.5"><Label for="longitude">Longitud</Label><Input id="longitude" v-model="form.longitude" type="number" step="any" /></div>
                    <div class="sm:col-span-2">
                        <Button type="button" variant="outline" size="sm" :disabled="geoLoading" @click="useCurrentLocation">
                            <Locate class="mr-2 h-4 w-4" />
                            {{ geoLoading ? 'Obteniendo ubicación...' : 'Usar mi ubicación' }}
                        </Button>
                        <p v-if="geoError" class="mt-1 text-xs text-destructive">{{ geoError }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Contacto comercial y horarios</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="commercial_contact_name">Nombre o área responsable</Label>
                        <Input id="commercial_contact_name" v-model="form.commercial_contact_name" />
                    </div>
                    <div class="space-y-1.5"><Label for="commercial_phone">Teléfono</Label><Input id="commercial_phone" v-model="form.commercial_phone" /></div>
                    <div class="space-y-1.5"><Label for="commercial_email">Correo electrónico</Label><Input id="commercial_email" v-model="form.commercial_email" type="email" /></div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="authorized_channel">Medio autorizado de contacto</Label>
                        <Input id="authorized_channel" v-model="form.authorized_channel" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="attention_hours">Horario de atención al público</Label>
                        <textarea id="attention_hours" v-model="form.attention_hours" rows="4" class="w-full rounded-md border border-input bg-background p-3 text-sm"></textarea>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="supplier_attention_hours">Horario de atención a proveedores</Label>
                        <textarea id="supplier_attention_hours" v-model="form.supplier_attention_hours" rows="4" class="w-full rounded-md border border-input bg-background p-3 text-sm"></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Requisitos y condiciones de compra</h2>
                <Separator />
                <div class="space-y-1.5">
                    <Label for="entry_requirements">Requisitos de ingreso</Label>
                    <textarea id="entry_requirements" v-model="form.entry_requirements" rows="4" class="w-full rounded-md border border-input bg-background p-3 text-sm"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-1.5"><Label for="payment_mode">Forma de pago</Label><Input id="payment_mode" v-model="form.purchase_conditions.payment_mode" /></div>
                    <div class="space-y-1.5"><Label for="initial_order">Pedido inicial</Label><Input id="initial_order" v-model="form.purchase_conditions.initial_order" /></div>
                    <div class="space-y-1.5"><Label for="returns">Devoluciones</Label><Input id="returns" v-model="form.purchase_conditions.returns" /></div>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox id="has_refrigeration" :checked="form.has_refrigeration" @update:checked="form.has_refrigeration = $event" />
                    <Label for="has_refrigeration">Tiene refrigeración</Label>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-1.5"><Label for="refrigeration_capacity">Capacidad de refrigeración</Label><Input id="refrigeration_capacity" v-model="form.refrigeration_capacity" /></div>
                    <div class="space-y-1.5"><Label for="weekly_purchase_volume">Volumen semanal</Label><Input id="weekly_purchase_volume" v-model="form.weekly_purchase_volume" type="number" step="any" /></div>
                    <div class="space-y-1.5"><Label for="supply_frequency">Frecuencia de abastecimiento</Label><Input id="supply_frequency" v-model="form.supply_frequency" /></div>
                </div>
            </div>

            <div v-if="form.type === 'supermercado'" class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Decisión de compra (supermercado)</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="supermarket_purchase_mode">¿Dónde se decide la compra?</Label>
                        <select id="supermarket_purchase_mode" v-model="form.supermarket_purchase_mode" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Sin especificar</option>
                            <option value="sucursal">En la sucursal</option>
                            <option value="oficina_central">En oficina central</option>
                        </select>
                    </div>
                    <div class="space-y-1.5"><Label for="central_office_contact">Contacto de oficina central</Label><Input id="central_office_contact" v-model="form.central_office_contact" /></div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Categorías y productos que busca</h2>
                <Separator />
                <div v-if="categorias.length > 0" class="space-y-2">
                    <Label>Categorías que vende</Label>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div v-for="cat in categorias" :key="cat.id" class="flex items-center gap-2">
                            <Checkbox :id="`cat-${cat.id}`" :checked="form.categories.includes(cat.id)" @update:checked="toggleCategory(cat.id)" />
                            <Label :for="`cat-${cat.id}`">{{ cat.name }}</Label>
                        </div>
                    </div>
                </div>
                <Separator />
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label>Productos que busca (necesidades)</Label>
                        <Button type="button" variant="outline" size="sm" @click="addNeed">
                            <Plus class="mr-1 h-4 w-4" />
                            Agregar
                        </Button>
                    </div>
                    <div v-for="(need, index) in form.needs" :key="index" class="space-y-3 rounded-lg border p-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label :for="`need-name-${index}`">Producto buscado *</Label>
                                <Input :id="`need-name-${index}`" v-model="need.product_name" />
                            </div>
                            <div class="space-y-1.5">
                                <Label :for="`need-cat-${index}`">Categoría</Label>
                                <select :id="`need-cat-${index}`" v-model="need.category_id" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                                    <option value="">Sin categoría</option>
                                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1.5"><Label :for="`need-qty-${index}`">Cantidad</Label><Input :id="`need-qty-${index}`" v-model="need.quantity" /></div>
                            <div class="space-y-1.5"><Label :for="`need-freq-${index}`">Frecuencia</Label><Input :id="`need-freq-${index}`" v-model="need.frequency" /></div>
                            <div class="space-y-1.5 sm:col-span-2"><Label :for="`need-notes-${index}`">Notas</Label><Input :id="`need-notes-${index}`" v-model="need.notes" /></div>
                        </div>
                        <Button type="button" variant="ghost" size="sm" @click="removeNeed(index)">
                            <Trash2 class="mr-1 h-4 w-4" />
                            Quitar
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" as-child>
                    <Link :href="establishmentUrl(establishment.slug).value">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                </Button>
            </div>
        </form>
    </div>
</template>
