<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

                                    <div>
                                        <input
                                            v-model="refCourrierArrivee"
                                            type="search"
                                            id="default-search"
                                            aria-label="Rechercher"
                                            class="h-10 block w-64 rounded-xl border border-primary-only bg-white px-3 text-sm text-primary-txt placeholder:text-primary-txt/50 focus:border-primary-menu focus:outline-none focus:ring-2 focus:ring-primary-menu/40"
                                            placeholder="Référence courrier arrivée"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 max-h-[600px] overflow-auto rounded-xl border border-primary-only">
                                <div class="container min-w-[1500px]"> 
                                    <div class="card"> 
                                        <div class="card-body"> 
                                            <table class="table-auto w-full text-left text-xs font-medium text-primary-txt">

                                                <thead class="sticky top-0 z-10 whitespace-nowrap text-center text-xs uppercase">
                                                    <tr class="h-10">  
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N°</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° d'ordre d'arrivée</th>  
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Types de Courrier</th> 
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° BE</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° Dossier</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Catégorie</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Date Arrivée</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° Courrier</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Date Courrier</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Référence</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Caractère</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Désignation</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Prénom Nom</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° LOT</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Superficie</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">N° TF</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Lotissement</th> 
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Date</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Heure</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Lieu</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Nombre Pièces</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Objet</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Expéditeur</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Agent Traiteur</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Observation</th>
                                                        <th scope="col" class="border border-primary bg-primary px-4 py-4 text-center text-xs font-semibold text-white">Fichier PDF</th> 
                                                    </tr>
                                                </thead>
                                                        
                                                <tbody sortedinstance>
                                                    <tr v-for="(arrivee, index) in filteredInstance" :key="arrivee.id"  
                                                        class="bg-white text-center text-xs font-medium text-primary-txt hover:bg-primary-form">
                                                        <td scope="col" class="sticky left-0 z-0 bg-white px-2 py-1.5 text-center text-xs font-semibold text-primary-txt border border-primary-only whitespace-nowrap">{{ index + 1 }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-semibold whitespace-nowrap border border-primary-only" :class="arrivee.isOverdue ? 'text-red-600 font-bold' : ''">{{ arrivee.txt_numdordre }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_typecourrier || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_numbordereau || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_numdossier || '-' }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_categorie || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ formatDate(arrivee.dt_datearrivee) || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_numcourier || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ formatDate(arrivee.dt_datecourier) }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_reference || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_caractere || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_designation || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_prenomNom || '-' }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_numLot || '-' }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_superficie || '-' }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_numTF || '-' }}</td> 
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_lotissement || '-' }}</td>  
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ formatDate(arrivee.dt_date) || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_heure || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_lieu || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_nombrepiece || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_objet || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_expediteur || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_agenttraiteur || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt">{{ arrivee.txt_observation || '-' }}</td>
                                                        <td scope="col" class="px-4 py-3 text-center text-xs font-medium whitespace-nowrap border border-primary-only text-primary-txt"> 
                                                            <div v-if="arrivee.fichierPDF">
                                                                <a :href="`/storage/${arrivee.fichierPDF}`" target="_blank" class="font-semibold text-primary underline flex justify-center items-center">
                                                                    <img src="/iconePDF.png" alt="PDF" class="w-8 h-8 mx-auto">
                                                                </a>
                                                            </div>
                                                            <div v-else class="text-primary-txt/60 italic">Aucun PDF</div>
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
 
