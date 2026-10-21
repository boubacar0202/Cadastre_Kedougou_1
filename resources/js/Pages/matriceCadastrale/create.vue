<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BaseInput from "@/Components/BaseInput.vue";
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { debounce } from 'lodash';
import { toast } from "@/Components/AppToast";
import DeleteModal from "@/Components/DeleteModal.vue";
import MazBtn from "maz-ui/components/MazBtn";
import axios from "axios";
import * as XLSX from 'xlsx';

const props = defineProps({
    terrains: {
        type: [Object, Array],
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15
        })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    totalGeneral: {
        type: Number,
        default: 0
    }
});

const filters = ref({
    numero: props.filters?.numero || '',
    nicad: props.filters?.nicad || '',
    titulaire: props.filters?.titulaire || '',
    commune: props.filters?.commune || ''
});

const showDeleteModal = ref(false);
const selectedTerrain = ref(null);
const loadingDelete = ref(false);
const exporting = ref(false);

const hasNicad = (terrain) => String(terrain?.txt_nicad ?? "").trim() !== "";

const pageMeta = computed(() => props.terrains?.meta || props.terrains || {});

const pageLinks = computed(() => {
    const links = props.terrains?.meta?.links || props.terrains?.links;
    return Array.isArray(links) ? links : [];
});

const prevUrl = computed(() => props.terrains?.links?.prev || props.terrains?.prev_page_url || null);
const nextUrl = computed(() => props.terrains?.links?.next || props.terrains?.next_page_url || null);

const displayData = computed(() => {
    const rows = Array.isArray(props.terrains)
        ? props.terrains
        : (props.terrains?.data || []);

    return rows.filter(hasNicad);
});

const totalEnregistrement = computed(() => props.totalGeneral || pageMeta.value.total || displayData.value.length);
const totalFiltre = computed(() => pageMeta.value.total || displayData.value.length);
const hasData = computed(() => displayData.value.length > 0);

const getRowNumber = (index) => {
    const current = pageMeta.value.current_page || 1;
    const perPage = pageMeta.value.per_page || 15;
    return (current - 1) * perPage + index + 1;
};

