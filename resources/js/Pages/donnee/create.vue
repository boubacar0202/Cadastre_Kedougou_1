<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { toast } from "@/Components/AppToast";
import DeleteModal from "@/Components/DeleteModal.vue";
import MazBtn from "maz-ui/components/MazBtn";
import { debounce } from 'lodash'; 
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    terrains: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            meta: { 
                current_page: 1, 
                last_page: 1, 
                total: 0,  
                per_page: 15 
            }
        })
    },
    uniqueServices: {
        type: Array,
        default: () => []
    },
    totalGeneral: {
        type: Number,
        default: 0
    },
    terrainsSansNicad: {
        type: Number,
        default: 0
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// États
const filters = ref({
    mois: props.filters.mois || '',
    annee: props.filters.annee || '',
    numero: props.filters.numero || '',
    prenomNom: props.filters.prenomNom || '',
    refServiceRendu: props.filters.refServiceRendu || ''
});

const showDeleteModal = ref(false);
const selectedTerrain = ref(null);
const loadingDelete = ref(false);
const deleteCode = ref(''); 
const adminCode = ref('');  

// Debounced search
const debouncedSearch = debounce((page = 1) => {
    router.get(route('donnees.create'), {
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

// totalSansNicad
const totalSansNicad = computed(() => {
    return props.terrainsSansNicad || 0;
});

// Nombre total d'enregistrements
const totalEnregistrement = computed(() => {
    return props.totalGeneral || 0;
});

// Nombre total d'enregistrements de la page actuelle
const totalPageActuelle = computed(() => {
    return props.terrains.data?.length || 0;
});

// Nombre total filtré (pour l'affichage "sur X enregistrements")
const totalFiltre = computed(() => {
    return props.terrains.meta?.total || 0;
});

// Ajoutez un computed pour vérifier si des données existent 
// Vérifier si des données existent
const hasData = computed(() => {
    return props.terrains?.data && props.terrains.data.length > 0;
});

// Vérifier si la pagination est disponible
const hasPagination = computed(() => {
    return hasData.value && 
        props.terrains?.meta?.last_page && 
        props.terrains.meta.last_page > 1;
});

// Pagination simplifiée
const goToPage = (url) => {
    if (!url) return;
    
    try {
        const urlParams = new URLSearchParams(url.split('?')[1]);
        const page = urlParams.get('page');
        
        if (page) {
            router.get(route('donnees.create'), {
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
    if (props.terrains.links?.prev) {
        goToPage(props.terrains.links.prev);
    }
};

const nextPage = () => {
    if (props.terrains.links?.next) {
        goToPage(props.terrains.links.next);
    }
};


// Ouvrir le modal de suppression
const supprimerTerrain = (terrain) => {
    if (!terrain?.id) return;
    selectedTerrain.value = terrain;
    deleteCode.value = "";
    adminCode.value = "";
    showDeleteModal.value = true;
};

// Confirmer la suppression
const confirmerSuppression = (codes) => {
    loadingDelete.value = true;
    
    router.delete(route('terrain.destroy', selectedTerrain.value.id), {
        data: {
            code: codes.adminCode
        },
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedTerrain.value = null;
            toast.success('Terrain supprimé avec succès');
            debouncedSearch(props.terrains.meta?.current_page || 1);
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
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return '-';
    }
};

// Réinitialisation
const resetFilters = () => {
    filters.value = {
        mois: '',
        annee: '',
        numero: '',
        prenomNom: '',
        refServiceRendu: ''
    };
};

// Chargement initial
onMounted(() => {
    if (!props.terrains.data?.length) {
        debouncedSearch(1);
    }
});

// ✅ Fonction pour la classe de la cellule N° Dossier
const getCellClass = (terrain) => {
    const nicad = terrain?.txt_nicad;

    // Cas 1 : NICAD est "NON" ou "null"
    if (nicad === 'NON' || nicad === 'null' || String(nicad).toLowerCase() === 'non') {
        return 'bg-blue-300 text-white font-bold rounded';
    }

    // Cas 2 : NICAD est null, undefined ou vide
    if (!nicad || String(nicad).trim() === '') {
        return 'bg-blue-100 text-blue-800 font-bold rounded';
    }

    // Cas 3 : NICAD valide
return 'text-primary-txt';
};

// ✅ Fonction pour le badge NICAD (optionnel)
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

</script>

<template>
    <Head title="Base de données">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Base de données
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow rounded-xl">
                        <!-- En-tête avec stats et filtres -->
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4 border-b rounded-t-xl bg-primary">
                            <div class="flex flex-wrap items-center gap-4">
                                <h1 class="text-lg text-white font-semibold">
                                    Base de données
                                </h1>
                                <!-- 🔥 TOTAL GÉNÉRAL (toute la base) -->
                                <span class="text-white/80 text-sm">
                                    Total : <span class="font-bold text-white">{{ totalEnregistrement }}</span>
                                </span>
                                <span class="text-yellow-300 text-sm font-semibold">
                                    Sans NICAD : {{ totalSansNicad }}
                                </span>
                                <!-- 🔥 Affichage du nombre filtré -->
                                <span class="text-white/60 text-xs" v-if="totalFiltre !== totalEnregistrement">
                                    ({{ totalFiltre }} avec filtres)
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1">
                                <input 
                                    v-model="filters.mois"
                                    type="number"
                                    min="1"
                                    max="12"
                                    class="h-8 w-20 rounded-xl bg-white text-gray-900 placeholder:text-gray-400 px-3 py-1.5 text-sm border border-primary-menu focus:outline focus:outline-2 focus:outline-primary"
                                    placeholder="Mois"
                                />
                                <input 
                                    v-model="filters.annee"
                                    type="number"
                                    min="2000"
                                    max="2100"
                                    class="h-8 w-20 rounded-xl bg-white text-gray-900 placeholder:text-gray-400 px-3 py-1.5 text-sm border border-primary-menu focus:outline focus:outline-2 focus:outline-primary"
                                    placeholder="Année"
                                />
                                <input 
                                    v-model="filters.numero"
                                    type="text"
                                    class="h-8 w-32 rounded-xl bg-white text-gray-900 placeholder:text-gray-400 px-3 py-1.5 text-sm border border-primary-menu focus:outline focus:outline-2 focus:outline-primary"
                                    placeholder="N° dossier"
                                />
                                <input 
                                    v-model="filters.prenomNom"
                                    type="text"
                                    class="h-8 w-40 rounded-xl bg-white text-gray-900 placeholder:text-gray-400 px-3 py-1.5 text-sm border border-primary-menu focus:outline focus:outline-2 focus:outline-primary"
                                    placeholder="Prénom Nom"
                                />
                                <select
                                    v-model="filters.refServiceRendu"
                                    class="h-8 w-44 rounded-xl bg-white text-gray-900 px-3 py-1.5 text-sm border border-primary-menu focus:outline focus:outline-2 focus:outline-primary"
                                >
                                    <option value="">Tous les services</option>
                                    <option 
                                        v-for="service in uniqueServices" 
                                        :key="service" 
                                        :value="service"
                                    >
                                        {{ service }}
                                    </option>
                                </select>
                                <button 
                                    @click="resetFilters"
                                    class="h-8 px-4 bg-white/20 text-white rounded-xl text-sm hover:bg-white/30 transition font-medium"
                                >
                                    ✕ Réinitialiser
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
                                <table class="table table-auto w-full text-base text-left text-gray-500">
                                    <thead 
                                        class="sticky top-0 z-10 text-primary-txt text-font-bold text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap bg-primary-only"
                                    >
                                        <tr class="h-10">
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N°</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° dossier</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Region</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Depart.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Arrond.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Commune</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Service Rendue</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Etat Cession.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Cession. définitive.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date création</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Parcelle</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lotissement</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Hors lotissement</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° lotissement</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Section</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Appartement</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° titre</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Surface</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Doc admin</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° délib.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date délib.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">NICAD</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Statut NICAD</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Motif Annulation NICAD</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Immatric,.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Dép. domaine</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Ussu born.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Titre mère</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Livre Fon.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° réquis.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Surf. born.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date born.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Nom géom.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Titulaire</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">National.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Civilité</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Prénom - Nom - Dénomination</th> 
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Pièce</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Pièce</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date déliv.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date naiss.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lieu naiss.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Adresse</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Tél.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Email</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Représ.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Tél Rép.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date éval.</th>
                                            <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">PDF</th>
                                            <th class="px-2 py-2 text-xs text-center bg-primary-only font-semibold whitespace-nowrap">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(terrain, index) in terrains.data"
                                            :key="terrain.id"
                                            class="h-8 text-xs hover:bg-primary hover:text-primary font-semibold text-primary-txt text-center"  
                                        >
                                            <td class="sticky left-0 z-8 text-xs border bg-white px-2 py-1 whitespace-nowrap">
                                                {{ ((terrains.meta?.current_page || 1) - 1) * (terrains.meta?.per_page || 15) + index + 1 }}
                                            </td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap transition-colors duration-200" :class="getCellClass(terrain)"><span class="font-mono">{{ terrain.dossier?.txt_num_dossier  || ''}}</span></td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.region?.slt_region || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.departement?.slt_departement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.arrondissement?.slt_arrondissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.commune?.slt_commune || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.dossier?.slt_service_rendu || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.dossier?.txt_etat_cession || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.dossier?.txt_cession_definitive || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.dossier?.dt_date_creation) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_parcelle || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_lotissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_HorsLotissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_lotissement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_section || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_appartement || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_titre || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.nbr_surface || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.slt_document_admin || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.txt_num_deliberation || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.dt_date_deliberation) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap font-bold">
                                                <span :class="getNicadBadgeClass(terrain)">
                                                    {{ terrain.txt_nicad || 'NON' }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.slt_statut_nicad || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.slt_motif_annulation_nicad || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.rd_immatriculation_terrain || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.slt_dependant_domaine || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.ussu_bornage || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.txt_titre_mere || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.slt_lf || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.txt_num_requisition || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.txt_surface_bornage || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.references_cadastrales?.dt_date_bornage) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.references_cadastrales?.txt_nom_geometre || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.slt_titulaire || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_nationalite || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.slt_civilite || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_prenom || '' }} {{ terrain.titulaire?.txt_denominationPM || '' }} {{ terrain.titulaire?.txt_nom || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.slt_piece || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_numPiece || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.titulaire?.dt_date_delivrance) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.titulaire?.dt_date_naissance) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_lieu_naissance || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_adresse || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.tel_telephone || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.eml_email || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.txt_representant || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ terrain.titulaire?.tel_telRepresentant || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(terrain.evaluations_terrains?.dt_dateEvaluation) || '' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">
                                                <a 
                                                    v-if="terrain.titulaire?.fichierPDF"
                                                    :href="`/storage/${terrain.titulaire.fichierPDF}?t=${Date.now()}`"
                                                    target="_blank"
                                                    class="text-blue-600 underline"
                                                >
                                                    Voir PDF
                                                </a>
                                                <span v-else class="text-gray-400 italic">Aucun PDF</span>
                                            </td>
                                            <td class="sticky right-0 z-8 px-2 py-1 border bg-white whitespace-nowrap">
                                                <div class="flex justify-center gap-2">
                                                    <!-- Edit -->  
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

                                                    <!-- Delete -->
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

                                        <!-- Message si aucun résultat -->
                                        <tr v-if="!terrains.data?.length">
                                            <td colspan="49" class="text-center py-12 text-gray-500 text-base">
                                                Aucun terrain trouvé
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="p-4 border-t flex flex-wrap justify-between items-center gap-4 bg-gray-50 rounded-b-xl">  
                            <!-- Pagination -->
                            <div class="flex gap-1 flex-wrap">
                                <button 
                                    @click="prevPage"
                                    :disabled="!terrains.links?.prev"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-gray-700"
                                >
                                    ← Précédent
                                </button>
                                
                                <button 
                                    v-for="link in terrains.links" 
                                    :key="link.label"
                                    @click="goToPage(link.url)"
                                    :disabled="!link.url"
                                    :class="[
                                        'px-3.5 py-1.5 rounded-lg text-sm font-medium transition',
                                        link.active 
                                            ? 'bg-primary text-white shadow-md' 
                                            : 'bg-gray-200 hover:bg-gray-300 text-gray-700',
                                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                >
                                </button>
                                
                                <button 
                                    @click="nextPage"
                                    :disabled="!terrains.links?.next"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-gray-700"
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
/* Toutes les cellules sur une seule ligne */
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
}

/* Scroll horizontal */
.overflow-x-auto {
scrollbar-width: thin;
scrollbar-color: #cbd5e1 #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar {
height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
background: #f1f5f9;
border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
background: #cbd5e1;
border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
background: #94a3b8;
}

/* Survol des lignes */
tr:hover td {
background-color: #f8fafc;
}

/* Animation des boutons */
button:active:not(:disabled) {
transform: scale(0.95);
}

/* Couleurs pour la cellule N° Dossier */
.bg-blue-100 {
background-color: #dbeafe !important;
}
.bg-blue-200 {
background-color: #bfdbfe !important;
}
.bg-blue-300 {
background-color: #93c5fd !important;
}
.text-blue-800 {
color: #1e40af !important;
}
.text-white {
color: #ffffff !important;
}
.rounded {
border-radius: 0.25rem;
}

/* Badges */
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