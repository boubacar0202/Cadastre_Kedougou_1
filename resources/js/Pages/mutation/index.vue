<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head } from "@inertiajs/vue3"; 
import MazBtn from "maz-ui/components/MazBtn";  
import DeleteModal from "@/Components/DeleteModal.vue";
import { Link } from '@inertiajs/vue3'; 
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
import { ref, computed } from "vue";
  
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
 
// Tri par numéro de dossier (ordre croissant, tri naturel)
// Tri par année puis par numéro d'ordre
const sortedMutations = computed(() => {
    return [...props.mutations].sort((a, b) => {
        const refA = a.txt_num_dossier || a.txt_numeroMutation || "";
        const refB = b.txt_num_dossier || b.txt_numeroMutation || "";

        // Analyse d'une référence "ordre/année"
        const parseRef = (ref) => {
            const parts = String(ref).split('/');
            if (parts.length >= 2) {
                const order = parseInt(parts[0], 10);
                const year = parseInt(parts[parts.length - 1], 10);
                if (!isNaN(order) && !isNaN(year)) {
                    return { order, year, valid: true };
                }
            }
            // Si pas de slash mais numérique : on considère année = 0 (pour les placer en premier)
            const num = parseInt(ref, 10);
            if (!isNaN(num)) {
                return { order: num, year: 0, valid: true };
            }
            // Référence invalide
            return { order: 0, year: 0, valid: false };
        };

        const pA = parseRef(refA);
        const pB = parseRef(refB);

        // Les références invalides vont à la fin
        if (!pA.valid && !pB.valid) return 0;
        if (!pA.valid) return 1;
        if (!pB.valid) return -1;

        // Comparaison : d'abord l'année, puis le numéro d'ordre
        if (pA.year !== pB.year) {
            return pA.year - pB.year;
        }
        return pA.order - pB.order;
    });
});
 
// Détection des numéros de dossier en doublon
const duplicateDossierNumbers = computed(() => {
    const counts = {};
    props.mutations.forEach(m => {
        const num = (m.txt_num_dossier || m.txt_numeroMutation || '').trim();
        if (num) {
            counts[num] = (counts[num] || 0) + 1;
        }
    });
    const duplicates = new Set();
    Object.keys(counts).forEach(num => {
        if (counts[num] > 1) {
            duplicates.add(num);
        }
    });
    return duplicates;
});

const isDuplicateDossier = (mutation) => {
    const num = (mutation.txt_num_dossier || mutation.txt_numeroMutation || '').trim();
    return num && duplicateDossierNumbers.value.has(num);
};

// Fonction pour afficher le modal de suppression
const supprimerMutation = (mutation) => {
    if (!mutation || !mutation.id) return

    selectedMutation.value = mutation
    deleteCode.value = ""
    showDeleteModal.value = true
}

