<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { toast } from "@/Components/AppToast";
import BaseInput from "@/Components/BaseInput.vue";
import { ref } from "vue";

defineOptions({ layout: DefaultLayout });

const { user } = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: user?.name || "",
    email: user?.email || "",
    password: "",
    password_confirmation: "",
});

const submitForm = () => {
    form.put(route("parametre.updateUser", user.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            toast.success(page.props.flash?.success || "Utilisateur modifié avec succès.");
            form.reset("password", "password_confirmation");
            showPassword.value = false;
            showPasswordConfirmation.value = false;
        },
        onError: () => {
            toast.error("Erreur lors de la modification.");
        },
    });
};
</script>

<template>
    <Head title="Modifier Utilisateur" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modifier un administrateur
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        
                        <!-- En-tête -->
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Modification de l'utilisateur
                            </h1>
                        </div>

                        <!-- Formulaire -->
                        <form @submit.prevent="submitForm">
                            <div class="p-6">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <BaseInput
                                        label="Nom complet"
                                        name="name"
                                        required
                                        v-model="form.name"
                                        :error="form.errors.name"
                                    />

                                    <BaseInput
                                        label="Adresse email"
                                        name="email"
                                        type="email"
                                        required
                                        v-model="form.email"
                                        :error="form.errors.email"
                                    />

                                    <BaseInput
                                        label="Nouveau mot de passe"
                                        name="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Laisser vide pour conserver l'actuel"
                                        v-model="form.password"
                                        :error="form.errors.password"
                                    >
                                        <template #append>
                                            <button
                                                type="button"
                                                @click="showPassword = !showPassword"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-primary-txt"
                                            >
                                                <span v-if="showPassword">👁️</span>
                                                <span v-else>👁️‍🗨️</span>
                                            </button>
                                        </template>
                                    </BaseInput>

                                    <BaseInput
                                        label="Confirmation du mot de passe"
                                        name="password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Confirmer le nouveau mot de passe"
                                        v-model="form.password_confirmation"
                                        :error="form.errors.password_confirmation"
                                    >
                                        <template #append>
                                            <button
                                                type="button"
                                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-primary-txt"
                                            >
                                                <span v-if="showPasswordConfirmation">👁️</span>
                                                <span v-else>👁️‍🗨️</span>
                                            </button>
                                        </template>
                                    </BaseInput>

                                </div>

                                <p class="mt-3 text-xs text-primary-txt/70">
                                    Le mot de passe n'est modifié que si un nouveau mot de passe est saisi.
                                </p>

                                <!-- Bouton -->
                                <div class="flex justify-center mt-6">
                                    <MazBtn
                                        no-shadow no-hover-effect
                                        type="submit"
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                            hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                            dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                            dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
                                            size="medium"
                                    >
                                        Modifier
                                    </MazBtn>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

