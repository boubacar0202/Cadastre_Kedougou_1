<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import { router, usePage  } from '@inertiajs/vue3'
import { Link as InertiaLink } from '@inertiajs/vue3'  
 
const props = defineProps({
    arrivee: Object,    
    arrivees: Array,  
});
   
const mois = ref('');
const numeroCA = ref('');  
const annee = ref(''); 
const rowRefs = ref({});
const refCourrierArrivee = ref('');
const prenomNomCourrierArrivee = ref('');
const refDesignation = ref(''); 
const selectedDate = ref(''); 

// Normalisation pour éviter les problèmes d'espaces et de casse
function normalize(str) {
    return str?.toString().trim().toLowerCase();
}
  
// Calculer les désignations uniques pour le filtre
const uniqueDesignations = computed(() => {
    return Array.from(
        new Set(
            props.arrivees
                .filter(a => {
                    if (!selectedDate.value) return true;

                    const date = a.dt_datearrivee ? new Date(a.dt_datearrivee) : null;
                    if (!date) return false;

                    return date.toISOString().startsWith(selectedDate.value);
                })
                .map(a => a.txt_designation || '')
                .filter(d => d.trim() !== '')
        )
    );
});

// ⚡ Ajout du tri par numéro d'ordre d'arrivée
const filtereArrivees = computed(() => {
    const now = new Date();
    const searchMois = normalize(mois.value || '') // ex: "01", "02", ..., "12"
    const searchDesignation = normalize(refDesignation.value || '')
 
    const addLateFlag = (arrivee) => {
        const dtDate = arrivee.dt_date ? new Date(arrivee.dt_date) : null;

        return {
            ...arrivee,
            isLateConvocation:
                arrivee.txt_categorie === 'Convocation - Invitation' &&
                dtDate &&
                (dtDate - now) > 0 && // date future
                (dtDate - now) <= (72 * 60 * 60 * 1000) // <= 72h
        };
    };

    const filtered = props.arrivees.filter(arrivee => {
        // 🔥 récupération du mois depuis la date
        const date = arrivee.dt_datearrivee
        const moisTerrain = date ? new Date(date).getMonth() + 1 : null // 1 à 12 
        const matchMois = !searchMois || moisTerrain == parseInt(searchMois)
        const designation = normalize(arrivee.txt_designation || '')
 
        const matchDesignation = !searchDesignation || designation.includes(searchDesignation) 
 
        const matchNumero = numeroCA.value
            ? normalize(arrivee.txt_numdordre)?.includes(normalize(numeroCA.value))
            : true;

        const matchAnnee = annee.value
            ? new Date(arrivee.dt_datearrivee).getFullYear().toString() === annee.value
            : true;

        const matchArrivee = refCourrierArrivee.value
            ? normalize(arrivee.txt_reference)?.includes(normalize(refCourrierArrivee.value))
            : true;

        const matchPrenomNom = prenomNomCourrierArrivee.value
            ? normalize(arrivee.txt_prenomNom)?.includes(normalize(prenomNomCourrierArrivee.value))
            : true;

        return matchDesignation && matchMois && matchNumero && matchAnnee && matchArrivee && matchPrenomNom;
    }); 
    // ⚡ ajout du tri ici 
    const sorted = filtered.sort((a, b) => {

        const parseNumero = (value) => {

            if (!value) {
                return { principal: 0, secondaire: 0 };
            }

            const first = value.split('/')[0]; 
            const [principal, secondaire] = first.split('_');

            return {
                principal: parseInt(principal || 0, 10),
                secondaire: parseInt(secondaire || 0, 10)
            };
        };

        const A = parseNumero(a.txt_numdordre);
        const B = parseNumero(b.txt_numdordre);

        if (A.principal !== B.principal) {
            return A.principal - B.principal;
        }

        return A.secondaire - B.secondaire;
    });

    return sorted.map(addLateFlag);
});

const perPage = 15;
const currentPage = ref(1);

watch(
    [mois, annee, numeroCA, refCourrierArrivee, prenomNomCourrierArrivee, refDesignation],
    () => {
        currentPage.value = 1;
    }
);

const lastPage = computed(() => Math.max(1, Math.ceil(filtereArrivees.value.length / perPage)));

const page = computed(() => Math.min(Math.max(currentPage.value, 1), lastPage.value));

