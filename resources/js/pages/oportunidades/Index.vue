<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarClock, ChevronRight, MessageSquarePlus, TrendingUp } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Opportunity, PaginatedData } from '@/types';
import { useTeamRoutes } from '@/composables/useTeamRoutes';

const { slug, establecimiento, establecimientos: establecimientosUrl, mapa: mapaUrl } = useTeamRoutes();
const oportunidadUrl = (id: number) => `/${slug.value}/oportunidades/${id}`;

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    oportunidades: PaginatedData<Opportunity>;
    estados: string[];
}>();

type EstadoConfig = {
    label: string;
    colorClass: string;
    badgeClass: string;
};

const estadosConfig: Record<string, EstadoConfig> = {
    por_contactar: {
        label: 'Por contactar',
        colorClass: 'border-l-gray-400',
        badgeClass: 'bg-gray-100 text-gray-700',
    },
    contactado: {
        label: 'Contactado',
        colorClass: 'border-l-blue-400',
        badgeClass: 'bg-blue-100 text-blue-700',
    },
    muestra_entregada: {
        label: 'Muestra entregada',
        colorClass: 'border-l-yellow-400',
        badgeClass: 'bg-yellow-100 text-yellow-700',
    },
    en_negociacion: {
        label: 'En negociación',
        colorClass: 'border-l-orange-400',
        badgeClass: 'bg-orange-100 text-orange-700',
    },
    primer_pedido: {
        label: 'Primer pedido',
        colorClass: 'border-l-lime-400',
        badgeClass: 'bg-lime-100 text-lime-700',
    },
    punto_venta_activo: {
        label: 'Punto de venta activo',
        colorClass: 'border-l-green-500',
        badgeClass: 'bg-green-100 text-green-700',
    },
    no_concretada: {
        label: 'No concretada',
        colorClass: 'border-l-red-400',
        badgeClass: 'bg-red-100 text-red-700',
    },
};

// Track which opportunity has the status dropdown open
const editingStatus = ref<number | null>(null);
const notesForm = useForm({ private_notes: '' });
const editingNotes = ref<number | null>(null);

function startEditingNotes(op: Opportunity) {
    editingNotes.value = op.id;
    notesForm.private_notes = op.private_notes ?? '';
}

function saveNotes(op: Opportunity) {
    notesForm.patch(oportunidadUrl(op.id), {
        onSuccess: () => {
            editingNotes.value = null;
        },
    });
}

function changeStatus(op: Opportunity, newStatus: string) {
    router.patch(
        oportunidadUrl(op.id),
        { status: newStatus },
        {
            preserveState: true,
            onSuccess: () => {
                editingStatus.value = null;
            },
        },
    );
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('es-EC', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function truncate(text: string | null, length = 80): string {
    if (!text) return '';
    return text.length > length ? text.slice(0, length) + '...' : text;
}

function getLinks(): PaginationLink[] {
    return props.oportunidades.links ?? [];
}
</script>

<template>
    <Head title="Mis Oportunidades" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Mis Oportunidades</h1>
                <p class="text-sm text-muted-foreground">Seguimiento de establecimientos donde ofreces tus productos</p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="establecimientosUrl">
                    <TrendingUp class="mr-2 h-4 w-4" />
                    Buscar establecimientos
                </Link>
            </Button>
        </div>

        <!-- Resumen de estados -->
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
            <div
                v-for="(config, estado) in estadosConfig"
                :key="estado"
                class="rounded-lg border bg-card p-2 text-center"
            >
                <p class="text-lg font-semibold">
                    {{ oportunidades.data.filter((o) => o.status === estado).length }}
                </p>
                <p class="text-xs text-muted-foreground leading-tight">{{ config.label }}</p>
            </div>
        </div>

        <!-- Sin oportunidades -->
        <div v-if="oportunidades.data.length === 0" class="flex flex-1 items-center justify-center">
            <div class="max-w-sm space-y-4 rounded-xl border bg-card p-8 text-center">
                <TrendingUp class="mx-auto h-12 w-12 text-muted-foreground/40" />
                <div>
                    <h2 class="font-semibold text-lg">Sin oportunidades aún</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Visita el mapa o el listado de establecimientos y guarda las que te interesen.
                    </p>
                </div>
                <Button as-child class="w-full">
                    <Link :href="mapaUrl">Ver mapa de establecimientos</Link>
                </Button>
            </div>
        </div>

        <!-- Lista agrupada por estado -->
        <template v-else>
            <div
                v-for="(config, estado) in estadosConfig"
                :key="estado"
            >
                <template v-if="oportunidades.data.filter((o) => o.status === estado).length > 0">
                    <h2 class="mb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                        {{ config.label }}
                        <span class="ml-1 text-foreground">
                            ({{ oportunidades.data.filter((o) => o.status === estado).length }})
                        </span>
                    </h2>
                    <div class="mb-4 space-y-2">
                        <Card
                            v-for="op in oportunidades.data.filter((o) => o.status === estado)"
                            :key="op.id"
                            class="border-l-4 transition-shadow hover:shadow-sm"
                            :class="config.colorClass"
                        >
                            <CardHeader class="pb-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <CardTitle class="text-sm font-medium">
                                            {{ op.establishment?.name ?? 'Establecimiento desconocido' }}
                                        </CardTitle>
                                        <p class="text-xs text-muted-foreground">
                                            {{ op.establishment?.canton }}, {{ op.establishment?.province }}
                                        </p>
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium shrink-0" :class="config.badgeClass">
                                        {{ config.label }}
                                    </span>
                                </div>
                            </CardHeader>

                            <CardContent class="pt-0 space-y-3">
                                <!-- Notas privadas -->
                                <div v-if="editingNotes === op.id" class="space-y-2">
                                    <textarea
                                        v-model="notesForm.private_notes"
                                        rows="2"
                                        placeholder="Notas privadas..."
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    />
                                    <div class="flex gap-2">
                                        <Button size="sm" :disabled="notesForm.processing" @click="saveNotes(op)">Guardar</Button>
                                        <Button size="sm" variant="ghost" @click="editingNotes = null">Cancelar</Button>
                                    </div>
                                </div>
                                <p v-else-if="op.private_notes" class="text-xs text-muted-foreground">
                                    {{ truncate(op.private_notes) }}
                                </p>

                                <!-- Próxima gestión -->
                                <div v-if="op.next_follow_up_at" class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <CalendarClock class="h-3.5 w-3.5" />
                                    Próxima gestión: {{ formatDate(op.next_follow_up_at) }}
                                </div>

                                <!-- Acciones -->
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <!-- Cambiar estado -->
                                    <select
                                        class="h-7 rounded-md border border-input bg-background px-2 text-xs"
                                        :value="op.status"
                                        @change="changeStatus(op, ($event.target as HTMLSelectElement).value)"
                                    >
                                        <option v-for="(cfg, est) in estadosConfig" :key="est" :value="est">
                                            {{ cfg.label }}
                                        </option>
                                    </select>

                                    <!-- Agregar notas -->
                                    <Button size="sm" variant="ghost" class="h-7 px-2 text-xs" @click="startEditingNotes(op)">
                                        <MessageSquarePlus class="mr-1 h-3.5 w-3.5" />
                                        Notas
                                    </Button>

                                    <!-- Ver establecimiento -->
                                    <Button v-if="op.establishment" size="sm" variant="ghost" class="h-7 px-2 text-xs" as-child>
                                        <Link :href="establecimiento(op.establishment.slug).value">
                                            Ver ficha
                                            <ChevronRight class="ml-1 h-3.5 w-3.5" />
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </template>
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
