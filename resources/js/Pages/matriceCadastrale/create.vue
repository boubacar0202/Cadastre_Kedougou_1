<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BaseInput from "@/Components/BaseInput.vue";
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { debounce } from 'lodash';
import { toast } from "@/Components/AppToast";
import DeleteModal from "@/Components/DeleteModal.vue";
import MazBtn from "maz-ui/components/MazBtn";
import * as XLSX from 'xlsx';

const props = defineProps({
    terrains: {
        type: [Object, Array],
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// États pour les filtres
const filters = ref({
    numero: props.filters?.numero || '',
    nicad: props.filters?.nicad || '',
    titulaire: props.filters?.titulaire || '',
    commune: props.filters?.commune || ''
});

const showDeleteModal = ref(false);
const selectedTerrain = ref(null);
const loadingDelete = ref(false);
const deleteCode = ref('');

const hasNicad = (terrain) => String(terrain?.txt_nicad ?? "").trim() !== "";

// 🔥 Vérifier si c'est une collection (Array) ou une pagination (Object)
const isCollection = computed(() => Array.isArray(props.terrains));

// 🔥 Obtenir les données, sans les lignes dont le NICAD est vide
const displayData = computed(() => {
    const rows = isCollection.value
        ? (props.terrains || [])
        : (props.terrains?.data || []);

    return rows.filter(hasNicad);
});

// 🔥 Total des enregistrements
const totalEnregistrement = computed(() => displayData.value.length);

// 🔥 Vérifier si des données existent
const hasData = computed(() => displayData.value.length > 0);

// 🔥 Vérifier si la pagination est disponible
const hasPagination = computed(() => {
    if (isCollection.value) return false;
    return hasData.value && 
           props.terrains?.meta?.last_page && 
           props.terrains.meta.last_page > 1;
});

// 🔥 Numéro de ligne
const getRowNumber = (index) => {
    if (isCollection.value) {
        return index + 1;
    }
    return ((props.terrains?.meta?.current_page || 1) - 1) * (props.terrains?.meta?.per_page || 15) + index + 1;
};

// Debounced search
const debouncedSearch = debounce((page = 1) => {
    router.get(route('matrice.create'), {
        ...filters.value,
        page: page
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 300);

// Watcher sur les filtres
watch(filters, () => {
    debouncedSearch(1);
}, { deep: true });

// Pagination (uniquement si ce n'est pas une collection)
const goToPage = (url) => {
    if (!url || isCollection.value) return;
    
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
    if (!isCollection.value && props.terrains?.links?.prev) {
        goToPage(props.terrains.links.prev);
    }
};

const nextPage = () => {
    if (!isCollection.value && props.terrains?.links?.next) {
        goToPage(props.terrains.links.next);
    }
};

// Suppression
const supprimerTerrain = (terrain) => {
    if (!terrain?.id) return;
    selectedTerrain.value = terrain;
    deleteCode.value = "";
    showDeleteModal.value = true;
};

const confirmerSuppression = () => {
    if (deleteCode.value !== 'SUPPRIMER') {
        toast.error('Code incorrect');
        return;
    }

    loadingDelete.value = true;
    router.delete(route('matrice.destroy', selectedTerrain.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedTerrain.value = null;
            toast.success('Terrain supprimé avec succès');
            debouncedSearch(1);
        },
        onError: (errors) => {
            toast.error('Erreur lors de la suppression');
        },
        onFinish: () => {
            loadingDelete.value = false;
        }
    });
};

// Formatage date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return '-';
    }
};

// Réinitialisation des filtres
const resetFilters = () => {
    filters.value = {
        numero: '',
        nicad: '',
        titulaire: '',
        commune: ''
    };
};

// Chargement initial
onMounted(() => {
    if (!hasData.value) {
        debouncedSearch(1);
    }
});


// 🔥 Export Excel
const exportToExcel = () => {
    if (!hasData.value) {
        toast.error('Aucune donnée à exporter');
        return;
    }

    try {
        // Préparer les données à exporter (mêmes colonnes que le tableau)
        const exportData = displayData.value.map((terrain, index) => ({
            'N°': getRowNumber(index),
            'N° dossier': terrain.dossier?.txt_num_dossier || '-',
            'Region': terrain.region?.slt_region || '-',
            'Departement': terrain.departement?.slt_departement || '-',
            'Commune': terrain.commune?.slt_commune || '-',
            'Lotissement': terrain.txt_lotissement || '-',
            'Section': terrain.txt_num_section || '-',
            'N° Lot': terrain.txt_num_lotissement || '-',
            'N° Parcelle': terrain.txt_num_parcelle || '-',
            'NICAD': terrain.txt_nicad || 'NON',
            'Surface': terrain.nbr_surface || '-',
            'Type Batie': terrain.evaluations_batis?.currentCat || '-',
            'Niveau': terrain.evaluations_batis?.nbr_niveauPR || '-',
            'Titre Mere': terrain.references_cadastrales?.txt_titre_mere || '-',
            'Titulaire': terrain.titulaire?.slt_titulaire || '-',
            'Prénom': terrain.titulaire?.txt_prenom || '-',
            'Nom': terrain.titulaire?.txt_nom || '-',
            'Téléphone': terrain.titulaire?.tel_telephone || '-',
            'Email': terrain.titulaire?.eml_email || '-',
        }));

        // Créer la feuille et le classeur
        const worksheet = XLSX.utils.json_to_sheet(exportData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Matrice Cadastrale');

        // Ajuster la largeur des colonnes automatiquement
        const columnWidths = Object.keys(exportData[0] || {}).map((key) => {
            const maxLength = Math.max(
                key.length,
                ...exportData.map((row) => String(row[key] ?? '').length)
            );
            return { wch: Math.min(maxLength + 2, 40) };
        });
        worksheet['!cols'] = columnWidths;

        // Nom du fichier avec la date
        const dateStr = new Date().toISOString().slice(0, 10);
        const fileName = `matrice_cadastrale_${dateStr}.xlsx`;

        // Télécharger
        XLSX.writeFile(workbook, fileName);

        toast.success('Export Excel réussi');
    } catch (error) {
        console.error('Erreur export Excel:', error);
        toast.error("Erreur lors de l'export");
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
                        <!-- En-tête -->
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4 border-b rounded-t-xl bg-primary">
                            <div class="flex flex-wrap items-center gap-4">
                                <h1 class="text-lg text-white font-semibold">
                                    Matrice Cadastrale
                                </h1>
                                <span class="text-white/80 text-sm">
                                    Total : <span class="font-bold text-white">{{ totalEnregistrement }}</span>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <BaseInput v-model="filters.numero" type="text" placeholder="N° dossier" wrapper-class="w-32" />
                                <BaseInput v-model="filters.nicad" type="text" placeholder="NICAD" wrapper-class="w-32" />
                                <BaseInput v-model="filters.titulaire" type="text" placeholder="Titulaire" wrapper-class="w-40" />
                                <BaseInput v-model="filters.commune" type="text" placeholder="Commune" wrapper-class="w-32" />
                                <button 
                                    @click="resetFilters"
                                    class="h-8 px-4 bg-white/20 text-white rounded-xl text-sm hover:bg-white/50 transition font-medium"
                                >
                                    ✕ Réinitialiser
                                </button>
                                <button 
                                    @click="exportToExcel"
                                    :disabled="!hasData"
                                    class="h-8 px-4 bg-green-600 text-white rounded-xl text-sm hover:bg-green-300 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                    title="Exporter en Excel"
                                >
                                    📥 Exporter Excel
                                </button>
                            </div>
                        </div>

                        <!-- Delete Modal -->
                        <DeleteModal 
                            :show="showDeleteModal"
                            :itemName="selectedTerrain?.txt_num_lotissement || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteModal = $event"
                            @confirm="confirmerSuppression"
                        />

                        <!-- Tableau -->
                        <div class="p-4">
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl">
                                <table class="table table-auto w-full text-xs text-left text-primary-txt">
                                    <thead class="sticky top-0 z-10 bg-primary text-white whitespace-nowrap">
                                        <tr>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">N°</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">N° dossier</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Region</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Departement</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Commune</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Lotissement</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Section</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">N° Lot</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">N° Parcelle</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">NICAD</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Surface</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Type Batie</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Niveau</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Titre Mere</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Titulaire</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Prénom</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Nom</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Téléphone</th>
                                            <th class="px-2 py-2 text-center border-r border-white/20 font-semibold">Email</th>
                                            <th class="px-2 py-2 text-center font-semibold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(terrain, index) in displayData"
                                            :key="terrain.id || index"
                                            class="hover:bg-gray-50 transition-colors"
                                        >
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">
                                                {{ getRowNumber(index) }}
                                            </td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.dossier?.txt_num_dossier || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.region?.slt_region || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.departement?.slt_departement || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.commune?.slt_commune || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.txt_lotissement || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.txt_num_section || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.txt_num_lotissement || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.txt_num_parcelle || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap font-bold">{{ terrain.txt_nicad || 'NON' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.nbr_surface || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.evaluations_batis?.currentCat || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.evaluations_batis?.nbr_niveauPR || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.references_cadastrales?.txt_titre_mere || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.titulaire?.slt_titulaire || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.titulaire?.txt_prenom || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.titulaire?.txt_nom || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.titulaire?.tel_telephone || '-' }}</td>
                                            <td class="px-2 py-1 text-center border border-gray-200 whitespace-nowrap">{{ terrain.titulaire?.eml_email || '-' }}</td>
                                            <td class="px-2 py-1 border border-gray-200 whitespace-nowrap">
                                                <div class="flex justify-center gap-1">
                                                    <Link :href="`/secretariat/edit/${terrain.id}`">
                                                        <MazBtn 
                                                            size="small"
                                                            class="h-7 w-16 text-white bg-gradient-to-r from-green-500 via-green-600 to-green-700 hover:bg-gradient-to-br font-medium rounded-lg text-[9px] px-2 py-1"
                                                        >
                                                            ✏️
                                                        </MazBtn>
                                                    </Link>
                                                    <MazBtn 
                                                        size="small"
                                                        color="danger"
                                                        @click="supprimerTerrain(terrain)"
                                                        :disabled="loadingDelete"
                                                        class="h-7 w-16 text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:bg-gradient-to-br font-medium rounded-lg text-[9px] px-2 py-1"
                                                    >
                                                        🗑️
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

                        <!-- Pagination - Masquée si c'est une collection -->
                        <div v-if="!isCollection && hasPagination" class="p-4 border-t flex flex-wrap justify-between items-center gap-4 bg-gray-50 rounded-b-xl">
                            <div class="text-sm text-primary-txt font-medium">
                                <template v-if="hasData">
                                    Affichage de <span class="font-bold text-primary-txt">{{ props.terrains?.meta?.from ?? 0 }}</span> 
                                    à <span class="font-bold text-primary-txt">{{ props.terrains?.meta?.to ?? 0 }}</span> 
                                    sur <span class="font-bold text-primary-txt">{{ props.terrains?.meta?.total ?? 0 }}</span> enregistrements
                                </template>
                                <template v-else>
                                    <span class="text-primary-txt">Aucun enregistrement trouvé</span>
                                </template>
                            </div>
                            
                            <div class="flex gap-1 flex-wrap">
                                <button 
                                    @click="prevPage"
                                    :disabled="!props.terrains?.links?.prev"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-primary-txt"
                                >
                                    ← Précédent
                                </button>
                                
                                <button 
                                    v-for="link in (props.terrains?.links || [])" 
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
                                    :disabled="!props.terrains?.links?.next"
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
    padding: 4px 6px !important;
    font-size: 0.70rem !important;
}

thead th {
    white-space: nowrap !important;
    padding: 6px 8px !important;
    font-size: 0.65rem !important;
}

tr:hover td {
    background-color: #f8fafc;
}

button:active:not(:disabled) {
    transform: scale(0.95);
}

.h-7 {
    height: 1.75rem;
}

.w-16 {
    width: 4rem;
}

.text-\[9px\] {
    font-size: 9px;
}
</style>