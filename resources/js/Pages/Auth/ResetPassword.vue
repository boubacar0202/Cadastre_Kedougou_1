<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Affichage / masquage des mots de passe
 */
const showPassword = ref(false);
const showPasswordNew = ref(false);

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordNew = () => {
    showPasswordNew.value = !showPasswordNew.value;
};

/**
 * Props envoyées par le contrôleur Laravel :
 *  - email : l'email de l'utilisateur
 *  - token : le token de réinitialisation présent dans l'URL
 */
const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

/**
 * Formulaire Inertia
 */
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

/**
 * Soumission du formulaire
 */
const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Réinitialiser le mot de passe" />

        <!-- Erreur globale liée au token (token expiré / invalide) -->
        <div
            v-if="form.errors.token"
            class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md p-3"
        >
            {{ form.errors.token }}
        </div>

        <form @submit.prevent="submit">
            <!-- ============================= -->
            <!-- CHAMP EMAIL                   -->
            <!-- ============================= -->
            <div>
                <InputLabel for="email" value="Email" class="text-primary" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900
                        outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400
                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- ============================= -->
            <!-- CHAMP MOT DE PASSE            -->
            <!-- ============================= -->
            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" class="text-primary" />

                <div class="flex items-center mt-1 relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900
                            outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400
                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />

                    <button
                        type="button"
                        @click="togglePassword"
                        class="absolute right-3 text-primary-txt"
                        tabindex="-1"
                    >
                        <span v-if="showPassword">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- ============================= -->
            <!-- CHAMP CONFIRMATION            -->
            <!-- ============================= -->
            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmation Mot de passe"
                    class="text-primary"
                />

                <div class="flex items-center mt-1 relative">
                    <TextInput
                        id="password_confirmation"
                        :type="showPasswordNew ? 'text' : 'password'"
                        class="block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900
                            outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400
                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />

                    <button
                        type="button"
                        @click="togglePasswordNew"
                        class="absolute right-3 text-primary-txt"
                        tabindex="-1"
                    >
                        <span v-if="showPasswordNew">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </div>

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <!-- ============================= -->
            <!-- BOUTON DE SOUMISSION          -->
            <!-- ============================= -->
            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    class="ms-4 bg-primary-dark text-white hover:bg-primary-dark focus:ring-primary-dark disabled:opacity-25"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Réinitialiser le mot de passe
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>