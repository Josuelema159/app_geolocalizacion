<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { Category, Product } from '@/types';

const props = defineProps<{
    product: Product;
    categorias: Category[];
}>();

const form = useForm({
    name: props.product.name,
    category_id: props.product.category?.id ?? ('' as string | number),
    description: props.product.description ?? '',
    presentation: props.product.presentation,
    wholesale_price: props.product.wholesale_price ?? ('' as string | number),
    suggested_retail_price: props.product.suggested_retail_price ?? ('' as string | number),
    minimum_order: props.product.minimum_order as string | number,
    weekly_capacity: props.product.weekly_capacity ?? ('' as string | number),
    shelf_life_days: props.product.shelf_life_days ?? ('' as string | number),
    storage_conditions: props.product.storage_conditions ?? '',
    coverage_zones: props.product.coverage_zones?.join(', ') ?? '',
    delivery_frequency: props.product.delivery_frequency ?? '',
    is_public: props.product.is_public,
});

function submit() {
    form.patch(`/productos/${props.product.slug}`);
}
</script>

<template>
    <Head :title="`Editar: ${product.name}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/productos/${product.slug}`">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Volver
                </Link>
            </Button>
            <div>
                <h1 class="text-2xl font-semibold">Editar producto</h1>
                <p class="text-sm text-muted-foreground">{{ product.name }}</p>
            </div>
        </div>

        <form class="mx-auto w-full max-w-2xl space-y-6" @submit.prevent="submit">
            <!-- Información básica -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Información básica</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="name">Nombre del producto *</Label>
                        <Input id="name" v-model="form.name" placeholder="Ej: Mermelada de mora 250g" required />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="category_id">Categoría</Label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">Sin categoría</option>
                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="text-xs text-destructive">{{ form.errors.category_id }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="presentation">Presentación *</Label>
                        <Input id="presentation" v-model="form.presentation" placeholder="Ej: Frasco 250g" required />
                        <p v-if="form.errors.presentation" class="text-xs text-destructive">{{ form.errors.presentation }}</p>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Describe brevemente el producto..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="is_public" :checked="form.is_public" @update:checked="form.is_public = $event" />
                        <Label for="is_public">Producto visible públicamente</Label>
                    </div>
                </div>
            </div>

            <!-- Precios y cantidades -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Precios y cantidades</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="wholesale_price">Precio al comercio (USD)</Label>
                        <Input id="wholesale_price" v-model="form.wholesale_price" type="number" step="0.01" min="0" placeholder="0.00" />
                        <p v-if="form.errors.wholesale_price" class="text-xs text-destructive">{{ form.errors.wholesale_price }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="suggested_retail_price">Precio sugerido al consumidor (USD)</Label>
                        <Input id="suggested_retail_price" v-model="form.suggested_retail_price" type="number" step="0.01" min="0" placeholder="0.00" />
                        <p v-if="form.errors.suggested_retail_price" class="text-xs text-destructive">{{ form.errors.suggested_retail_price }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="minimum_order">Pedido mínimo (unidades)</Label>
                        <Input id="minimum_order" v-model="form.minimum_order" type="number" min="1" placeholder="1" />
                        <p v-if="form.errors.minimum_order" class="text-xs text-destructive">{{ form.errors.minimum_order }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="weekly_capacity">Capacidad semanal (unidades)</Label>
                        <Input id="weekly_capacity" v-model="form.weekly_capacity" type="number" min="0" placeholder="Opcional" />
                        <p v-if="form.errors.weekly_capacity" class="text-xs text-destructive">{{ form.errors.weekly_capacity }}</p>
                    </div>
                </div>
            </div>

            <!-- Almacenamiento y distribución -->
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <h2 class="font-semibold text-base">Almacenamiento y distribución</h2>
                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="shelf_life_days">Vida útil (días)</Label>
                        <Input id="shelf_life_days" v-model="form.shelf_life_days" type="number" min="0" placeholder="Opcional" />
                        <p v-if="form.errors.shelf_life_days" class="text-xs text-destructive">{{ form.errors.shelf_life_days }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="delivery_frequency">Frecuencia de entrega</Label>
                        <Input id="delivery_frequency" v-model="form.delivery_frequency" placeholder="Ej: Semanal, quincenal" />
                        <p v-if="form.errors.delivery_frequency" class="text-xs text-destructive">{{ form.errors.delivery_frequency }}</p>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="storage_conditions">Condiciones de almacenamiento</Label>
                        <Input id="storage_conditions" v-model="form.storage_conditions" placeholder="Ej: Lugar fresco y seco, refrigerado" />
                        <p v-if="form.errors.storage_conditions" class="text-xs text-destructive">{{ form.errors.storage_conditions }}</p>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="coverage_zones">Zonas de cobertura</Label>
                        <Input id="coverage_zones" v-model="form.coverage_zones" placeholder="Ej: Latacunga, Salcedo, Pujilí" />
                        <p class="text-xs text-muted-foreground">Separadas por comas</p>
                        <p v-if="form.errors.coverage_zones" class="text-xs text-destructive">{{ form.errors.coverage_zones }}</p>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" as-child>
                    <Link :href="`/productos/${product.slug}`">Cancelar</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                </Button>
            </div>
        </form>
    </div>
</template>
