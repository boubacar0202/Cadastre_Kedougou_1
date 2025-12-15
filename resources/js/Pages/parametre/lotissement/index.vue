<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import MazBtn from "maz-ui/components/MazBtn";
import DeleteModal from "@/Components/DeleteModal.vue";
import { Link } from '@inertiajs/vue3';
import { Head } from "@inertiajs/vue3";
import { ref, watch, nextTick } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import { Inertia } from "@inertiajs/inertia";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    lotissements: {
        type: Array,
        default: () => []
    }
});
 
const loadingDelete = ref(false);
 
const showDeleteModal = ref(false)
const deleteCode = ref("")
const selectedLotissement = ref(null) 

// const lotissements = computed(() => {
//     const searchNumero = normalize(numero.value || '');

//     const filtered = props.lotissements.filter(lotissement => {
//         const denominationLotissement = normalize(lotissement?.txt_denomination || '');

//         return !searchNumero || denominationLotissement.includes(searchNumero);
//     });

//     // Tri croissant par numéro (ex: "12/2024")
//     const sorted = filtered.sort((a, b) => {
//         const [numA] = (a?.txt_denomination || "0").split("/");
//         const [numB] = (b?.txt_denomination || "0").split("/");

//         return parseInt(numA, 10) - parseInt(numB, 10);
//     });

//     return sorted;
// }); 
// // total enregistrements
// const totalEnregistrement = computed(() => lotissements.value.length);
 
const supprimerLotissement = (lotissement) => {
    if (!lotissement || !lotissement.id) return

    selectedLotissement.value = lotissement
    deleteCode.value = ""
    showDeleteModal.value = true
}
 
const confirmerSuppression = async (code) => {
    if (!selectedLotissement.value || loadingDelete.value) return;

    if (!code?.trim()) {
        toast.error("Veuillez entrer le code administrateur.")
        return
    }

    try {
        loadingDelete.value = true;

        const url = route('parametre.lotissement.destroy', selectedLotissement.value.id);

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
  <Head title="Liste Lotissement" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">Liste Lotissements</h2>
    </template>

    <div class="py-12">
      <div class="flex justify-center">
        <div class="w-full max-w-8xl">
          <div class="bg-white shadow md:rounded-xl">
            <div class="p-4 border-b sm:rounded-t-xl bg-primary">
              <h1 class="text-lg text-white font-semibold">Liste des lotissements</h1>
                <!-- <h1 class="text-xl font-bold text-primary-txt">
                    Enregistrements : 
                    <span v-if="totalEnregistrement>0" class="text-gray-600">
                        ({{ totalEnregistrement }})
                    </span>
                    <span v-else class="text-red-600">
                        Aucun enregistrement
                    </span> 
                </h1>  -->
            </div>

            <div>
                <DeleteModal 
                    :show="showDeleteModal"
                    :itemName="selectedLotissement?.txt_denomination || ''"
                    :loading="loadingDelete"
                    @update:show="showDeleteModal = $event"
                    @confirm="confirmerSuppression"
                />
            </div>

            <div class="p-7"> 
              <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl mt-2">
                <div class="container">
                  <div class="card-body">
                    <table class="table table-auto w-full text-base text-left text-gray-500">
                      <thead
                        class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap"
                      >
                        <tr class="h-10">
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">N°</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Département</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Arrondissement</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Commune</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Dénomination</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Superficie (ha)</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Nombre de Parcelles</th>
                          <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-bold">Documents Afferants</th>
                          <th class="px-4 py-1 text-xs text-center bg-primary-only font-bold">ACTIONS</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr
                          v-for="(lot, index) in lotissements"
                          :key="lot?.id ?? index"
                          class="h-8 bg-white text-xs text-primary-txt text-center font-bold hover:bg-primary-form"
                        >
                          <td class="sticky left-0 z-8 border bg-white px-2 py-1 font-bold">{{ index + 1 }}</td> 
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.departement?.slt_departement || '-' }}</td>
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.arrondissement?.slt_arrondissement || '-' }}</td>
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.commune?.slt_commune || '-' }}</td>
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.txt_denomination || '-' }}</td>
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.txt_surfaceLotissement || '-' }}</td> 
                          <td class="px-2 py-1 text-xs border whitespace-nowrap">{{ lot.txt_nombreParcelle || '-' }}</td> 
                          <td class="px-2 py-1 text-xs border">
                            <div v-if="lot.txt_documentsAfferants">
                              <a :href="`/storage/${lot.txt_documentsAfferants}?t=${Date.now()}`" target="_blank" class="text-blue-600 underline">
                                Voir PDF
                              </a>
                            </div>
                            <div v-else class="text-gray-400 italic">Aucun PDF</div>
                          </td>

                          <!-- Actions -->
                          <td class="px-2 py-1 border flex justify-center gap-2">
                            <!-- Edit -->  
                            <Link :href="`/parametre/lotissement/edit/${lot.id}`">
                              <MazBtn 
                                  size="large"  
                                  :loading="loading"
                                  class="h-8 w-36 text-white bg-gradient-to-r from-green-500 via-green-600 
                                  to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                  focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50 
                                  dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-5 
                                  py-2.5 text-center me-2 mb-2">
                                  Modifier
                              </MazBtn>
                            </Link>
 
                            <!-- Delete -->
                            <MazBtn size="large" color="danger" @click="supprimerLotissement(lot)" :disabled="loadingDelete"
                                class="h-8 w-36 text-white bg-gradient-to-r from-danger-500 via-danger-600 
                                to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50 
                                dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-5 
                                py-2.5 text-center me-2 mb-2">
                              Supprimer
                            </MazBtn>
                          </td>
                        </tr>

                        <!-- si aucun lot -->
                        <tr v-if="!lotissements || lotissements.length === 0">
                          <td class="px-6 py-4 text-center text-gray-400 italic" colspan="9">Aucun lotissement trouvé.</td>
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
