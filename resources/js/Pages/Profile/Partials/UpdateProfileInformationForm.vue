<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-primary-txt">
                Informations Personnelles
            </h2>

            <p class="mt-1 text-sm text-primary-txt">
                Mettez à jour les informations de profil et l’adresse e-mail de votre compte
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <BaseInput
                id="name"
                name="name"
                label="Prénom et Nom"
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
                label="Email"
                type="email"
                v-model="form.email"
                required
                autocomplete="username"
                wrapper-class="w-full"
                :error="form.errors.email"
            />

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-primary-txt">
                    Votre adresse e-mail n’est pas vérifiée.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-primary-txt underline hover:text-primary-txt focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Cliquez ici pour renvoyer l’e-mail de vérification.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    AUn nouveau lien de vérification a été envoyé à votre adresse e-mail.
                </div>
            </div>

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
