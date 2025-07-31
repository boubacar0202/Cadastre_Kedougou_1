<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import DefaultLayout from "@/Layouts/DefaultLayout.vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
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

    <Head title="Matrice"/>
    <AuthenticatedLayout>   

        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-primary-txt"
            >
                Matrice Cadastrale
            </h2>
            <template v-if="!terrains || terrains.length === 0">
                <p>Aucun terrain trouvé.</p>
            </template>
           
        </template>
     
        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-7xl">  
                    <div class="bg-white shadow-md rounded-lg "> <br> 
                        <div class="mx-auto max-w-7xl sm:px-8 lg:px-12 mt-4 mb-4">
                            <div class="card-header">
                                <div class="p-4 border-b bg-primary-only">
                                    <h1 class="text-3xl text-primary-txt font-bold">Matrice Cadastrale</h1>
                                </div>
                            </div>
 
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-8">
                                <div class="container"> 
                                    <div class="card"> 
                                        <div class="card-body"> 
                                            <table class="table table-sm table-strictped table-bordered">
                                                
                                                <thead >
                                                    <tr>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center whitespace-nowrap">N°</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Region</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Departement</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Commune</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Village/Quartier</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Rue</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Section</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Lotissement</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Numéro Lot</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Numéro_Parcelle</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">NICAD</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Numéro_Prote</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Superfice Legale</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Superficie Batie</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Type_Batie</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Niveau</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Superfice_NBatie</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Type_Designation</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Cat_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Source_Donnee</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Titre Mere</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Titre Parcelle</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Type_DocFoncier</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Nature_Juridique</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Prenom_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Nom_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">CIN_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Passport_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">DateDelivrance_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Date_Naissance</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Lieu_Naissance</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">NINEA_PRO</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Telephone1_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Telephone2_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Email_Pro</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Prenom_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Nom_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">CIN_Ocupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Passeport_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Date_Delivrance</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">DateNaiss_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">LieuNaiss_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">NINEA_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Tel_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Email_Occupant</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Valeur_Locative</th>
                                                        <th scope="col" class="px-6 py-6 border-l bg-primary text-white text-center font-bold whitespace-nowrap">Observations</th> 
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="terrain in terrains" :key="terrain.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                        <th scope="row" class="px-6 py-4 border-l border border-primary-only font-bold text-primary-txt whitespace-nowrap dark:text-white">
                                                            {{ terrain.dossier ? terrain.dossier.txt_num_dordre : 'Dossier Inconnue' }}
                                                        </th>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.region?.slt_region || 'Region inconnue' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.departement?.slt_departement || 'Departement inconnue' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.commune?.slt_commune || 'Commune inconnue' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_lotissement || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Rue</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_num_section || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_lotissement || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_num_lotissement || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_num_parcelle || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_nicad || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Numéro_Prote</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.nbr_surface || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.nbr_surface_bati || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{terrain.evaluations_batis?.currentCat || 'Null' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Niveau</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Superfice_NBatie</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Type_Designation</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Cat_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Source_Donnee</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.txt_titre_mere || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Titre Parcelle</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Type_DocFoncier</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.references_cadastrales?.rd_immatriculation_terrain}}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.txt_prenom || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.txt_nom || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.slt_piece || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.slt_piece || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ formatDate(terrain.dt_date_deliberation) || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ formatDate(terrain.titulaire?.dt_date_naissance) || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.txt_lieu_naissance || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.txt_ninea || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.tel_telephone || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.tel_telephone || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.eml_email || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.txt_representant || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Nom_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">CIN_Ocupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Passeport_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Date_Delivrance</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">DateNaiss_Occupant</td>txt_num_titre
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">LieuNaiss_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">NINEA_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">{{ terrain.titulaire?.tel_telRepresentant || '-' }}</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Email_Occupant</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Valeur_Locative</td>
                                                        <td class="px-6 py-3 text-center border-l border border-primary-only whitespace-nowrap">Observations</td>
                                                    </tr> 
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div><br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
