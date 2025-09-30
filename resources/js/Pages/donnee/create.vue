<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed  } from 'vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import { router, usePage  } from '@inertiajs/vue3'
import { Link as InertiaLink } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from "maz-ui";

const props = defineProps({
    terrain: Object,
    terrains: Array,  
});
 
const annee = ref('');
const numero = ref('')
const numeroRecherche = ref('')
const toast = useToast()

// Au clic sur "Rechercher"
function searchDossier() {
  numeroRecherche.value = numero.value.trim()
}

// Normalise une chaîne pour la comparaison
function normalize(str) {
  return str?.toString().trim().toLowerCase()
}
 
 
const terrains = computed(() => {
    // Normalisation des champs de recherche
    const searchNumero = normalize(numero.value || '')
    const searchAnnee = normalize(annee.value || '')

    return props.terrains.filter(terrain => {
        const dossierNum = normalize(terrain.dossier?.txt_num_dossier)
        const dossierAnnee = normalize(terrain.dossier?.dt_date_creation)

        const matchNumero = !searchNumero || dossierNum?.includes(searchNumero)
        const matchAnnee = !searchAnnee || dossierAnnee?.includes(searchAnnee)

        return matchNumero && matchAnnee
    })
})

 
// Fonction pour formater la date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
 

 

// Suppromer enregistrement
// function supprimerTerrain(terrain) {
//     if (confirm(`Voulez-vous vraiment supprimer ce terrain avec le NICAD : ${terrain.txt_nicad} ?`)) {
//         router.delete(route('terrains.destroy', terrain.id), {
//             preserveScroll: true,
//             onSuccess: () => {
//                 console.log('Terrain supprimé avec succès');
//             },
//             onError: (errors) => {
//                 console.error('Erreur lors de la suppression', errors);
//             }
//         });
//     }
// } 



async function supprimerTerrain(terrain) {
    if (!confirm(`Voulez-vous vraiment supprimer ce terrain NICAD : ${terrain.txt_nicad} ?`)) return;

    const saisie = prompt('Entrez votre code de confirmation :');
    if (!saisie) return;

    try {
        const res = await axios.post(route('terrain.verifier.supprimer'), {
        code: saisie,
        terrain_id: terrain.id
        });

        if (res.data.success) {
            toast.success(res.data.message);
            Inertia.reload() // retirer localement l'item
        }
    } catch (err) {
        toast.error(err.response?.data?.message || 'Erreur lors de la suppression');
    }
    }


</script>

