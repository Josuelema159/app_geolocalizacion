<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Trash2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { EntrepreneurProfile, PaginatedData, Product } from '@/types';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    productos: PaginatedData<Product>;
    perfil: EntrepreneurProfile | null;
}>();

function deleteProduct(product: Product) {
    if (confirm(`¿Eliminar el producto "${product.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/productos/${product.slug}`);
    }
}

function getLinks(): PaginationLink[] {
    return props.productos.links ?? [];
}
</script>

<template>
    <Head title="Mis Productos" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Mis Productos</h1>
                <p class="text-sm text-muted-foreground">Gestiona tu catálogo de productos</p>
            </div>
            <Button v-if="perfil" as-child>
                <Link :href="productoCreate.value">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo producto
                </Link>
            </Button>
        </div>

        <!-- Sin perfil -->
        <div v-if="!perfil" class="flex flex-1 items-center justify-center">
            <div class="max-w-sm space-y-4 rounded-xl border bg-card p-8 text-center">
                <Package class="mx-auto h-12 w-12 text-muted-foreground/40" />
                <div>
                    <h2 class="font-semibold text-lg">Configura tu perfil primero</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Necesitas crear tu perfil de emprendedor antes de registrar productos.
                    </p>
                </div>
                <Button as-child class="w-full">
                    <Link :href="miPerfilUrl.value">Crear perfil de emprendedor</Link>
                </Button>
            </div>
        </div>

        <!-- Lista de productos -->
        <template v-else>
            <!-- Sin productos -->
            <div v-if="productos.data.length === 0" class="flex flex-1 items-center justify-center">
                <div class="max-w-sm space-y-4 rounded-xl border bg-card p-8 text-center">
                    <Package class="mx-auto h-12 w-12 text-muted-foreground/40" />
                    <div>
                        <h2 class="font-semibold text-lg">Sin productos aún</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Registra tu primer producto para empezar a conectar con establecimientos.
                        </p>
                    </div>
                    <Button as-child class="w-full">
                        <Link :href="productoCreate.value">
                            <Plus class="mr-2 h-4 w-4" />
                            Registrar producto
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Grid de productos -->
            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div
                    v-for="product in productos.data"
                    :key="product.id"
                    class="group rounded-xl border bg-card overflow-hidden hover:shadow-md transition-shadow"
                >
                    <!-- Foto placeholder -->
                    <div class="aspect-video bg-muted/50 flex items-center justify-center">
                        <Package class="h-10 w-10 text-muted-foreground/30" />
                    </div>

                    <div class="p-4 space-y-3">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium text-sm leading-tight">{{ product.name }}</h3>
                                <Badge
                                    :variant="product.is_public ? 'default' : 'secondary'"
                                    class="shrink-0 text-xs"
                                >
                                    {{ product.is_public ? 'Público' : 'Privado' }}
                                </Badge>
                            </div>
                            <p v-if="product.category" class="text-xs text-muted-foreground mt-0.5">
                                {{ product.category.name }}
                            </p>
                        </div>

                        <div class="text-xs text-muted-foreground space-y-0.5">
                            <p>Presentación: <span class="text-foreground">{{ product.presentation }}</span></p>
                            <p v-if="product.wholesale_price">
                                Precio comercio:
                                <span class="text-foreground font-medium">${{ product.wholesale_price.toFixed(2) }}</span>
                            </p>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <Button variant="ghost" size="sm" class="flex-1" as-child>
                                <Link :href="`/productos/${product.slug}`">Ver</Link>
                            </Button>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/productos/${product.slug}/edit`">
                                    <Pencil class="h-3.5 w-3.5" />
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" class="text-destructive hover:text-destructive" @click="deleteProduct(product)">
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="getLinks().length > 3" class="flex items-center justify-center gap-1">
                <template v-for="link in getLinks()" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-muted"
                        :class="link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : ''"
                        preserve-state
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="cursor-default rounded-md px-3 py-1.5 text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </template>
    </div>
</template>
