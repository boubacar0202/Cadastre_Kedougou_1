<script setup>
import { computed, ref, watch } from "vue"; 
import MazBtn from "maz-ui/components/MazBtn";
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';
import BaseInput from "@/Components/BaseInput.vue";

const props = defineProps({
    show: { type: Boolean, required: true },
    itemName: { type: String, default: "" },
    mutation: { type: Object, default: null },
    loading: { type: Boolean, default: false }
});

const displayName = computed(() => props.itemName || props.mutation?.txt_numeroMutation || "");

const emit = defineEmits(["update:show", "confirm"]);

const deleteCode = ref("");
const adminCode = ref("");
const showAdminPassword = ref(false);
const showDeletePassword = ref(false);

// Réinitialiser les codes à l'ouverture du modal
watch(() => props.show, (newVal) => {
    if (newVal) {
        deleteCode.value = "";
        adminCode.value = "";
        showAdminPassword.value = false;
        showDeletePassword.value = false;
    }
});

// Fermer le modal
const closeModal = () => {
    emit("update:show", false);
    deleteCode.value = "";
    adminCode.value = "";
    showAdminPassword.value = false;
    showDeletePassword.value = false;
};

// Confirmer la suppression
const confirmer = () => {
    // Vérification du code de confirmation
    if (deleteCode.value.trim().toUpperCase() !== 'SUPPRIMER') {
        alert('Veuillez taper exactement "SUPPRIMER" pour confirmer');
        return;
    }

    // Vérification du code admin
    if (!adminCode.value || adminCode.value.trim() === '') {
        alert('Veuillez entrer le code administrateur');
        return;
    }

    // Envoyer les deux codes
    emit("confirm", {
        deleteCode: deleteCode.value,
        adminCode: adminCode.value
    });
};

// Basculer la visibilité du mot de passe admin
const toggleAdminPassword = () => {
    showAdminPassword.value = !showAdminPassword.value;
};

// Basculer la visibilité du mot de passe de suppression
const toggleDeletePassword = () => {
    showDeletePassword.value = !showDeletePassword.value;
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[2000]">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-red-600 text-center mb-4">
                Confirmer la suppression
            </h2>

            <p class="text-center text-primary-txt mb-4">
                Voulez-vous vraiment supprimer :
                <br>
                <span class="font-semibold text-primary">{{ displayName }}</span>
            </p>

            <BaseInput
                v-model="adminCode"
                label="Code administrateur"
                :type="showAdminPassword ? 'text' : 'password'"
                autocomplete="off"
                placeholder="Code administrateur"
                wrapper-class="mb-2 w-full"
            >
                <template #append>
                    <button
                        type="button"
                        @click="toggleAdminPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt/60 hover:text-primary"
                    >
                        <EyeIcon v-if="!showAdminPassword" class="h-5 w-5" />
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </button>
                </template>
            </BaseInput>

            <BaseInput
                v-model="deleteCode"
                label="Code de suppression"
                :type="showDeletePassword ? 'text' : 'password'"
                autocomplete="off"
                placeholder="Entrez votre code de suppression"
                wrapper-class="mb-4 w-full"
            >
                <template #append>
                    <button
                        type="button"
                        @click="toggleDeletePassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt/60 hover:text-primary"
                    >
                        <EyeIcon v-if="!showDeletePassword" class="h-5 w-5"/>
                        <EyeSlashIcon v-else class="h-5 w-5" />
                    </button>
                </template>
            </BaseInput>

            <div class="flex justify-center items-center gap-3">
                <MazBtn
                    size="large"
                    class="h-10 w-36 bg-gradient-to-r from-green-400 to-green-600 text-white font-semibold rounded-2xl"
                    @click="closeModal"
                    :disabled="loading"
                >
                    Annuler
                </MazBtn>

                <MazBtn
                    size="large"
                    :loading="loading"
                    class="h-10 w-36 bg-gradient-to-r from-red-500 via-red-600 to-red-700 text-white font-semibold rounded-2xl"
                    @click="confirmer"
                    :disabled="loading || deleteCode.trim().toUpperCase() !== 'SUPPRIMER' || !adminCode || adminCode.trim() === ''"
                >
                    Supprimer
                </MazBtn>
            </div>
        </div>
    </div>
</template>