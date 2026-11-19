<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { Category } from '@/types';
import { useTeamRoutes } from '@/composables/useTeamRoutes';

const { productos: productosUrl } = useTeamRoutes();

const props = defineProps<{
    categorias: Category[];
}>();

const photoPreview = ref('');

const form = useForm({
    name: '',
    category_id: '' as string | number,
    photo: null as File | null,
    description: '',
    presentation: '',
    wholesale_price: '' as string | number,
    suggested_retail_price: '' as string | number,
    minimum_order: 1 as string | number,
    weekly_capacity: '' as string | number,
    shelf_life_days: '' as string | number,
    storage_conditions: '',
    required_temperature_min: '' as string | number,
    required_temperature_max: '' as string | number,
    coverage_zones: '',
    delivery_frequency: '',
    documentation: [''] as string[],
    is_public: true,
});

function onPhotoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : '';
}

function submit() {
    form.post(productosUrl.value, { forceFormData: true });
}
</script>

<template>
    <Head title="Nuevo producto" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="productosUrl">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Volver
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">Nuevo producto</h1>
                <p class="text-sm text-muted-foreground">Registra un producto en tu catálogo</p>
            </div>
        </div>

        <form class="mx-auto w-full max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Información básica</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="name">Nombre del producto *</Label>
                        <Input id="name" v-model="form.name" placeholder="Ej: Mermelada de mora 250g" required />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="category_id">Categoría</Label>
                        <select id="category_id" v-model="form.category_id" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">Sin categoría</option>
                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="presentation">Presentación *</Label>
                        <Input id="presentation" v-model="form.presentation" placeholder="Ej: Frasco 250g" required />
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="description">Descripción</Label>
                        <textarea id="description" v-model="form.description" rows="3" class="w-full rounded-md border border-input bg-background p-3 text-sm"></textarea>
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="photo">Fotografía del producto</Label>
                        <Input id="photo" type="file" accept="image/*" @change="onPhotoChange" />
                        <img v-if="photoPreview" :src="photoPreview" alt="Vista previa" class="mt-2 h-32 w-32 rounded-lg border object-cover" />
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="is_public" :checked="form.is_public" @update:checked="form.is_public = $event" />
                        <Label for="is_public">Producto visible en el catálogo público</Label>
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Precios y cantidades</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="wholesale_price">Precio al comercio (USD)</Label>
                        <Input id="wholesale_price" v-model="form.wholesale_price" type="number" step="0.01" min="0" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="suggested_retail_price">Precio sugerido al consumidor (USD)</Label>
                        <Input id="suggested_retail_price" v-model="form.suggested_retail_price" type="number" step="0.01" min="0" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="minimum_order">Pedido mínimo (unidades)</Label>
                        <Input id="minimum_order" v-model="form.minimum_order" type="number" min="1" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="weekly_capacity">Capacidad de abastecimiento semanal</Label>
                        <Input id="weekly_capacity" v-model="form.weekly_capacity" type="number" step="any" />
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Vida útil y conservación</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="shelf_life_days">Vida útil (días)</Label>
                        <Input id="shelf_life_days" v-model="form.shelf_life_days" type="number" min="1" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="storage_conditions">Condiciones de conservación</Label>
                        <Input id="storage_conditions" v-model="form.storage_conditions" placeholder="Ej: Refrigerado 2-8 °C" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="temp_min">Temperatura mínima (°C)</Label>
                        <Input id="temp_min" v-model="form.required_temperature_min" type="number" step="0.1" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="temp_max">Temperatura máxima (°C)</Label>
                        <Input id="temp_max" v-model="form.required_temperature_max" type="number" step="0.1" />
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-card p-6">
                <h2 class="text-base font-semibold">Entrega y documentación</h2>
                <Separator />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="coverage_zones">Zonas de entrega (separadas por coma)</Label>
                        <Input id="coverage_zones" v-model="form.coverage_zones" placeholder="Latacunga, Quito, Ambato" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="delivery_frequency">Frecuencia de entrega</Label>
                        <Input id="delivery_frequency" v-model="form.delivery_frequency" placeholder="Ej: Semanal" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label>Documentación disponible</Label>
                        <Button type="button" variant="outline" size="sm" @click="form.documentation.push('')">
                            <Plus class="mr-1 h-4 w-4" />
                            Agregar
                        </Button>
                    </div>
                    <div v-for="(doc, index) in form.documentation" :key="index" class="flex gap-2">
                        <Input v-model="form.documentation[index]" placeholder="Ej: RUC, registro sanitario, notificación sanitaria" />
                        <Button type="button" variant="ghost" size="sm" @click="form.documentation.splice(index, 1)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" as-child>
                    <Link :href="productosUrl">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Registrar producto' }}
                </Button>
            </div>
        </form>
    </div>
</template>
