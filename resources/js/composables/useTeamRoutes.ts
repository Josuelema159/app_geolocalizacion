import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useTeamRoutes() {
    const page = usePage();
    const slug = computed(() => page.props.currentTeam?.slug ?? '');

    const r = (path: string) => computed(() => `/${slug.value}${path}`);

    return {
        slug,
        mapa:              r('/mapa'),
        establecimientos:  r('/establecimientos'),
        establecimientoCreate: r('/establecimientos/create'),
        productos:         r('/productos'),
        productoCreate:    r('/productos/create'),
        oportunidades:     r('/oportunidades'),
        miPerfil:          r('/mi-perfil'),
        establecimiento:   (id: string | number) => computed(() => `/${slug.value}/establecimientos/${id}`),
        producto:          (id: string | number) => computed(() => `/${slug.value}/productos/${id}`),
    };
}