const debouncedSearch = debounce((page = 1) => {
    router.get(route('matrice.create'), {
        ...filters.value,
        page: page
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 1500);

watch(filters, () => {
    debouncedSearch(1);
}, { deep: true });

const goToPage = (url) => {
    if (!url) return;

    try {
        const urlParams = new URLSearchParams(url.split('?')[1]);
        const page = urlParams.get('page');

        if (page) {
            router.get(route('matrice.create'), {
                ...filters.value,
                page: page
            }, {
                preserveState: true,
                replace: true,
                preserveScroll: true
            });
        }
    } catch (error) {
        console.error('Erreur de pagination:', error);
    }
};

const prevPage = () => {
    if (prevUrl.value) goToPage(prevUrl.value);
};

const nextPage = () => {
    if (nextUrl.value) goToPage(nextUrl.value);
};

const supprimerTerrain = (terrain) => {
    if (!terrain?.id) return;
    selectedTerrain.value = terrain;
    showDeleteModal.value = true;
};

const confirmerSuppression = (codes) => {
    loadingDelete.value = true;
    router.delete(route('matrice.destroy', selectedTerrain.value.id), {
        data: {
            code: codes?.adminCode
        },
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedTerrain.value = null;
            toast.success('Terrain supprimé avec succès');
            debouncedSearch(pageMeta.value.current_page || 1);
        },
        onError: () => {
            toast.error('Erreur lors de la suppression');
        },
        onFinish: () => {
            loadingDelete.value = false;
        }
    });
};

const resetFilters = () => {
    filters.value = {
        numero: '',
        nicad: '',
        titulaire: '',
        commune: ''
    };
};

onMounted(() => {
    if (!displayData.value.length && !pageMeta.value.total) {
        debouncedSearch(1);
    }
});

const getNicadBadgeClass = (terrain) => {
    const nicad = terrain?.txt_nicad;

    if (!nicad || nicad === 'NON' || nicad === 'null' || String(nicad).toLowerCase() === 'non') {
        return 'px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs';
    }

    if (String(nicad).length < 9) {
        return 'px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full text-xs';
    }

    return 'px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs';
};

const rowsToExport = (rows) => rows.filter(hasNicad).map((terrain, index) => ({
    'N°': index + 1,
    'N° dossier': terrain.dossier?.txt_num_dossier || '',
    'Region': terrain.region?.slt_region || '',
    'Departement': terrain.departement?.slt_departement || '',
    'Commune': terrain.commune?.slt_commune || '',
    'Lotissement': terrain.txt_lotissement || '',
    'Section': terrain.txt_num_section || '',
    'N° Lot': terrain.txt_num_lotissement || '',
    'N° Parcelle': terrain.txt_num_parcelle || '',
    'NICAD': terrain.txt_nicad || 'NON',
    'Surface': terrain.nbr_surface || '',
    'Type Batie': terrain.evaluations_batis?.currentCat || '',
    'Niveau': terrain.evaluations_batis?.nbr_niveauPR || '',
    'Titre Mere': terrain.references_cadastrales?.txt_titre_mere || '',
    'Titulaire': terrain.titulaire?.slt_titulaire || '',
    'Prénom': terrain.titulaire?.txt_prenom || '',
    'Nom': terrain.titulaire?.txt_nom || '',
    'Téléphone': terrain.titulaire?.tel_telephone || '',
    'Email': terrain.titulaire?.eml_email || '',
}));

const exportToExcel = async () => {
    if (exporting.value) return;

    exporting.value = true;
    try {
        const { data } = await axios.get(route('matrice.create'), {
            params: { ...filters.value, export: 1 },
            headers: { Accept: 'application/json' },
        });
        const rows = Array.isArray(data) ? data : (data?.data || []);
        const exportData = rowsToExport(rows);

        if (!exportData.length) {
            toast.error('Aucune donnée à exporter');
            return;
        }

        const worksheet = XLSX.utils.json_to_sheet(exportData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Matrice Cadastrale');

        const columnWidths = Object.keys(exportData[0] || {}).map((key) => {
            const maxLength = Math.max(
                key.length,
                ...exportData.map((row) => String(row[key] ?? '').length)
            );
            return { wch: Math.min(maxLength + 2, 40) };
        });
        worksheet['!cols'] = columnWidths;

        const dateStr = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(workbook, `matrice_cadastrale_${dateStr}.xlsx`);
        toast.success('Export Excel réussi');
    } catch (error) {
        console.error('Erreur export Excel:', error);
        toast.error("Erreur lors de l'export");
    } finally {
        exporting.value = false;
    }
};
</script>

<template>
    <Head title="Matrice Cadastrale">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Matrice Cadastrale
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow rounded-xl">
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4 border-b rounded-t-xl bg-primary">
                            <div class="flex flex-wrap items-center gap-4">
                                <h1 class="text-lg text-white font-semibold">
                                    Matrice Cadastrale
                                </h1>
                                <span class="text-white/80 text-sm">
                                    Total : <span class="font-bold text-white">{{ totalEnregistrement }}</span>
                                </span>
                                <span class="text-white/60 text-xs" v-if="totalFiltre !== totalEnregistrement">
                                    ({{ totalFiltre }} avec filtres)
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <BaseInput v-model="filters.numero" type="text" placeholder="N° dossier" wrapper-class="w-32" />
                                <BaseInput v-model="filters.nicad" type="text" placeholder="NICAD" wrapper-class="w-32" />
                                <BaseInput v-model="filters.titulaire" type="text" placeholder="Titulaire" wrapper-class="w-40" />
                                <BaseInput v-model="filters.commune" type="text" placeholder="Commune" wrapper-class="w-32" />
                                <button
                                    @click="resetFilters"
                                    class="h-8 px-4 bg-white/20 text-white rounded-xl text-sm hover:bg-white/30 transition font-medium"
                                >
                                    ✕ Réinitialiser
                                </button>
                                <button
                                    @click="exportToExcel"
                                    :disabled="exporting || !totalFiltre"
                                    class="h-8 px-4 bg-white/20 text-white rounded-xl text-sm hover:bg-white/30 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ exporting ? 'Export…' : 'Exporter Excel' }}
                                </button>
                            </div>
                        </div>

                        <DeleteModal
                            :show="showDeleteModal"
                            :itemName="selectedTerrain?.txt_num_lotissement || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteModal = $event"
                            @confirm="confirmerSuppression"
                        />

                        <div class="p-4">
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl">
                                <table class="table table-auto w-full text-base text-left text-primary-txt">
                                    <thead
                                        class="sticky top-0 z-10 text-primary-txt text-font-bold text-center uppercase bg-primary-only dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap bg-primary-only"
                                    >
                                        <tr class="h-10">
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N°</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° dossier</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Region</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Depart.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Commune</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lotissement</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Section</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Lot</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Parcelle</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">NICAD</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Surface</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Type Batie</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Niveau</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Titre mère</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Titulaire</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Prénom</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Nom</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Tél.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Email</th>
                                            <th class="px-2 py-2 text-xs text-center bg-primary-only font-semibold whitespace-nowrap">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(terrain, index) in displayData"
                                            :key="terrain.id || index"
                                            class="h-8 text-xs hover:bg-primary hover:text-primary font-semibold text-primary-txt text-center"
                                        >
                                            <td class="sticky left-0 z-8 text-xs border bg-white px-2 py-1 whitespace-nowrap">
                                                {{ getRowNumber(index) }}
                                            </td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap"><span class="font-mono">{{ terrain.dossier?.txt_num_dossier || '' }}</span></td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.region?.slt_region || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.departement?.slt_departement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.commune?.slt_commune || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_lotissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_section || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_lotissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_parcelle || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap font-bold">
                                                <span :class="getNicadBadgeClass(terrain)">
                                                    {{ terrain.txt_nicad || 'NON' }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.nbr_surface || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.evaluations_batis?.currentCat || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.evaluations_batis?.nbr_niveauPR || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.txt_titre_mere || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.slt_titulaire || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_prenom || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_nom || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.tel_telephone || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.eml_email || '' }}</td>
                                            <td class="sticky right-0 z-8 px-2 py-1 border bg-white whitespace-nowrap">
                                                <div class="flex justify-center gap-2">
                                                    <Link :href="`/secretariat/edit/${terrain.id}`">
                                                        <MazBtn
                                                            size="large"
                                                            class="h-6 w-6 text-white bg-gradient-to-r from-green-500 via-green-600
                                                            to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                            focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50
                                                            dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-xs px-5
                                                            py-2.5 text-center me-2 mb-2"
                                                        >
                                                            ✏️
                                                        </MazBtn>
                                                    </Link>
                                                    <MazBtn
                                                        size="large"
                                                        color="danger"
                                                        @click="supprimerTerrain(terrain)"
                                                        :disabled="loadingDelete"
                                                        class="h-6 w-6 text-white bg-gradient-to-r from-red-500 via-red-600
                                                        to-red-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                        focus:ring-red-300 dark:focus:ring-red-800 shadow-lg shadow-red-500/50
                                                        dark:shadow-lg dark:shadow-red-800/80 font-medium rounded-lg text-xs px-5
                                                        py-2.5 text-center me-2 mb-2"
                                                    >
                                                        🗑
                                                    </MazBtn>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr v-if="!hasData">
                                            <td colspan="20" class="text-center py-12 text-primary-txt text-base">
                                                Aucun terrain trouvé
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="p-4 border-t flex flex-wrap justify-between items-center gap-4 bg-gray-50 rounded-b-xl">
                            <div class="flex gap-1 flex-wrap">
                                <button
                                    @click="prevPage"
                                    :disabled="!prevUrl"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-primary-txt"
                                >
                                    ← Précédent
                                </button>

                                <button
                                    v-for="link in pageLinks"
                                    :key="link.label"
                                    @click="goToPage(link.url)"
                                    :disabled="!link.url"
                                    :class="[
                                        'px-3.5 py-1.5 rounded-lg text-sm font-medium transition',
                                        link.active
                                            ? 'bg-primary text-white shadow-md'
                                            : 'bg-gray-200 hover:bg-gray-300 text-primary-txt',
                                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                >
                                </button>

                                <button
                                    @click="nextPage"
                                    :disabled="!nextUrl"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-primary-txt"
                                >
                                    Suivant →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
table tr td,
table tr th {
    white-space: nowrap !important;
    padding: 4px 8px !important;
    font-size: 0.75rem !important;
}

thead th {
    white-space: nowrap !important;
    padding: 6px 8px !important;
    font-size: 0.70rem !important;
    background: #fff6ee;
    color: #613104;
}

tr:hover td {
    background-color: #fff8f1;
}

td.sticky {
    background-color: #fff;
    z-index: 8;
}

tr:hover td.sticky {
    background-color: #fff8f1;
}

button:active:not(:disabled) {
    transform: scale(0.95);
}

.bg-red-100 {
    background-color: #fee2e2 !important;
}
.text-red-800 {
    color: #991b1b !important;
}
.bg-yellow-100 {
    background-color: #fef9c3 !important;
}
.text-yellow-800 {
    color: #854d0e !important;
}
.bg-green-100 {
    background-color: #dcfce7 !important;
}
.text-green-800 {
    color: #166534 !important;
}
.rounded-full {
    border-radius: 9999px !important;
}
</style>
