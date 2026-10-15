<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-primary-txt">
                Mettre à jour le mot de passe
            </h2>

            <p class="mt-1 text-sm text-primary-txt">
                Assurez-vous d’utiliser un mot de passe long et aléatoire pour sécuriser votre compte.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <BaseInput
                id="current_password"
                name="current_password"
                ref="currentPasswordInput"
                label="Mot de passe Actuel"
                v-model="form.current_password"
                type="password"
                autocomplete="current-password"
                wrapper-class="w-full"
                :error="form.errors.current_password"
            />

            <BaseInput
                id="password"
                name="password"
                ref="passwordInput"
                label="Nouveau Mot de passe"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                wrapper-class="w-full"
                :error="form.errors.password"
            />

            <BaseInput
                id="password_confirmation"
                name="password_confirmation"
                label="Confirmation Mot de passe"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                wrapper-class="w-full"
                :error="form.errors.password_confirmation"
            />

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-primary-txt"
                    >
                        Enregistrer
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
