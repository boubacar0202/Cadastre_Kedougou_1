 <script setup>
import { ref, watch } from "vue"; 
import MazBtn from "maz-ui/components/MazBtn";

const props = defineProps({
  show: { type: Boolean, required: true },
  mutation: { type: Object, default: null },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(["update:show", "confirm"]);

const deleteCode = ref("");

// Réinitialiser le code à l'ouverture du modal
watch(() => props.show, (newVal) => {
  if (newVal) deleteCode.value = "";
});

// Fermer le modal
const closeModal = () => {
  emit("update:show", false);
  deleteCode.value = "";
};

// Confirmer la suppression
const confirmer = () => {
  emit("confirm", deleteCode.value);
  deleteCode.value = "";
};

</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-red-600 text-center mb-4">
                Confirmer la suppression
            </h2>

            <p class="text-center text-gray-700 mb-4">
                Voulez-vous vraiment supprimer :
                <br>
                <!-- <span class="font-semibold text-primary">{{ mutation?.txt_numeroMutation }}</span> ? -->
            </p>

                <div>
                    <input 
                    type="password"
                    autocomplete="off"
                    autocorrect="off" 
                    v-model="deleteCode" 
                    placeholder="Entrez votre code de suppression"
                    class="h-10 block w-full rounded-xl bg-white px-3 py-1.5 text-base text-primary-txt 
                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6 mb-4">
                </div> 

            <div class="flex justify-center items-center gap-3">
                <MazBtn
                    size="large"
                    class="h-10 w-36 bg-gradient-to-r from-green-400 to-green-600 text-white font-semibold rounded-2xl"
                    @click="closeModal"
                >
                    Annuler
                </MazBtn>

                <MazBtn
                    size="large"
                    :loading="loading"
                    class="h-10 w-36 bg-gradient-to-r from-red-500 via-red-600 to-red-700 text-white font-semibold rounded-2xl"
                    @click="confirmer"
                >
                    Supprimer
                </MazBtn>
            </div>
        </div>
    </div>
</template>
