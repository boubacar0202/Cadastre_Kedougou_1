<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { toast } from "@/Components/AppToast";  
import BaseInput from "@/Components/BaseInput.vue";
import { Inertia } from "@inertiajs/inertia";

defineOptions({ layout: DefaultLayout });
  
// Récupération de l'utilisateur envoyé par le controller
const { user } = defineProps({
    user: {
        type: Object,
        required: true,
    }
});

// Formulaire
const form = useForm({
    name: user?.name || "",
    email: user?.email || "",
});

// Soumission
const submitForm = () => {
    form.put(route("parametre.updateUser", user.id), {
        preserveScroll: true,

        onSuccess: () => {
            toast.success(message);
            Inertia.reload({ preserveState: false });
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
                                    />

                                    <BaseInput
                                        label="Adresse email"
                                        name="email"
                                        required
                                        v-model="form.email"
                                    />

                                </div>

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

