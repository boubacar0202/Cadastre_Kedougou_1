<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import { defineProps, onMounted } from 'vue'; 
import { ref, computed, watch } from 'vue';
import { Head, router, Link as InertiaLink } from '@inertiajs/vue3';

 
const props = defineProps({
    arrivee: Object,    
    arrivees: Array,
    departs: Array,
    depart: Object,
});
  
const numeroCD = ref('');  
const annee = ref('');
const refCourrierArrivee = ref('');
const selectedDate = ref(null); 
const refCA = ref('');
 
// Filtrer  
const uniqueReferences = computed(() => {
    return Array.from(
        new Set(
            props.departs
                .filter(t => {
                    if (!selectedDate.value) return true;
                    if (!t.dt_datecouriercd) return false;
                    return t.dt_datecouriercd.startsWith(selectedDate.value);
                })
                .map(t => t.txt_categoriecd)
                .filter(Boolean)
        )
    );
});
 
// Normalisation pour éviter les problèmes d'espaces et de casse
function normalize(str) {
    return str?.toString().trim().toLowerCase();
}
 
//  Filtrer par date et par Numéro + Tri croissant par txt_numdordrecd
const filtereDeparts = computed(() => { 
 
    const filtered = props.departs.filter(depart => { 


        const matchNumero = numeroCD.value
            ? normalize(depart.txt_numdordrecd)?.includes(normalize(numeroCD.value))
            : true; 

        const matchArrivee = refCourrierArrivee.value
            ? normalize(depart.txt_categoriecd)?.includes(normalize(refCourrierArrivee.value))
            : true;

        const matchCA = refCA.value 
            ? normalize(depart.txt_referencecourierarriveecd)?.includes(normalize(refCA.value))
            : true;  
 
        const matchAnnee = annee.value ? depart.dt_datecouriercd &&
        new Date(depart.dt_datecouriercd).getFullYear().toString() === annee.value
        : true;


        return matchNumero && matchAnnee && matchArrivee && matchCA;
    });

    // Tri par ordre numérique de la partie avant "/"
    return filtered.sort((a, b) => {
        const numA = parseInt(a.txt_numdordrecd?.split("/")[0] || 0, 10);
        const numB = parseInt(b.txt_numdordrecd?.split("/")[0] || 0, 10);
        return numA - numB; // ordre croissant
    });
});

const perPage = 15;
const currentPage = ref(1);

watch([annee, numeroCD, refCA, refCourrierArrivee], () => {
    currentPage.value = 1;
});

const lastPage = computed(() => Math.max(1, Math.ceil(filtereDeparts.value.length / perPage)));

const page = computed(() => Math.min(Math.max(currentPage.value, 1), lastPage.value));

const paginatedDeparts = computed(() => {
    const start = (page.value - 1) * perPage;
    return filtereDeparts.value.slice(start, start + perPage);
});

const pagination = computed(() => {
    const current = page.value;
    const last = lastPage.value;
    const pages = new Set([1, last, current, current - 1, current + 1, current - 2, current + 2]);
    const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);
    const links = [];
    let previous = 0;

    sorted.forEach((p) => {
        if (p - previous > 1) {
            links.push({ label: '...', url: null, active: false });
        }
        links.push({ label: String(p), url: `?page=${p}`, active: p === current });
        previous = p;
    });

    links.prev = current > 1 ? `?page=${current - 1}` : null;
    links.next = current < last ? `?page=${current + 1}` : null;

    return {
        links,
        meta: { current_page: current, per_page: perPage },
    };
});

const goToPage = (url) => {
    if (!url) return;
    const params = new URLSearchParams(url.split('?')[1]);
    const next = Number(params.get('page'));
    if (next) currentPage.value = next;
};

const prevPage = () => {
    if (pagination.value.links?.prev) goToPage(pagination.value.links.prev);
};

const nextPage = () => {
    if (pagination.value.links?.next) goToPage(pagination.value.links.next);
};
 
// Compter le nombre total de courrier
const totalCourrier = computed(() => {
    return props.departs.filter(totalarrivee => !!totalarrivee.txt_numdordrecd).length;
});
 
// filtrer les instances par categorie  
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
 
function supprimerCourrierDepart(depart) {
    if (confirm(`Voulez-vous vraiment supprimer le courrier départ N° ${depart.txt_numdordrecd} ?`)) {
        router.delete(route('instancedepart.destroyDepart', depart.id), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Courrier départ supprimé avec succès');
            },
            onError: (errors) => {
                console.error('Erreur lors de la suppression', errors);
            }
        });
    }
}

</script>

