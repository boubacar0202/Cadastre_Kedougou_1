<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'

const showPassword = ref(false)
const showPasswordConfirmer = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value 
}

const togglePasswordConfirmer = () => {
    showPasswordConfirmer.value = !showPasswordConfirmer.value
}
 
const roleOptions = [
    { label: 'Administrateur', value: 'admin' },
    { label: 'Utilisateur', value: 'user' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: ''
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <form @submit.prevent="submit">
            <BaseInput
                id="name"
                name="name"
                label="Pénom et Nom"
                type="text"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
                wrapper-class="w-full"
                :error="form.errors.name"
            />

            <BaseInput
                id="email"
                name="email"
                label="Adresse Email"
                type="email"
                v-model="form.email"
                required
                autocomplete="username"
                wrapper-class="mt-4 w-full"
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
                :type="showPasswordConfirmer ? 'text' : 'password'"
                v-model="form.password_confirmation"
                required
                autocomplete="new-password"
                wrapper-class="mt-4 w-full"
                :error="form.errors.password_confirmation"
            >
                <template #append>
                    <button
                        type="button"
                        @click="togglePasswordConfirmer"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt"
                    >
                        <span v-if="showPasswordConfirmer">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </template>
            </BaseInput>

            <BaseSelect
                id="role"
                name="role"
                label="Rôle"
                required
                placeholder="Choisir un rôle"
                :options="roleOptions"
                v-model="form.role"
                wrapper-class="mt-4 w-full"
                :custom-error="form.errors.role"
            />

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-primary underline hover:text-primary-dark focus:outline-none focus:ring-primary focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Déjà inscrit ?
                </Link>

                <PrimaryButton
                    class="ms-4 bg-primary-dark text-white hover:bg-primary-dark focus:ring-primary-dark disabled:opacity-25"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Inscrire
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
