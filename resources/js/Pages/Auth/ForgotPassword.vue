<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <div class="mb-4 text-sm text-primary">
            Vous avez oublié votre mot de passe ?
            Indiquez votre adresse e-mail et nous vous enverrons un
            lien de réinitialisation qui vous permettra d'en choisir un nouveau.
        </div>

        <!-- Message de succès renvoyé par Laravel -->
        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-md p-3"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <BaseInput
                id="email"
                name="email"
                label="Email"
                type="email"
                placeholder="votre@email.com"
                v-model="form.email"
                required
                autofocus
                autocomplete="username"
                wrapper-class="w-full"
                :error="form.errors.email"
            />

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    class="ms-4 bg-primary-dark text-white hover:bg-primary-dark focus:ring-primary-dark disabled:opacity-25"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Envoyer le lien
                </PrimaryButton>
            </div>
        </form>

        <!-- Lien retour vers la connexion -->
        <div class="mt-6 text-center text-sm">
            <Link
                :href="route('login')"
                class="text-primary-dark hover:underline"
            >
                ← Retour à la connexion
            </Link>
        </div>
    </GuestLayout>
</template>
