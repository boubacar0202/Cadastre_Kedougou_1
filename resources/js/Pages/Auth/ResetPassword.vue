<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
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
            <BaseInput
                id="email"
                name="email"
                label="Email"
                type="email"
                v-model="form.email"
                required
                autofocus
                autocomplete="username"
                wrapper-class="w-full"
                :error="form.errors.email"
            />

            <BaseInput
                id="password"
                name="password"
                label="Mot de passe"
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                required
                autocomplete="new-password"
                wrapper-class="mt-4 w-full"
                :error="form.errors.password"
            >
                <template #append>
                    <button
                        type="button"
                        @click="togglePassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt"
                        tabindex="-1"
                    >
                        <span v-if="showPassword">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </template>
            </BaseInput>

            <BaseInput
                id="password_confirmation"
                name="password_confirmation"
                label="Confirmation Mot de passe"
                :type="showPasswordNew ? 'text' : 'password'"
                v-model="form.password_confirmation"
                required
                autocomplete="new-password"
                wrapper-class="mt-4 w-full"
                :error="form.errors.password_confirmation"
            >
                <template #append>
                    <button
                        type="button"
                        @click="togglePasswordNew"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt"
                        tabindex="-1"
                    >
                        <span v-if="showPasswordNew">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </template>
            </BaseInput>

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