const paginatedArrivees = computed(() => {
    const start = (page.value - 1) * perPage;
    return filtereArrivees.value.slice(start, start + perPage);
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
 
// Compter le nombre d'invitation dans 72H 
const totalConvocationsImminentes = computed(() => {
    const now = new Date();

    return props.arrivees.filter(arrivee => {
        const dtDate = arrivee.dt_date ? new Date(arrivee.dt_date) : null;

        return (
            arrivee.txt_categorie === 'Convocation - Invitation' &&
            dtDate &&
            (dtDate - now) > 0 &&
            (dtDate - now) <= (72 * 60 * 60 * 1000)
        );
    }).length;
});
 
// Compter le nombre total de courrier
const totalCourrier = computed(() => {
    return props.arrivees.filter(totalarrivee => !!totalarrivee.txt_numdordre).length;
});

// format de la date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

// fonction d'envoi du formulaire
function supprimerCourrierArrivee(arrivee) {
    if (confirm(`Voulez-vous vraiment supprimer le courrier arrivée N° ${arrivee.txt_numdordre} ?`)) {
        router.delete(route('instancearrivee.destroy', arrivee.id), {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Arrivée supprimée avec succès');
            },
            onError: (errors) => {
                console.error('Erreur lors de la suppression', errors);
            }
        });
    }
} 
// Liste filtrée (même logique mais sans length)
const convocationsDans48h = computed(() => {
    const now = new Date();

    return props.arrivees.filter(arrivee => {
        const dtDate = arrivee.dt_date ? new Date(arrivee.dt_date) : null;

        return (
            arrivee.txt_categorie === 'Convocation - Invitation' &&
            dtDate &&
            dtDate > now && 
            (dtDate - now) <= 48 * 60 * 60 * 1000
        );
    });
}); 
// 👉 Convocation(s) la/les plus proche(s)
const convocationProche = computed(() => {
    if (!convocationsDans48h.value.length) return null;

    // Prendre la date la plus proche
    const minDate = Math.min(
        ...convocationsDans48h.value.map(c => new Date(c.dt_date).getTime())
    );

    // Retourner toutes celles qui ont cette même date
    return convocationsDans48h.value.filter(
        c => new Date(c.dt_date).getTime() === minDate
    );
}); 
// Fonction pour faire défiler jusqu'à la convocation
const goToConvocation = async (txt_numdordre) => {
    const index = filtereArrivees.value.findIndex((arrivee) => arrivee.txt_numdordre === txt_numdordre);
    if (index >= 0) {
        currentPage.value = Math.floor(index / perPage) + 1;
    }
    await nextTick();
    const row = rowRefs.value[txt_numdordre];
    if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('bg-primary-only');
        setTimeout(() => row.classList.remove('bg-primary-only'), 5000);
    }
};
const formatDateDMY = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',  
        year: 'numeric'
    });
};

</script>

