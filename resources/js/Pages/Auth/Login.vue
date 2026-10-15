<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { toast } from '@/Components/AppToast';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const showPassword = ref(false);
const page = usePage();

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success);
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error);
    }
});
</script>

<template>
    <Head title="Connexion">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <div class="min-h-screen bg-primary-layout text-primary-txt">
        <div class="h-1.5 bg-gradient-to-r from-primary via-primary-menu to-primary-only"></div>

        <header class="border-b border-primary-only/80 bg-white/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <Link href="/" class="shrink-0">
                    <img
                        src="/logo.jpg"
                        alt="Direction Générale des Impôts et des Domaines"
                        class="h-12 w-auto sm:h-14"
                    >
                </Link>
                <Link
                    href="/"
                    class="text-sm font-semibold text-primary transition hover:text-primary-dark"
                >
                    Retour à l'accueil
                </Link>
            </div>
        </header>

        <main class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
            <section class="hidden lg:block">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-menu">
                    Centre des services fiscaux
                </p>
                <h1 class="mt-3 text-4xl font-bold leading-tight text-primary">
                    Cadastre de Kédougou
                </h1>
                <p class="mt-4 max-w-md text-base leading-relaxed">
                    Connectez-vous pour enregistrer les dossiers, suivre les courriers
                    et consulter la matrice cadastrale.
                </p>
                <div class="relative mt-8 h-44 overflow-hidden rounded-3xl border border-primary-only bg-white shadow-[0_18px_40px_rgba(97,49,4,0.12)]">
                    <img
                        src="/pageAcceuil.jpg"
                        alt="Carte du Sénégal, région de Kédougou mise en évidence"
                        class="absolute inset-x-0 top-0 w-full"
                    >
                </div>
            </section>

            <section class="mx-auto w-full max-w-md rounded-3xl border border-primary-only bg-white p-6 shadow-[0_18px_40px_rgba(97,49,4,0.1)] sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-menu lg:hidden">
                    Cadastre de Kédougou
                </p>
                <h2 class="mt-2 text-2xl font-bold text-primary lg:mt-0">Connexion</h2>
                <p class="mt-1 text-sm">Accès réservé au personnel du centre.</p>

                <div
                    v-if="status"
                    class="mt-5 rounded-xl border border-primary-only bg-primary-form px-4 py-3 text-sm font-medium text-primary"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="mt-6">
                    <BaseInput
                        id="email"
                        name="email"
                        label="Adresse e-mail"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nom@impots.gouv.sn"
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
                        autocomplete="current-password"
                        placeholder="Votre mot de passe"
                        wrapper-class="mt-4 w-full"
                        :error="form.errors.password"
                    >
                        <template #append>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-primary-txt/70 hover:text-primary"
                                :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                            >
                                <svg v-if="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.5 10.7A2 2 0 0 0 12 14a2 2 0 0 0 1.3-.5M9.9 5.2A9.8 9.8 0 0 1 12 5c5 0 8.5 3.5 10 7-1.2 2.7-3.4 4.9-6.1 6.1M6.1 6.1C4.2 7.4 2.7 9.2 2 12c.6 1.2 1.4 2.3 2.4 3.2" />
                                </svg>
                                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </template>
                    </BaseInput>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-primary">Se souvenir de moi</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-primary underline-offset-2 hover:text-primary-dark hover:underline"
                        >
                            Mot de passe oublié ?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="mt-6 inline-flex h-11 w-full items-center justify-center rounded-xl bg-gradient-to-r from-primary to-primary-dark text-sm font-semibold text-white shadow-md transition hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Connexion…' : 'Se connecter' }}
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>
