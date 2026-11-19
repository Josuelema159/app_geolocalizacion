<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { User } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { EntrepreneurProfile } from '@/types';

const props = defineProps<{
    perfil: EntrepreneurProfile | null;
}>();

const isEditing = ref(!props.perfil);

const form = useForm({
    business_name: props.perfil?.business_name ?? '',
    description: props.perfil?.description ?? '',
    phone: props.perfil?.phone ?? '',
    whatsapp: props.perfil?.whatsapp ?? '',
    coverage_zones: props.perfil?.coverage_zones?.join(', ') ?? '',
});

function submit() {
    if (props.perfil) {
        form.patch('/mi-perfil', {
            onSuccess: () => {
                isEditing.value = false;
            },
        });
    } else {
        form.post('/mi-perfil');
    }
}

function startEditing() {
    form.business_name = props.perfil?.business_name ?? '';
    form.description = props.perfil?.description ?? '';
    form.phone = props.perfil?.phone ?? '';
    form.whatsapp = props.perfil?.whatsapp ?? '';
    form.coverage_zones = props.perfil?.coverage_zones?.join(', ') ?? '';
    isEditing.value = true;
}
</script>

<template>
    <Head title="Mi Perfil de Emprendedor" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Mi Perfil de Emprendedor</h1>
                <p class="text-sm text-muted-foreground">
                    {{ perfil ? 'Gestiona la información de tu emprendimiento' : 'Configura tu perfil para empezar' }}
                </p>
            </div>
            <Button v-if="perfil && !isEditing" variant="outline" @click="startEditing">
                Editar perfil
            </Button>
        </div>

        <!-- Vista del perfil -->
        <div v-if="perfil && !isEditing" class="mx-auto w-full max-w-2xl space-y-4">
            <div class="rounded-xl border bg-card p-6 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                        <User class="h-8 w-8 text-muted-foreground" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold">{{ perfil.business_name }}</h2>
                        <p v-if="perfil.description" class="text-sm text-muted-foreground mt-0.5">{{ perfil.description }}</p>
                    </div>
                </div>

                <Separator />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-if="perfil.phone">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Teléfono</p>
                        <p class="text-sm">{{ perfil.phone }}</p>
                    </div>
                    <div v-if="perfil.whatsapp">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">WhatsApp</p>
                        <p class="text-sm">{{ perfil.whatsapp }}</p>
                    </div>
                    <div v-if="perfil.coverage_zones && perfil.coverage_zones.length > 0" class="sm:col-span-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Zonas de cobertura</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="zone in perfil.coverage_zones"
                                :key="zone"
                                class="rounded-full border px-2.5 py-0.5 text-xs"
                            >
                                {{ zone }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario (crear o editar) -->
        <div v-else class="mx-auto w-full max-w-2xl">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-xl border bg-card p-6 space-y-4">
                    <h2 class="font-semibold text-base">
                        {{ perfil ? 'Editar perfil' : 'Crear perfil de emprendedor' }}
                    </h2>
                    <Separator />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="business_name">Nombre del emprendimiento *</Label>
                            <Input
                                id="business_name"
                                v-model="form.business_name"
                                placeholder="Ej: Productos Naturales Cotopaxi"
                                required
                            />
                            <p v-if="form.errors.business_name" class="text-xs text-destructive">{{ form.errors.business_name }}</p>
                        </div>

                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="description">Descripción</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Cuéntanos sobre tu emprendimiento..."
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="phone">Teléfono</Label>
                            <Input id="phone" v-model="form.phone" placeholder="Ej: 0987654321" />
                            <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="whatsapp">WhatsApp</Label>
                            <Input id="whatsapp" v-model="form.whatsapp" placeholder="Ej: 0987654321" />
                            <p v-if="form.errors.whatsapp" class="text-xs text-destructive">{{ form.errors.whatsapp }}</p>
                        </div>

                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="coverage_zones">Zonas de cobertura</Label>
                            <Input
                                id="coverage_zones"
                                v-model="form.coverage_zones"
                                placeholder="Ej: Latacunga, Salcedo, Pujilí"
                            />
                            <p class="text-xs text-muted-foreground">Separadas por comas</p>
                            <p v-if="form.errors.coverage_zones" class="text-xs text-destructive">{{ form.errors.coverage_zones }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Button v-if="perfil" type="button" variant="outline" @click="isEditing = false">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : perfil ? 'Guardar cambios' : 'Crear perfil' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