<template>
    <Head title="Instances Arrivées">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-primary-txt"
            >
                Liste des Courriers Arrivés
            </h2>
            
            <!-- Vérification pour afficher un message si aucune donnée -->
            <template class="text-xl font-semibold leading-tight text-primary-txt" v-if="(!arrivees || arrivees.length === 0)">
                <p>Aucun Courrier arrivée trouvé.</p>
            </template>
           
        </template v-else>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Liste des courriers arrivées</h1>
                        </div>

                        <div class="p-6">
                            <div class="p-4 bg-primary-form border border-primary-only rounded-xl">
                                <div class="flex flex-wrap items-center justify-between gap-3">  
                                    <h1 class="text-lg text-primary-txt font-bold">
                                        Liste des Courriers Arrivés : 
                                        <span v-if="totalCourrier>0" class="text-primary-txt">
                                            ({{ totalCourrier }})
                                        </span>
                                        <span v-else class="text-red-600">
                                            Aucun enregistrement
                                        </span> 
                                    </h1> 
                                    <h2 class="text-sm text-primary font-bold">
                                        Convocation dans 72H : 
                                        <span class="text-primary-dark font-bold">{{ totalConvocationsImminentes }}</span> 
                                    </h2>  
                                    
                                    <ul v-if="convocationProche && convocationProche.length" class="space-y-2">
                                        <li
                                            v-for="convocation in convocationProche"
                                            :key="convocation.txt_numdordre"
                                            class="p-2 h-10 bg-white border border-primary-only rounded-xl shadow-sm hover:bg-green-300 hover:text-white cursor-pointer flex justify-between items-center"
                                             @click="goToConvocation(convocation.txt_numdordre)"
                                        >
                                            <div class="flex items-center space-x-2">
                                                <p class="font-medium text-sm text-primary-txt"> 
                                                    Dans 48H : 📅 {{ formatDateDMY(convocation.dt_date) }} à {{ convocation.txt_heure }}
                                                </p>
                                            </div>
                                        </li>
                                    </ul>

                                    <p v-else class="text-sm text-primary-txt p-2 bg-white border border-primary-only rounded-xl shadow-sm italic">
                                        Pas de convocation dans les 48h
                                    </p>
 
                                    <form @submit.prevent class="flex flex-wrap items-center gap-2">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <div>
                                                <input 
                                                    v-model="mois"
                                                    type="search"
                                                    maxlength="4"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-10 block w-20 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only placeholder:text-primary-txt/50 focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                    placeholder="Mois"
                                                /> 
                                            </div>

                                            <div>
                                                <input 
                                                    v-model="annee"
                                                    type="search"
                                                    maxlength="4"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-10 block w-20 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only placeholder:text-primary-txt/50 focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                    placeholder="Années"
                                                /> 
                                            </div>
                                        
                                            <div>
                                                <input 
                                                    v-model="numeroCA"
                                                    type="search"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-10 block w-28 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only placeholder:text-primary-txt/50 focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                    placeholder="N° Ordre"
                                                />
                                            </div> 
 
                                            <div>
                                                <input 
                                                    v-model="prenomNomCourrierArrivee"
                                                    type="search"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-10 block w-24 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only placeholder:text-primary-txt/50 focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                    placeholder="Prénom Nom"
                                                />
                                            </div>
                                                                                        
                                            <div>
                                                <input 
                                                    v-model="refCourrierArrivee"
                                                    type="search"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-10 block w-24 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only placeholder:text-primary-txt/50 focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                    placeholder="Ref.C.A"
                                                />
                                            </div>
                                                   
                                            <div>
                                                <select
                                                    v-model="refDesignation"
                                                    class="h-10 block w-28 rounded-xl bg-white px-3 text-sm text-primary-txt border border-primary-only focus:outline-none focus:ring-2 focus:ring-primary-menu/40 focus:border-primary-menu"
                                                >
                                                    <option value="">Tous</option> 
                                                    <option
                                                        v-for="designation in uniqueDesignations"
                                                        :key="designation"
                                                        :value="designation"
                                                    >
                                                        {{ designation }}
                                                    </option>
                                                </select>
                                            </div> 
                                        </div>
                                    </form>
                                </div>
                            </div>
 
                            <div class="p-4">
                                <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl">
                                    <table class="table table-auto w-full text-base text-left text-primary-txt">
                                        <thead class="sticky top-0 z-10 text-primary-txt text-font-bold text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap bg-primary-only">
                                            <tr class="h-10">
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N°</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° d'ordre d'arrivée</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Types de Courrier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° BE</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Dossier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date Récéption</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Categorie</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° Courier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date Courrier</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Référence</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Caractère</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Designation</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Prénom Nom</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° LOT</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Superficie</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">N° TF</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lotissement</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Date</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Heure</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Lieu</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Nombre Pièce</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Objet</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Expéditeur</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Agent Traiteur</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">Observation</th>
                                                <th class="px-2 py-2 text-xs text-primary text-center font-semibold whitespace-nowrap">PDF</th>
                                                <th class="px-2 py-2 text-xs text-center bg-primary-only font-semibold whitespace-nowrap">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(arrivee, index) in paginatedArrivees"
                                                :key="arrivee.id"
                                                :ref="el => (rowRefs[arrivee.txt_numdordre] = el)"
                                                class="h-8 text-xs hover:bg-primary hover:text-primary font-semibold text-primary-txt text-center"
                                            >
                                                <td class="sticky left-0 z-8 text-xs border bg-white px-2 py-1 whitespace-nowrap">{{ ((pagination.meta?.current_page || 1) - 1) * (pagination.meta?.per_page || 15) + index + 1 }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap" :class="arrivee.isLateConvocation ? 'text-primary font-bold' : ''">{{ arrivee.txt_numdordre }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_typecourrier || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numbordereau || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap"><span class="font-mono">{{ arrivee.txt_numdossier || '' }}</span></td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_datearrivee) || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_categorie || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numcourier || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_datecourier) || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_reference || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_caractere || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_designation || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_prenomNom || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numLot || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_superficie || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numTF || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_lotissement || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_date) || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_heure || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_lieu || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_nombrepiece || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_objet || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_expediteur || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_agenttraiteur || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_observation || '' }}</td>
                                                <td class="px-2 py-1 text-xs border whitespace-nowrap">
                                                    <a
                                                        v-if="arrivee.fichierPDF"
                                                        :href="`/storage/${arrivee.fichierPDF}`"
                                                        target="_blank"
                                                        class="text-primary underline flex justify-center items-center"
                                                    >                                                                    
                                                        <img src="/iconePDF.png" alt="PDF" class="w-8 h-8 mx-auto">
                                                    </a>
                                                    <span v-else class="text-primary-txt/60 italic">Aucun PDF</span>
                                                </td>
                                                <td class="sticky right-0 z-8 px-2 py-1 border bg-white whitespace-nowrap">
                                                    <div class="flex justify-center gap-2">
                                                        <InertiaLink :href="route('arrivee.edit', arrivee.id)">
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
                                                            @click="() => supprimerCourrierArrivee(arrivee)"
                                                            class="h-6 w-6 text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-xs px-5 py-2.5 text-center"
                                                        >
                                                            🗑
                                                        </MazBtn>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="!filtereArrivees?.length">
                                                <td colspan="27" class="text-center py-12 text-primary-txt text-base">
                                                    Aucun courrier arrivée trouvé
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
                        </div><br>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

.animate-fade-in {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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

tr:hover td {
    background-color: #f8fafc;
}

button:active:not(:disabled) {
    transform: scale(0.95);
}


</style>
 
 
