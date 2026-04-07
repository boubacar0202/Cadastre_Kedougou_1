<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head } from "@inertiajs/vue3"; 
import MazBtn from "maz-ui/components/MazBtn"; 
import BaseInput from "@/Components/BaseInput.vue";
import DeleteModal from "@/Components/DeleteModal.vue";
import { Link } from '@inertiajs/vue3';
import { ref } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
  
defineOptions({ layout: DefaultLayout });

const loadingDelete = ref(false); 
const showDeleteModal = ref(false)
const deleteCode = ref("")
const selectedMutation = ref(null) 

const props = defineProps({
    mutations: {
        type: Array,
        default: () => []
    }
}); 

const supprimerMutation = (mutation) => {
    if (!mutation || !mutation.id) return

    selectedMutation.value = mutation
    deleteCode.value = ""
    showDeleteModal.value = true
}

const confirmerSuppression = async (code) => {

    if (!selectedMutation.value || loadingDelete.value) return;

    if (!code?.trim()) {
        toast.error("Veuillez entrer le code administrateur.")
        return
    }

    try {
        loadingDelete.value = true;

        const url = route('parametre.mutation.destroy', selectedMutation.value.id);

        const { data } = await axios.delete(url, { data: { code } });

        if (data.success) {
            toast.success(data.message)
            showDeleteModal.value = false
            Inertia.reload({ preserveState: false })
        } else {
            toast.error(data.message || "Code invalide.")
        }

    } catch (error) {
        console.error("Erreur suppression :", error)
        if (error.response?.status === 403) {
            toast.error(error.response.data.message || "Code invalide, veuillez contacter l'administrateur.")
        } else {
            toast.error("Impossible de supprimer le lotissement.")
        }
    } finally {
        loadingDelete.value = false;
    }
}

</script>
 