<template>
    <Head title="Instances Départs">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl text-primary-txt font-semibold leading-tight"
            >
                Liste des Courriers Departs
            </h2>
            
            <!-- Vérification pour afficher un message si aucune donnée -->
            <template v-if="(!props.departs || props.departs.length === 0)">
                <p>Aucun Instance Départ n'est trouvé.</p>
            </template>
           
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Liste des courriers départs</h1>
                        </div>

                        <div class="p-6">
                            <div class="p-4 bg-primary-form border border-primary-only rounded-xl">
                                <div class="flex flex-wrap items-center justify-between gap-3"> 
                                    <h1 class="text-lg text-primary-txt font-bold">
                                        Liste des Courriers Départs : 
                                        <span v-if="totalCourrier>0" class="text-primary-txt">
                                            ({{ totalCourrier }})
                                        </span>
                                        <span v-else class="text-red-600">
                                            Aucun enregistrement
                                        </span> 
                                    </h1>  

                                    <form @submit.prevent class="flex flex-wrap items-center gap-2">
                                        <BaseInput
                                            v-model="annee"
                                            type="search"
                                            maxlength="4"
                                            placeholder="année"
                                            wrapper-class="w-20"
                                        />
                                        <BaseInput
                                            v-model="numeroCD"
                                            type="search"
                                            placeholder="N° Ordre"
                                            wrapper-class="w-28"
                                        />
                                        <BaseInput
                                            v-model="refCA"
                                            type="search"
                                            placeholder="Référence C.A"
                                            wrapper-class="w-36"
                                        />
                                        <BaseSelect
                                            v-model="refCourrierArrivee"
                                            :options="uniqueReferences"
                                            placeholder="Refetrence C.Arrivées"
                                            wrapper-class="w-44"
                                        />
                                    </form>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl">
                                    <table class="table table-auto w-full text-base text-left text-primary-txt">
                                        <thead class="sticky top-0 z-10 text-primary-txt text-font-bold text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap bg-primary-only">
                                            <tr class="h-10">
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N°</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° d'ordre départ</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Avis</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Dossier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Types de Courrier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° BE</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date Courrier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Categorie</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Référence C.Arrivée</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Nombre Pièce</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Référence</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Objet</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Destinataire</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Prénom Nom</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° LOT</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Superficie</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° TF</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lotissement</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date Envoie</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Référence Réception</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Observation</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Durée Traitement</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Caractère</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">PDF</th>
                                                <th class="px-2 py-2 text-xs text-center bg-primary-only font-semibold whitespace-nowrap">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(depart, index) in paginatedDeparts"
                                                :key="depart.id"
                                                class="h-8 text-xs hover:bg-primary hover:text-primary font-semibold text-primary-txt text-center"
                                            >
                                                <td class="sticky left-0 z-8 text-xs border bg-white px-2 py-1 whitespace-nowrap">{{ ((pagination.meta?.current_page || 1) - 1) * (pagination.meta?.per_page || 15) + index + 1 }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_numdordrecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_avisReponsecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap"><span class="font-mono">{{ depart.txt_numdossiercd || '' }}</span></td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_typecourriercd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_numdordrecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(depart.dt_datecouriercd) || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_categoriecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_referencecourierarriveecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_nombrepiececd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_referencecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_objetcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_destinatairecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_prenomNomcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_numLotcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_superficiecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_numTFcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_lotissementcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(depart.dt_dateenvoicd) || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_referencereceptioncd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_observationcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_dureetraitementcd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ depart.txt_caracterecd || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">
                                                    <a
                                                        v-if="depart.fichierPDFcd"
                                                        :href="`/storage/${depart.fichierPDFcd}`"
                                                        target="_blank"
                                                        class="text-primary underline flex justify-center items-center"
                                                    >
                                                        <img src="/iconePDF.png" alt="PDF" class="w-8 h-8 mx-auto">
                                                    </a>
                                                    <span v-else class="text-primary-txt/60 italic">Aucun PDF</span>
                                                </td>
                                                <td class="sticky right-0 z-8 px-2 py-1 border bg-white whitespace-nowrap">
                                                    <div class="flex justify-center gap-2">
                                                        <InertiaLink :href="`/depart/editdepart/${depart.id}`">
                                                            <MazBtn
                                                                size="large"
                                                                class="h-6 w-6 text-white bg-gradient-to-r from-green-500 via-green-600 to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-xs px-5 py-2.5 text-center"
                                                            >
                                                                ✏️
                                                            </MazBtn>
                                                        </InertiaLink>
                                                        <MazBtn
                                                            size="large"
                                                            color="danger"
                                                            @click="() => supprimerCourrierDepart(depart)"
                                                            class="h-6 w-6 text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-5 py-2.5 text-center"
                                                        >
                                                            🗑
                                                        </MazBtn>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="!filtereDeparts?.length">
                                                <td colspan="25" class="text-center py-12 text-primary-txt text-base">
                                                    Aucun courrier départ trouvé
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
                                        :disabled="!pagination.links?.prev"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-gray-200 hover:bg-gray-300 text-primary-txt"
                                    >
                                        ← Précédent
                                    </button>

                                    <button
                                        v-for="(link, linkIndex) in pagination.links"
                                        :key="linkIndex"
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
                                        :disabled="!pagination.links?.next"
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
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
table tr td,
table tr th {
    white-space: nowrap !important;
    padding: 10px 10px !important;
    font-size: 0.75rem !important;
}

thead th {
    white-space: nowrap !important;
    padding: 10px 10px !important;
    font-size: 0.70rem !important;
    background-color: #E6C5A2;
}

tr:hover td {
    background-color: #f8fafc;
}

button:active:not(:disabled) {
    transform: scale(0.95);
}
</style>
 