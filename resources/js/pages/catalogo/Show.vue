<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, Mail, MapPin, Package, Phone, Store } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { EntrepreneurProfile, Product } from '@/types';

const props = defineProps<{
    perfil: EntrepreneurProfile;
    productos: Product[];
}>();

function photoUrl(path: string | null): string {
    if (!path) return '';
    return path.startsWith('http') ? path : `/storage/${path}`;
}

const pdfUrl = `/catalogo/${props.perfil.slug}/pdf`;
</script>

<template>
    <Head :title="`Catálogo de ${perfil.business_name}`" />

    <div class="min-h-screen bg-muted/30">
        <header class="border-b bg-background">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10">
                        <Store class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold">{{ perfil.business_name }}</h1>
                        <p class="text-sm text-muted-foreground">Catálogo comercial</p>
                    </div>
                </div>
                <Button as-child>
                    <a :href="pdfUrl">
                        <Download class="mr-2 h-4 w-4" />
                        Descargar ficha PDF
                    </a>
                </Button>
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-6 px-4 py-8">
            <section class="rounded-xl border bg-card p-6">
                <p v-if="perfil.description" class="text-sm leading-relaxed">{{ perfil.description }}</p>
                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                    <span v-if="perfil.phone" class="flex items-center gap-1">
                        <Phone class="h-4 w-4" /> {{ perfil.phone }}
                    </span>
                    <span v-if="perfil.whatsapp" class="flex items-center gap-1">
                        <Mail class="h-4 w-4" /> WhatsApp: {{ perfil.whatsapp }}
                    </span>
                    <span v-if="perfil.coverage_zones?.length" class="flex items-center gap-1">
                        <MapPin class="h-4 w-4" /> {{ perfil.coverage_zones.join(', ') }}
                    </span>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-lg font-semibold">Productos ({{ productos.length }})</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="product in productos" :key="product.id" class="overflow-hidden rounded-xl border bg-card">
                        <div class="flex h-40 items-center justify-center bg-muted/50">
                            <img
                                v-if="product.photo_path"
                                :src="photoUrl(product.photo_path)"
                                :alt="product.name"
                                class="h-full w-full object-cover"
                            />
                            <Package v-else class="h-12 w-12 text-muted-foreground/30" />
                        </div>
                        <div class="space-y-2 p-4 text-sm">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium">{{ product.name }}</h3>
                                <Badge v-if="product.category" variant="outline">{{ product.category.name }}</Badge>
                            </div>
                            <p class="text-xs text-muted-foreground">{{ product.presentation }}</p>
                            <p v-if="product.description" class="text-xs">{{ product.description }}</p>
                            <div class="space-y-0.5 border-t pt-2 text-xs">
                                <p v-if="product.wholesale_price">Precio al comercio: <strong>${{ product.wholesale_price.toFixed(2) }}</strong></p>
                                <p v-if="product.suggested_retail_price">Precio sugerido: <strong>${{ product.suggested_retail_price.toFixed(2) }}</strong></p>
                                <p>Pedido mínimo: <strong>{{ product.minimum_order }}</strong></p>
                                <p v-if="product.weekly_capacity">Capacidad semanal: <strong>{{ product.weekly_capacity }}</strong></p>
                                <p v-if="product.shelf_life_days">Vida útil: <strong>{{ product.shelf_life_days }} días</strong></p>
                                <p v-if="product.storage_conditions">Conservación: <strong>{{ product.storage_conditions }}</strong></p>
                                <p v-if="product.coverage_zones?.length">Zonas: <strong>{{ product.coverage_zones.join(', ') }}</strong></p>
                                <p v-if="product.delivery_frequency">Entrega: <strong>{{ product.delivery_frequency }}</strong></p>
                            </div>
                            <div v-if="product.documentation?.length" class="border-t pt-2">
                                <p class="mb-1 text-xs font-medium">Documentación</p>
                                <ul class="list-inside list-disc text-xs text-muted-foreground">
                                    <li v-for="(doc, index) in product.documentation" :key="index">{{ doc }}</li>
                                </ul>
                            </div>
                        </div>
                    </article>
                </div>
                <p v-if="!productos.length" class="rounded-xl border bg-card p-8 text-center text-sm text-muted-foreground">
                    Este emprendedor aún no ha publicado productos.
                </p>
            </section>
        </main>
    </div>
</template>
