<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';

const props = defineProps({
    terrain: Object,
    terrains: Array,
});

onMounted(() => {
    console.log(props.terrains); // Vérifie la structure des données
});

// Fonction pour formater la date
const formatDate = (dateString) => {
    if (!dateString) return 'Date inconnue';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
 

</script>

<template>
    <Head title="Donnee">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Base de données
            </h2>
            
            <!-- Vérification pour afficher un message si aucune donnée -->
            <template v-if="!terrains || terrains.length === 0">
                <p>Aucun terrain trouvé.</p>
            </template>
           
        </template v-else>
     
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-8 lg:px-12">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <div class="container">
                        <form @submit.prevent="searchDossier" class="max-w-md mx-auto">
                            <div class="sm:col-span-2">
                                <div class="flex items-center space-x-2">
                                    <div class="relative flex-grow">
                                        <input 
                                            v-model.trim="numero"
                                            type="search"
                                            id="default-search"
                                            aria-label="Rechercher"
                                            class="h-10 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                            placeholder="Entrez le numéro du dossier"
                                            required
                                        />
                                    </div>
                                    <MazBtn 
                                        type="submit" 
                                        no-shadow 
                                        no-hover-effect
                                        title="Rechercher"
                                        class="h-10 bg-gradient-to-r from-primary via-primary-light to-primary-dark 
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-primary 
                                                dark:focus:ring-primary-dark shadow-lg shadow-primary/50 
                                                dark:shadow-lg dark:shadow-primary-dark font-medium rounded-lg text-sm 
                                                px-5 py-2.5 text-center"
                                    >
                                        Recherche
                                    </MazBtn>
                                </div>
                            </div>
                        </form>
                        <div class="card">
                            <div class="card-header">
                                <div class="p-4 border-b bg-gray-100">
                                    <h1 class="text-lg font-semibold">Base de données</h1>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-strictped table-bordered">
                                    <thead >
                                        <tr>
                                            <th scope="col" class="px-6 py-3">
                                                N°
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_dossier
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Region
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Departement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Arrondisssement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Commune
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_dordre
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_service_dendu
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_etat_cession
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_cession_definitive
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_date_creation
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Numéro_Parcelle
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_lotissement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_HorsLotissement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_lotissement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_section
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_parcelle
                                            </th>
                                            <th>
                                                txt_appartement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_titre
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                nbr_surface
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_document_admin
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_deliberation
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_date_deliberation
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_nicad  
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                rd_immatricalation_terrain
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_dependant_domaine
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                ussu_bornage
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_titre_mere
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_lf
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_num_requisition
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_surface_bornage
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_date_bornage
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_nom_geometre
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_titulaire
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_nationalite
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_civilite
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_prenom
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_nom
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_piece
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_numPiece
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_date_delivrance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_date_naissance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_lieu_naissance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_adresse
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                tel_telephone
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_ninea
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                eml_email
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_representant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_telRepresentant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_reference_usage
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                slt_residence
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_nomOccupantTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                txt_numAppartementTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                txt_activiteTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                txt_nineaTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                tel_telephoneTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                nbr_montantLoyerTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                dt_dateNaissanceTG
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                txt_cniPasseportTG
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                dt_dateDelivranceTG
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                nbr_montantLoyerTotal
                                            </th> 
                                            <th scope="col" class="px-6 py-3">
                                                nbr_TVATotal
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                ACTIONS
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="terrain in terrains" :key="terrain.id"  class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                
                                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                {{ terrain.dossier ? terrain.dossier.id : 'Dossier inconnu' }}
                                            </th>
                                            <td class="px-6 py-4">
                                                {{ terrain.dossier ? terrain.dossier.txt_num_dossier : 'Dossier inconnu' }}
                                            </td>
                                            <!-- Affichage de la région, département et commune -->
                                            <td class="px-6 py-4">
                                                {{ terrain.region ? terrain.region.slt_region : 'Région inconnue' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.departement ? terrain.departement.slt_departement : 'Département inconnu' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.arrondissement ? terrain.arrondissement.slt_arrondissement : 'Arrondissement inconnue' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.commune ? terrain.commune.slt_commune : 'Commune inconnue' }}
                                            </td>
                                            <!-- Autres champs -->
                                            <td class="px-6 py-4">
                                                {{ terrain.dossier.txt_num_dordre || 'Numéro de dossier inconnu' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.dossier.slt_service_rendu || 'Service inconnu' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.dossier.txt_etat_cession || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.dossier.txt_cession_definitive || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ formatDate(terrain.dt_date_deliberation) || 'Null' }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_parcelle || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_lotissement || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_HorsLotissement || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_lotissement || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_section || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_parcelle || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_appartement || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_titre || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.nbr_surface || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.slt_document_admin || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_num_deliberation || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ formatDate(terrain.dt_date_deliberation) || 'Null'}}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ terrain.txt_nicad || 'Null'}}  
                                            </td>
                                            <!--  -->
                                            <td scope="col" class="px-6 py-3">
                                                {{ terrain.references_cadastrales?.rd_immatriculation_terrain || 'Null' }}   
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.slt_dependant_domaine || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.ussu_bornage || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.txt_titre_mere || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.slt_lf || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.txt_num_requisition || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.txt_surface_bornage || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{formatDate(terrain.references_cadastrales?.dt_date_bornage) || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_cadastrales?.txt_nom_geometre || 'Null'}}
                                            </td>
                                            <!--  -->
                                            <td scope="col" class="px-6 py-3">
                                                {{ terrain.titulaire && terrain.titulaire.slt_titulaire ? terrain.titulaire.slt_titulaire : 'Nom definie' }}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_nationalite || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.slt_civilite || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_prenom || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_nom || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.slt_piece || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_numPiece || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{formatDate(terrain.titulaire.dt_date_delivrance) || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{formatDate(terrain.titulaire.dt_date_naissance) || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_lieu_naissance || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_adresse || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.tel_telephone || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_ninea || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.eml_email || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.txt_representant || 'Null'}}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.titulaire.tel_telRepresentant || 'Null'}}
                                            </td>

                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.slt_usage || 'Null' }}
                                            </td>
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.slt_residence || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.txt_nomOccupantTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3" >
                                                {{terrain.references_usages?.txt_numAppartementTG || 'Null' }}
                                                <!-- {{ usage.txt_nomOccupantTG || 'Null' }} -->
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.txt_activiteTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.txt_nineaTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.tel_telephoneTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.nbr_montantLoyerTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.dt_dateNaissanceTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.txt_cniPasseportTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.dt_dateDelivranceTG || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.nbr_montantLoyerTotal || 'Null' }}
                                            </td> 
                                            <td scope="col" class="px-6 py-3">
                                                {{terrain.references_usages?.nbr_TVATotal || 'Null' }}
                                            </td>
                                            <td class="flex items px-6 py-6">
                                                <div class="mt-2">
                                                    <InertiaLink :href="`/secreatrit/create/${terrain.id}`">
                                                        <MazBtn pastel size="sm">Modifier</MazBtn>
                                                    </InertiaLink>                                                
                                                </div>
                                                <div class="container">
                                                    <p>.</p>
                                                </div>
                                                <div class="mt-2">
                                                    <MazBtn color="danger" pastel size="sm">Supprimer</MazBtn>
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
    </AuthenticatedLayout>
</template>