<template>
    <Head title="Mutations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Liste des Mutations</h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Liste des Mutations</h1>
                        </div>

                        <!-- MODAL DE SUPPRESSION --> 
                        <div>
                            <DeleteModal
                                :show="showDeleteModal"
                                :itemName="selectedMutation?.txt_numeroMutation || ''"
                                :loading="loadingDelete"
                                @update:show="showDeleteModal = $event"
                                @confirm="confirmerSuppression"
                            />
                        </div>
                         
                        <div class="p-6">  
                            <div class="max-h-[600px] overflow-x-auto shadow-md sm:rounded-xl mt-4">
                                <div class="container">
                                    <div class="card-body">
                                        <table class="table table-auto w-full text-base text-left text-gray-500">
                                            <thead
                                                class="sticky top-0 z-10 text-primary-txt text-center bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap"
                                            >
                                                <!-- Ligne des titres principaux -->
                                                <tr class="h-10">
                                                    <th colspan="31"class="px-4 py-3 text-lg text-primary text-center border bg-primary-only font-bold whitespace-normal">
                                                        Proprietaire
                                                    </th>
                                                    <th colspan="33" class="px-4 py-3 text-lg text-white text-center border bg-primary font-bold">
                                                        ACQUÉREUR
                                                    </th>
                                                    <th rowspan="2" class="px-4 py-3 text-lg text-white text-center border bg-primary-only font-bold">
                                                        LES FICHIERS PDF
                                                    </th> 
                                                    <th rowspan="4" class="px-4 py-3 text-lg text-white text-center border bg-primary-only font-bold">
                                                        ACTIONS
                                                    </th>
                                                </tr>
 
                                                <tr>
                                                    <!-- Colonnes Propriétaire --> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">ID</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_numeroMutation</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">Prénom & Nom</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_nicadMutation</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">slt_titulaireMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_nationaliteMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">slt_civiliteMT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">slt_pieceMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_numPieceMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">dt_date_delivranceMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">dt_date_naissanceMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_lieu_naissanceMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_adresseMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">tel_telephoneMT</th>  
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_representantMT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">tel_telRepresentantMT</th>  
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">slt_categoriePM_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_formJuridiquePM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_denominationPM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_nineaPM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_perssonneRepresentantPM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_fonctionPM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_telephonePM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_emailPM_MT</th> 
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_adressePM_MT</th>  
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">slt_etablissementPA_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_personneResponsablePA_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_fonctionResponsablePA_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_telephonePA_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_emailPA_MT</th>
                                                    <th class="px-4 py-3 text-sm text-primary-txt text-center border bg-primary-only font-bold">txt_ministèreTutelePA_MT</th>
                                                    <!-- Colonnes Acquéreur -->
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">Prénom & Nom</th> 
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">slt_titulaireMutation</th> 
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_nationaliteMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">slt_civiliteMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">slt_pieceMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_numPieceMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">dt_date_delivranceMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">dt_date_naissanceMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_lieu_naissanceMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_adresseMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">tel_telephoneMutation</th> 
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_representantMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">tel_telRepresentantMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_typeActeMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_nomNotaireMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">dt_dateMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_jugementMutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_observationMutation</th> 
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">slt_categoriePM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_formJuridiquePM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_denominationPM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_nineaPM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_perssonneRepresentantPM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_fonctionPM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_telephonePM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_emailPM_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_adressePM_Mutation</th> 
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">slt_etablissementPA_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_personneResponsablePA_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_fonctionResponsablePA_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_telephonePA_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_emailPA_Mutation</th>
                                                    <th class="px-4 py-3 text-sm text-white text-center border bg-primary font-bold">txt_ministèreTutelePA_Mutation</th>
  
                                                </tr>
                                            </thead>
 
                                            <tbody>
                                                <tr 
                                                    v-for="(mutation, index) in props.mutations" :key="mutation.id"
                                                    class="h-8 bg-white text-xs font-bold text-primary-txt text-center hover:bg-primary-form"
                                                >
                                                    <!-- titulaire -->
                                                    <td class="sticky left-0 z-8 border bg-white px-6 py-4 font-bold">{{ index + 1 }}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_numeroMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_prenomMT +' '+mutation.txt_nomMT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_nicadMutation || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.slt_titulaireMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_nationaliteMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.slt_civiliteMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.slt_pieceMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_numPieceMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.dt_date_delivranceMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.dt_date_naissanceMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_lieu_naissanceMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_adresseMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.tel_telephoneMT || '-'}}</td>  
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_representantMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.tel_telRepresentantMT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.slt_categoriePM_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_formJuridiquePM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_denominationPM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_nineaPM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_perssonneRepresentantPM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_fonctionPM_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_telephonePM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_emailPM_MT || '-'}}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_adressePM_MT || '-'}}</td>  
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.slt_etablissementPA_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_personneResponsablePA_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_fonctionResponsablePA_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_telephonePA_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_emailPA_MT || '-'}}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{mutation.txt_ministèreTutelePA_MT || '-'}}</td>
                                                    <!-- acquereur -->
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.slt_titulaireMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_prenomMutation +' '+ mutation.txt_nomMutation || '-'}}</td> 
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_nationaliteMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.slt_civiliteMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.slt_pieceMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_numPieceMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.dt_date_delivranceMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.dt_date_naissanceMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_lieu_naissanceMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_adresseMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.tel_telephoneMutation || '-'}}</td> 
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_representantMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.tel_telRepresentantMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_typeActeMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_nomNotaireMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.dt_dateMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_jugementMutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_observationMutation || '-'}}</td> 
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.slt_categoriePM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_formJuridiquePM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_denominationPM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_nineaPM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_perssonneRepresentantPM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_fonctionPM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_telephonePM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_emailPM_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_adressePM_Mutation || '-'}}</td> 
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.slt_etablissementPA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_personneResponsablePA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_fonctionResponsablePA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_telephonePA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_emailPA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{mutation.txt_ministèreTutelePA_Mutation || '-'}}</td>
                                                    <td class="px-2 py-1 border bg-primary-only whitespace-nowrap">{{fichierPDFMutation || '-'}}</td>
                        
                                                    <!-- Actions -->
                                                    <td class="px-2 py-1 border flex justify-center gap-2"> 
                                                        <Link :href="`/parametre/mutationEdit/${mutation.id}`">
                                                            <MazBtn size="sm" 
                                                                class="h-8 w-28 text-white bg-gradient-to-r from-green-500 via-green-600 
                                                                to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50 
                                                                dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center me-2 mb-2">
                                                                Modifier
                                                            </MazBtn>
                                                        </Link>
                             
                                                        <MazBtn size="sm" color="danger" @click="() => supprimerMutation(mutation)" :disabled="loadingDelete"
                                                            class="h-8 w-28 text-white bg-gradient-to-r from-danger-500 via-danger-600 
                                                            to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                            focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50 
                                                            dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-5 
                                                            py-2.5 text-center me-2 mb-2">
                                                            Supprimer
                                                        </MazBtn>
                                                    </td>
                                                </tr>

                                                <!-- si aucun lot -->
                                                <tr v-if="props.mutations.length === 0"> 
                                                    <td class="px-6 py-4 text-center text-gray-400 italic" colspan="67">Aucun lotissement trouvé.</td>
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
    </AuthenticatedLayout>
</template>
 