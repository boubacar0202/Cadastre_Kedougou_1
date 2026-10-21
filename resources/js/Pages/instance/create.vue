<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { ref, computed  } from 'vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import { router, usePage  } from '@inertiajs/vue3'
import { Link as InertiaLink } from '@inertiajs/vue3'
 
const props = defineProps({
    arrivee: Object,    
    arrivees: Array,
    departs: Array,
    depart: Object,
});
   
const refCourrierArrivee = ref('');
 
// filtrer les instances par categorie  
const filteredInstance = computed(() => {
    const now = new Date();

    const addIsOverdueFlag = (arrivee) => {
        const dtCourrier = arrivee.dt_datearrivee ? new Date(arrivee.dt_datearrivee) : null;

        return {
            ...arrivee,
            isOverdue:
                dtCourrier &&  
                (now - dtCourrier) >= (10 * 24 * 60 * 60 * 1000) 
        };
    };

    // Fonction de normalisation pour la recherche insensible à la casse
    const normalize = (text) => {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .trim()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    };

    return props.arrivees
        .filter(arrivee => {
            // Vérifie si le courrier existe déjà dans les départs
            const existeDansDepart = props.departs.some(
                depart => depart.txt_referencecourierarriveecd === arrivee.txt_reference
            );

            const isBEReponsesMultiples = arrivee.txt_typecourrier === 'BE à réponses Multiples';
            
            // Filtre principal par catégorie
            const matchCategorie = arrivee.txt_categorie === 'Demande SERVICES' && !existeDansDepart && !isBEReponsesMultiples;
            
            // Filtre optionnel par référence
            const searchTerm = refCourrierArrivee.value;
            const matchArrivee = searchTerm
                ? normalize(arrivee.txt_reference)?.includes(normalize(searchTerm))
                : true;

            return matchCategorie && matchArrivee;
        })
        .map(addIsOverdueFlag);
});
   
// Compter le nombre d'instance
const totalInstance = computed(() => {
    return filteredInstance.value.filter(instance => instance.isOverdue).length;
});

// Compter le nombre total de courrier
const totalCourrierInstance = computed(() => {
    return filteredInstance.value.filter(totalCourrierInstance => !!totalCourrierInstance.txt_numdordre).length;
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
 
</script>

<template>
    <Head title="Instances">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Courriers en Instance
            </h2>
            
            <!-- Vérification pour afficher un message si aucune donnée -->
            <p v-if="!arrivees || arrivees.length === 0" class="mt-2 text-sm font-medium text-primary-txt">
                Aucun courrier arrivée trouvé.
            </p>
           
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg font-semibold text-white">Liste des courriers en instance</h1>
                        </div>

                        <div class="p-6">
                            <div class="rounded-xl border border-primary-only bg-primary-form p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h1 class="text-lg font-bold text-primary-txt">
                                        Instances 
                                        <span v-if="totalCourrierInstance>0" class="text-primary-txt">
                                            ({{ totalCourrierInstance }})
                                        </span>
                                        <span v-else class="text-red-600">
                                            Aucun enregistrement
                                        </span> 
                                    </h1>

                                    <h2 class="text-sm font-bold text-primary">
                                        Instances de plus de 10 jours :
                                        <span class="text-primary-dark">{{ totalInstance }}</span>
                                    </h2>

                                    <BaseInput
                                        v-model="refCourrierArrivee"
                                        type="search"
                                        id="default-search"
                                        placeholder="Référence courrier arrivée"
                                        wrapper-class="w-64"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 max-h-[600px] overflow-x-auto shadow-md rounded-xl">
                                <table class="table table-auto w-full text-base text-left text-primary-txt">
                                    <thead class="sticky top-0 z-10 text-center uppercase whitespace-nowrap text-primary-txt bg-primary-form">
                                        <tr class="h-10">
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N°</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° d'ordre d'arrivée</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Types de Courrier</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° BE</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° Dossier</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Catégorie</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Date Arrivée</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° Courrier</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Date Courrier</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Référence</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Caractère</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Désignation</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Prénom Nom</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° LOT</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Superficie</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">N° TF</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Lotissement</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Date</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Heure</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Lieu</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Nombre Pièces</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Objet</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Expéditeur</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Agent Traiteur</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Observation</th>
                                            <th class="px-2 py-2 text-xs font-semibold text-center text-primary whitespace-nowrap">Fichier PDF</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="(arrivee, index) in filteredInstance"
                                            :key="arrivee.id"
                                            class="h-8 bg-white text-xs font-semibold text-center text-primary-txt hover:bg-primary-form"
                                        >
                                            <td class="sticky left-0 z-8 px-2 py-1 text-xs border bg-white whitespace-nowrap">{{ index + 1 }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap" :class="arrivee.isOverdue ? 'text-red-600 font-bold' : ''">{{ arrivee.txt_numdordre }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_typecourrier || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numbordereau || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap"><span class="font-mono">{{ arrivee.txt_numdossier || '-' }}</span></td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_categorie || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_datearrivee) }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numcourier || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_datecourier) }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_reference || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_caractere || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_designation || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_prenomNom || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numLot || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_superficie || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_numTF || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_lotissement || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ formatDate(arrivee.dt_date) }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_heure || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_lieu || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_nombrepiece || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_objet || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_expediteur || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_agenttraiteur || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ arrivee.txt_observation || '-' }}</td>
                                            <td class="px-2 py-1 text-xs border whitespace-nowrap">
                                                <a
                                                    v-if="arrivee.fichierPDF"
                                                    :href="`/storage/${arrivee.fichierPDF}`"
                                                    target="_blank"
                                                    class="flex items-center justify-center text-primary underline"
                                                >
                                                    <img src="/iconePDF.png" alt="PDF" class="w-8 h-8 mx-auto">
                                                </a>
                                                <span v-else class="italic text-primary-txt/60">Aucun PDF</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!filteredInstance.length">
                                            <td colspan="26" class="px-6 py-12 text-center text-base text-primary-txt">
                                                Aucun courrier en instance.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
 