<template>
    <Head title="Donnee">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-primary-txt"
            >
                Base de données
            </h2>
            
            <!-- Vérification pour afficher un message si aucune donnée -->
            <template v-if="!terrains || terrains.length === 0">
                <p>Aucun terrain trouvé.</p>
            </template>
           
        </template v-else>

        <div class="py-12">
            <div class="flex justify-center">   
                <div class="w-full max-w-7xl">  
                    <div class="bg-white shadow-md rounded-lg "> <br> 
                        <div class="mx-auto   sm:px-8 lg:px-12 mt-4 mb-4">  
                            <div class="card-header">
                                <div class="p-4 border-b bg-primary-only">
                                    <h1 class="text-4xl text-primary-txt font-bold">Base de données</h1>
                                </div> 
                            </div><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="flex justify-center">   
                <div class="w-full max-w-7xl">    
                    <div class="bg-white shadow-md rounded-lg "><br> 
                        <div class="mx-auto max-w-7xl sm:px-8 lg:px-12 mt-4 mb-4">  
                            <div class="card-header"> 
                                <form @submit.prevent="searchDossier" class="max-w-md mx-auto">
                             
                                        <label for="default-search"
                                            class="text-xl text-primary-txt font-bold">
                                            <b>Recherche Dossier</b>
                                        </label>
                                        <div class="flex w-full items-start space-x-4">  
                                            <div>
                                                <input 
                                                    v-model="numero"
                                                    type="search"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-9 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-menu placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    placeholder="Entrez le numéro du dossier"
                                                    required
                                                />  
                                            </div>
                                            <div>
                                                <input 
                                                    v-model="annee"
                                                    type="search"
                                                    maxlength="4"
                                                    id="default-search"
                                                    aria-label="Rechercher"
                                                    class="h-9 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        border border-primary-menu  placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    placeholder="année"
                                                /> 
                                            </div>
                                        </div> 
                                  
                                </form>
                                
                            </div>
                                
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-8">
                                <div class="container">
                                    <div class="card">    
                                        <div class="card-body">
                                            <table class="table table-sm table-strictped table-bordered">
                                                <thead >
                                                    <tr>
                                                        <th scope="col" class="px-6 py-3 text-white text-center border-r bg-primary font-bold whitespace-nowrap">N°</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° dossier</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Region</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Departement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Arrondisssement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Commune</th> 
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Service dendu</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Etat Cession</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Cession définitive</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Date création</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Numéro Parcelle</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Lotissement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Hors lotissement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° lotissement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° section</th> 
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Appartement</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° titre</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Surface</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Document admin</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° déliberation</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Date déeliberation</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Nicad</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Immatricalation terrain</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Dépendant domaine</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Ussu bornage</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Titre mére</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Livre Foncière</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° réquisition</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Surface bornage</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Date bornage</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Nom géometre</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Titulaire</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Nationalite</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Civilité</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Prénom</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Nom</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Pièce</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">N° Pièce</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Date délivrance</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Date naissance</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Lieu naissance</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Adresse</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Télephone</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Ninea</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Email</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">epresentant</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border bg-primary font-bold whitespace-nowrap">Telephone Répresentant</th> 
                                                        <!-- <h3 class="text-lg text-white text-center border-b  bg-primary font-bold whitespace-nowrap text-center">Références Usages</h3> -->
                                                        <table class="border-b border-primary text-sm">
                                                            <thead>  
                                                                <tr>
                                                                    <th colspan="12" class="px-6 py-3 whitespace-nowrap border-l border bg-primary text-white text-center">
                                                                        Références Usages
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Occupant</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Usage</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Résidence</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">N° Appartement</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Activité</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">NINEA</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Téléphone</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Loyer</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Date/Lieu Naissance</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">CNI/Passeport</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Date Délivrance</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">Loyer Total</th>
                                                                    <th scope="col" class="px-6 py-3 text-primary-txt border-l border bg-primary-only font-bold text-center">TVA Total</th>
                                                                </tr>
                                                            </thead> 
                                                        </table>

                                                        <!-- Bati -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Surface bati_sol</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Prix mètre carré</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Superficie totale</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Secteur</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Date dévaluation</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur terrain</th> 
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">CurrentCat</th>   
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur terrain_bati</th>
                                                        <!-- Domaine Principal -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Dépendant domaine PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Categorie PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Prix mètre carré PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Surface bati_sol PR</th>    
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Niveau PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Surface utile PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Coefficient PR</th>        
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Surface corriger PR</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Valeur PR</th>
                                                        <!-- Dimaine Suivants --> 
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Dependant domaine TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Categorie TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Prix mètre carre TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Surface bati_sol TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Niveau TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Surface utile TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Coefficient TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Surface corriger TG</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur TG</th>  
                                                        <!-- Cours Amenagers -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Valeur total AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Cours amenager totale AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Surface total AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Categorie total AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Prix mètre carre total AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Coefficient total AM</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l bprder bg-primary font-bold whitespace-nowrap">Valeur total AM</th>
                                                        <!-- Clotures -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Cours amenager CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Longueur avant CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Categorie  CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Prix mètre carre CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Coefficient CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur CL</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur total CL</th>
                                                        <!-- Amenagements -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Désignation AP</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur unitaire AP</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Quantile AP</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Coeficien AP</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur AP</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur totale AP</th>
                                                        <!-- Actions -->
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur venale de l'imeuble</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Valeur locative</th>
                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Date d'évaluation</th>

                                                        <th scope="col" class="px-6 py-3 text-lg text-white text-center border-l border bg-primary font-bold whitespace-nowrap">Voir le PDF</th>

                                                        <th scope="col" class="px-6 py-3 text-lg text-primary-txt text-center border-b bg-primary-only font-bold whitespace-nowrap">
                                                            ACTIONS
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
            
                                                    <tr v-for="terrain in terrains" :key="terrain.id"  
                                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            
                                                        <th class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.dossier.txt_num_dordre || '-' }}</th>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.dossier.txt_num_dossier || '-' }} </td> 
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.region ? terrain.region.slt_region : '-' }}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.departement ? terrain.departement.slt_departement : '-' }}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.arrondissement ? terrain.arrondissement.slt_arrondissement : '-' }}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.commune ? terrain.commune.slt_commune : '-' }}</td> 
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain?.dossier.slt_service_rendu || '-' }}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.dossier.txt_etat_cession || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.dossier.txt_cession_definitive || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ formatDate(terrain.dossier.dt_date_creation) || '-' }}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_num_parcelle || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_lotissement || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_HorsLotissement || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_num_lotissement || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_num_section || '-'}}</td> 
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_appartement || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_num_titre || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.nbr_surface || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.slt_document_admin || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_num_deliberation || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ formatDate(terrain.dt_date_deliberation) || '-'}}</td>
                                                        <td class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.txt_nicad || '-'}}</td>
                                                        <!--  -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.references_cadastrales?.rd_immatriculation_terrain || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.slt_dependant_domaine || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.ussu_bornage || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.txt_titre_mere || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.slt_lf || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.txt_num_requisition || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.txt_surface_bornage || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{formatDate(terrain.references_cadastrales?.dt_date_bornage) || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.references_cadastrales?.txt_nom_geometre || '-'}}</td>
                                                        <!-- Titulaire -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.slt_titulaire || '-' }} </td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_nationalite || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.slt_civilite || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_prenom || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_nom || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.slt_piece || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_numPiece || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{formatDate(terrain.titulaire?.dt_date_delivrance) || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{formatDate(terrain.titulaire?.dt_date_naissance) || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_lieu_naissance || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_adresse || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.tel_telephone || '-'}}</td> 
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.eml_email || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.txt_representant || '-'}}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.titulaire?.tel_telRepresentant || '-'}}</td>

                                                        <template v-if="terrain.references_usages && terrain.references_usages.length > 0">
                                                            <tr v-for="usage in terrain.references_usages" :key="usage.id"  class="border-b border-primary text-sm">
                                                                
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_nomOccupantTG || '-' }}</td> 
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.slt_usage || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.slt_residence || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_numAppartementTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_activiteTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_nineaTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.tel_telephoneTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.nbr_montantLoyerTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_dateLieuNaissanceTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.txt_cniPasseportTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.dt_dateDelivranceTG || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.nbr_montantLoyerTotal || '-' }}</td>
                                                                <td scope="col" class="px-6 py-3 text-primary-txt border border-primary-only text-center">{{usage.nbr_TVATotal || '-' }}</td>
                                                            </tr>
                                                        </template> 
                                                        <template v-else>
                                                                <td class="text-center text-gray-500 italic">Aucune donnée d’usage enregistrer</td>
                                                        </template>
 
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.txt_superficie_bati_sol || '-' }}</td> 
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.nbr_prix_metre_carre || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.nbr_surface || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.slt_secteur || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.txt_date_devaluation  || '-'   }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.nbr_valeur_terrain || '-' }}</td>
                                                        <!-- Domaine Principal -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.currentCat || '-' }}</td>   
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.txt_valeur_terrain_bati || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.txt_dependant_domainePR || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.slt_categoriePR || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_prix_metre_carrePR || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_surface_bati_solPR || '-' }}</td>    
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_niveauPR || '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_surface_utilePR  || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.slt_coeffPR || '-' }}</td>        
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_surface_corrigerPR ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_batis?.nbr_valeurPR || '-' }}</td>
                                                        <!-- Domaine Suivants --> 
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.slt_dependant_domaineTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.slt_categorieTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_prix_metre_carreTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_surface_bati_solTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_niveauTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_surface_utileTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.slt_coeffTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_surface_corrigerTG || '-' }}</td>
                                                        <td  scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{ terrain.evaluations_batis?.nbr_valeurTG || '-' }}</td>
                                                        <!-- Cours amenagers  -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.nbr_valeur_total_ca || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.slt_cours_amenager_totale || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.nbr_surface_ca_total ||  '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.slt_categorie_ca_total || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.nbr_prix_metre_carre_ca_total  || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.nbr_coefficient_ca_total ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_cours_amenagees?.nbr_valeur_ca_total || '-'  }}</td> 
                                                        <!-- Cours cloture  -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.slt_cours_amenager_clo || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.nbr_longueur_avant_clo  ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.slt_categorie_clo   ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.nbr_prix_metre_carre_clo || '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.nbr_coefficient_clo ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.nbr_valeur_clo || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_clotures?.nbr_valeur_total_clotur || '-' }}</td>
                                                        <!-- Amenagement -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.txt_designation_am  || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.nbr_valeur_unitaire_am  ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.nbr_quantile_am || '-' }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.slt_coeficien_am    ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.nbr_valeur_am   ||  '-'  }}</td>
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_amenagements?.nbr_valeur_totale_ap    ||  '-'  }}</td>

                                                       <!-- terrain -->
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.nbr_valeurVenaleLimeuble || '-' }}</td> 
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{terrain.evaluations_terrains?.nbr_valeurLocative || '-' }}</td> 
                                                        <td scope="col" class="px-6 py-4 font-bold text-primary-txt whitespace-nowrap border border-primary-only">{{formatDate(terrain.evaluations_terrains?.dt_dateEvaluation) || '-' }}</td> 
 
                                                        <td scope="col" class="px-6 py-3 text-center border border-primary-only font-bold whitespace-nowrap"> 
                                                            <div v-if="terrain && terrain.titulaire.fichierPDF">
                                                                <a :href="`/storage/${terrain.titulaire.fichierPDF}?t=${Date.now()}`" target="_blank" class="text-blue-600 underline">
                                                                    Voir PDF
                                                                </a>
                                                            </div>
                                                            <div v-else class="text-primary-txt italic">Aucun fichier PDF</div>
                                                        </td>
                                                        <td class="flex items px-6 py-6 border-b border-primary-only">
                                                            <div class="mt-2 ml-4">
                                                                <InertiaLink :href="`/secretariat/edit/${terrain.id}`">
                                                                    <MazBtn 
                                                                        color="white" pastel size="sm"
                                                                        class="h-8 w-28 text-white bg-gradient-to-r from-green-500 via-green-600 
                                                                        to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                        focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50 
                                                                        dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-5 
                                                                        py-2.5 text-center me-2 mb-2"
                                                                    >
                                                                        Modifier
                                                                    </MazBtn>
                                                                </InertiaLink> 
                                                            </div>
                                                           
                                                            <div class="mt-2 ml-4">
                                                                <MazBtn 
                                                                    color="danger" size="sm"   
                                                                    @click="() => supprimerTerrain(terrain)"
                                                                    class="h-8 w-28 text-white bg-gradient-to-r from-danger-500 via-danger-600 
                                                                    to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                    focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50 
                                                                    dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-5 
                                                                    py-2.5 text-center me-2 mb-2"
                                                                >
                                                                    Supprimer
                                                                </MazBtn>
                                                            </div>
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

 
