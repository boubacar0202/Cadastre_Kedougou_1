<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const services = [
    {
        title: 'Dossiers',
        text: 'Enregistrement et suivi des dossiers cadastraux du centre.',
        icon: 'M7 3.5h7l4 4V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1zm7 0v4h4',
    },
    {
        title: 'Courriers',
        text: 'Arrivées, départs et courriers encore en instance.',
        icon: 'M4 6.5h16v11H4v-11zm0 0 8 6 8-6',
    },
    {
        title: 'Matrice',
        text: 'Parcelles, NICAD, lotissements et mutations.',
        icon: 'M4 4.5h7v7H4v-7zm9 0h7v7h-7v-7zM4 13.5h7v7H4v-7zm9 0h7v7h-7v-7z',
    },
    {
        title: 'Plans',
        text: 'Consultation cartographique et points densifiés.',
        icon: 'M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10zm0-7.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    },
];
</script>

<template>
    <Head title="Cadastre de Kédougou">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <div class="min-h-screen bg-primary-layout text-primary-txt">
        <div class="h-1.5 bg-gradient-to-r from-primary via-primary-menu to-primary-only"></div>

        <header class="sticky top-0 z-20 border-b border-primary-only/80 bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <img
                    src="/logo.jpg"
                    alt="Direction Générale des Impôts et des Domaines"
                    class="h-12 w-auto sm:h-16"
                >

                <nav v-if="canLogin" class="flex shrink-0 items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-dark"
                    >
                        Tableau de bord
                    </Link>
                    <template v-else>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="hidden rounded-xl px-4 py-2.5 text-sm font-semibold text-primary-txt transition hover:bg-primary-form sm:inline-flex"
                        >
                            S'inscrire
                        </Link>
                        <Link
                            :href="route('login')"
                            class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-dark"
                        >
                            Se connecter
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:py-16">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-menu">
                        Centre des services fiscaux
                    </p>
                    <h1 class="mt-3 text-4xl font-bold leading-tight text-primary sm:text-5xl">
                        Cadastre de Kédougou
                    </h1>
                    <p class="mt-4 max-w-xl text-base leading-relaxed">
                        Programme de gestion de la base de données MB-1. Enregistrez les dossiers,
                        suivez les courriers et consultez la matrice cadastrale ainsi que les plans.
                    </p>

                    <div v-if="canLogin" class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="$page.props.auth.user ? route('dashboard') : route('login')"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary to-primary-dark px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl"
                        >
                            {{ $page.props.auth.user ? 'Ouvrir le tableau de bord' : "Accéder à l'application" }}
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-6-6m6 6-6 6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <div class="relative h-44 overflow-hidden rounded-3xl border border-primary-only bg-white shadow-[0_18px_40px_rgba(97,49,4,0.12)] sm:h-56">
                    <img
                        src="/pageAcceuil.jpg"
                        alt="Carte du Sénégal, région de Kédougou mise en évidence"
                        class="absolute inset-x-0 top-0 w-full"
                    >
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-primary">Services disponibles</h2>
                    <p class="mt-1 text-sm">Les principaux modules de l'application.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="service in services"
                        :key="service.title"
                        class="rounded-2xl border border-primary-only bg-white p-5 shadow-[0_12px_30px_rgba(97,49,4,0.06)]"
                    >
                        <span class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-form text-primary">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="service.icon" />
                            </svg>
                        </span>
                        <h3 class="text-lg font-semibold text-primary">{{ service.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed">{{ service.text }}</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="border-t border-primary/20 bg-primary px-4 py-8 text-center text-sm text-primary-form">
            <p class="font-semibold tracking-wide text-white">Centre des services fiscaux</p>
            <p class="mt-1">Cadastre de Kédougou — Programme de gestion de base de données MB-1</p>
        </footer>
    </div>
</template>