const confirmerSuppression = async ({ deleteCode, adminCode } = {}) => {

    if (!selectedMutation.value || loadingDelete.value) return;

    // ✅ Vérifier le code administrateur (celui qui valide vraiment)
    if (!adminCode || String(adminCode).trim() === '') {
        toast.error("Veuillez entrer le code administrateur.")
        return
    }

    try {
        loadingDelete.value = true;

        const url = route('parametre.mutation.destroy', selectedMutation.value.id);

        // ✅ Envoyer le bon code à l'API (adminCode)
        const { data } = await axios.delete(url, {
            data: {
                code: String(adminCode).trim(),
                deleteCode: String(deleteCode ?? '').trim()
            }
        });

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
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">
        Liste des Mutations
      </h2>
    </template>

    <div class="py-12">
      <div class="flex justify-center">
        <div class="w-full max-w-8xl">
          <div class="bg-white shadow-md rounded-xl">
            <div class="p-4 border-b bg-primary sm:rounded-t-xl">
              <h1 class="text-lg text-white font-semibold">
                Liste des Mutations
              </h1>
            </div>

            <!-- MODAL DE SUPPRESSION -->
            <DeleteModal
              :show="showDeleteModal"
              :itemName="selectedMutation?.txt_numeroMutation || ''"
              :loading="loadingDelete"
              @update:show="showDeleteModal = $event"
              @confirm="confirmerSuppression"
            />

            <div class="p-6">
              <div class="max-h-[600px] overflow-x-auto shadow-md sm:rounded-xl mt-4">
                <table class="table-auto w-full text-base text-left text-primary-txt">
                  <thead
                    class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form whitespace-nowrap"
                  >
                    <!-- Ligne des titres principaux -->
                    <tr class="h-10">
                      <th rowspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        #
                      </th>
                      <th rowspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        N° Dossier
                      </th>
                      <th rowspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        N° Lotissement
                      </th>
                      <th colspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        Propriétaire
                      </th>
                      <th colspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary-dark font-semibold">
                        Acquéreur
                      </th>
                      <th rowspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        Type Actes
                      </th>  
                      <th rowspan="2" class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        Fichier PDF
                      </th>
                      <th rowspan="2" class="px-4 py-1 text-xs text-center border bg-primary-only font-semibold">
                        Actions
                      </th>
                    </tr>

                    <!-- Sous-titres -->
                    <tr>
                      <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        Nom & Prénom
                      </th>
                      <th class="px-4 py-1 text-xs text-white text-center border bg-primary font-semibold">
                        N° Pièce
                      </th>
                      <th class="px-4 py-1 text-xs text-white text-center border bg-primary-dark font-semibold">
                        Nom & Prénom
                      </th> 
                      <th class="px-4 py-1 text-xs text-white text-center border bg-primary-dark font-semibold">
                        Numéro CIN
                      </th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr
                      v-for="(mutation, index) in sortedMutations"
                      :key="mutation.id"
                      class="h-8 bg-white text-xs font-semibold text-primary-txt text-center hover:bg-primary-form"
                    >
                      <td class="sticky left-0 z-8 border bg-white px-2 py-1 text-xs">{{ index + 1 }}</td>
                      <td class="border px-2 py-1 text-xs whitespace-nowrap" :class="{ 'bg-primary-form text-primary font-bold': isDuplicateDossier(mutation) }">
                        {{ mutation.txt_numeroMutation || '-' }}
                      </td>
                      <td class="border px-2 py-1 text-xs whitespace-nowrap">
                        {{ mutation.txt_num_lotissementMutation + ' ' + mutation.txt_lotissementMutation || '-' }}
                      </td>

                      <!-- Propriétaire -->
                      <td class="border px-2 py-1 text-xs whitespace-nowrap">
                        {{ mutation.txt_prenomMT + ' ' + mutation.txt_nomMT || '-' }}
                      </td>
                      
                      <td class="border px-2 py-1 text-xs whitespace-nowrap">
                        {{ mutation.txt_numPieceMT || '-' }}
                      </td>

                      <!-- Acquéreur -->
                      <td class="border px-2 py-1 text-xs whitespace-nowrap bg-primary-form text-primary-txt">
                        {{ mutation.txt_prenomMutation + ' ' + mutation.txt_nomMutation || '-' }}
                      </td> 
                      <td class="border px-2 py-1 text-xs whitespace-nowrap bg-primary-form text-primary-txt">
                        {{ mutation.txt_numPieceMutation || '-' }}
                      </td>
                      <td class="border px-2 py-1 text-xs whitespace-nowrap">
                        {{ mutation.txt_typeActeMutation || '-' }} 
                      </td>

                      <!-- Fichier PDF -->
                      <td class="border px-2 py-1 text-xs whitespace-nowrap">
                        <a
                          v-if="mutation.fichier_pdf"
                          :href="mutation.fichier_pdf"
                          target="_blank"
                          class="text-primary underline"
                        >
                          Voir PDF
                        </a>
                        <span v-else class="text-primary-txt/60 italic">Aucun PDF</span>
                      </td>

                      <!-- Actions -->
                      <td class="border px-2 py-1 flex justify-center gap-2">
                        <Link :href="`/parametre/mutationEdit/${mutation.id}`">
                          <MazBtn
                            size="sm"
                            class="h-8 w-6 text-white bg-gradient-to-r from-green-500 via-green-600 to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5"
                          >
                            ✏️
                          </MazBtn>
                        </Link>

                        <MazBtn
                          size="sm"
                          color="danger"
                          @click="() => supprimerMutation(mutation)"
                          :disabled="loadingDelete"
                          class="h-8 w-6 text-white bg-gradient-to-r from-danger-500 via-danger-600 to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-danger-300 font-medium rounded-lg text-sm px-5 py-2.5"
                        >
                          🗑 
                        </MazBtn>
                      </td>
                    </tr>

                    <!-- Message si vide -->
                    <tr v-if="mutations.length === 0">
                      <td colspan="10" class="px-6 py-4 text-center text-xs text-primary-txt/60 italic">
                        Aucune mutation trouvée.
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
 