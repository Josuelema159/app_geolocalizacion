<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Map, Package, Pencil, Thermometer } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type { Product } from '@/types';
import { useTeamRoutes } from '@/composables/useTeamRoutes';

const { productos: productosUrl, mapa: mapaUrl, producto: productoUrl } = useTeamRoutes();

const props = defineProps<{
    product: Product;
    canEdit?: boolean;
}>();
</script>

<template>
    <Head :title="product.name" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="productosUrl">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                        <Badge :variant="product.is_public ? 'default' : 'secondary'">
                            {{ product.is_public ? 'Público' : 'Privado' }}
                        </Badge>
                    </div>
                    <p v-if="product.category" class="text-sm text-muted-foreground">{{ product.category.name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="`${mapaUrl}?categoria=${product.category?.slug ?? ''}`">
                        <Map class="mr-2 h-4 w-4" />
                        Buscar dónde ofrecerlo
                    </Link>
                </Button>
                <Button v-if="canEdit" variant="outline" as-child>
                    <Link :href="`${productoUrl(product.slug).value}/edit`">
                        <Pencil class="mr-2 h-4 w-4" />
                        Editar
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Columna principal -->
            <div class="space-y-4 lg:col-span-2">
                <!-- Información básica -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Package class="h-4 w-4" />
                            Información del producto
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="aspect-video max-h-64 w-full overflow-hidden rounded-lg bg-muted/50 flex items-center justify-center">
                            <img
                                v-if="product.photo_path"
                                :src="product.photo_path.startsWith('http') ? product.photo_path : `/storage/${product.photo_path}`"
                                :alt="product.name"
                                class="h-full w-full object-contain"
                            />
                            <Package v-else class="h-12 w-12 text-muted-foreground/30" />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Presentación</p>
                                <p class="text-sm">{{ product.presentation }}</p>
                            </div>
                            <div v-if="product.category">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Categoría</p>
                                <p class="text-sm">{{ product.category.name }}</p>
                            </div>
                        </div>

                        <div v-if="product.description">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Descripción</p>
                            <p class="text-sm text-muted-foreground">{{ product.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Precios y cantidades -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Precios y cantidades</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-if="product.wholesale_price">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Precio al comercio</p>
                                <p class="text-lg font-semibold">${{ product.wholesale_price.toFixed(2) }}</p>
                            </div>
                            <div v-if="product.suggested_retail_price">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">PVP sugerido</p>
                                <p class="text-lg font-semibold">${{ product.suggested_retail_price.toFixed(2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Pedido mínimo</p>
                                <p class="text-sm">{{ product.minimum_order }} unidades</p>
                            </div>
                            <div v-if="product.weekly_capacity">
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Capacidad semanal</p>
                                <p class="text-sm">{{ product.weekly_capacity }} unidades</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Documentación -->
                <Card v-if="product.documentation?.length">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileText class="h-4 w-4" />
                            Documentación disponible
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="list-inside list-disc space-y-1 text-sm">
                            <li v-for="(doc, index) in product.documentation" :key="index">{{ doc }}</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <!-- Columna lateral -->
            <div class="space-y-4">
                <!-- Almacenamiento -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Thermometer class="h-4 w-4" />
                            Almacenamiento
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="product.shelf_life_days">
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Vida útil</p>
                            <p class="text-sm">{{ product.shelf_life_days }} días</p>
                        </div>
                        <div v-if="product.storage_conditions">
                            <Separator class="my-2" />
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Condiciones</p>
                            <p class="text-sm">{{ product.storage_conditions }}</p>
                        </div>
                        <div v-if="product.required_temperature_min !== null || product.required_temperature_max !== null">
                            <Separator class="my-2" />
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Temperatura requerida</p>
                            <p class="text-sm">
                                {{ product.required_temperature_min ?? '?' }} °C a {{ product.required_temperature_max ?? '?' }} °C
                            </p>
                        </div>
                        <div v-if="product.delivery_frequency">
                            <Separator class="my-2" />
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Frecuencia de entrega</p>
                            <p class="text-sm">{{ product.delivery_frequency }}</p>
                        </div>
                        <p
                            v-if="!product.shelf_life_days && !product.storage_conditions && !product.delivery_frequency && product.required_temperature_min === null && product.required_temperature_max === null"
                            class="text-sm text-muted-foreground"
                        >
                            Sin datos de almacenamiento.
                        </p>
                    </CardContent>
                </Card>

                <!-- Cobertura -->
                <Card v-if="product.coverage_zones && product.coverage_zones.length > 0">
                    <CardHeader>
                        <CardTitle class="text-base">Zonas de cobertura</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="zone in product.coverage_zones"
                                :key="zone"
                                variant="outline"
                            >
                                {{ zone }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
