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
 
 

</script>

<template>
    <Head title="Login - Cadastre Kedougou">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>


    <div class="bg-white text-primary-txt dark:bg-black dark:text-white/50">

       
        <div
            class="relative flex min-h-screen flex-col items-center justify-center selection:bg-[#FF2D20] 
            selection:text-white"
        >


            <div class="relative w-full max-w-3xl px-6 lg:max-w-7xl">
                <header
                    class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3"
                >
                    <div class="flex lg:col-start-2 lg:justify-center">

                        <img src="/logo.jpg" alt="Logo" class="h-auto w-auto">
                        
                    </div>
                    <nav v-if="canLogin" class="-mx-3 flex flex-1 justify-end">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-3 py-2 text-primary-txt ring-1 ring-transparent
                                transition hover:text-primary-dark focus:outline-none focus-visible:ring-[#FF2D20] 
                                dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-primary-txt text-xl ring-1 ring-transparent
                                    transition hover:text-primary-dark hover:font-bold focus:outline-none focus-visible:ring-[#FF2D20] 
                                    dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                Connecter 
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-primary-txt text-xl ring-1 ring-transparent
                                    transition hover:text-primary-dark hover:font-bold focus:outline-none focus-visible:ring-[#FF2D20] 
                                    dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                            >
                                S'inscrire
                            </Link>
                        </template>
                    </nav>
                </header>
             
                <main class="mt-10">
                    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                        
                        <a
                            :href="route('login')"
                            id="docs-card"
                            class="flex flex-col items-start border border-primary-txt-txt gap-6 overflow-hidden 
                            rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] 
                            transition duration-300 hover:text-primary-dark hover:ring-black/20 focus:outline-none 
                            focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 
                            dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                        >
                            <div
                                id="screenshot-container"
                                class="relative flex w-full flex-1 items-stretch"
                            >
                               
                                <div
                                    class="absolute -bottom-16 -left-16 h-40 w-[calc(100%+8rem)] bg-gradient-to-b from-transparent via-white to-white dark:via-zinc-900 dark:to-zinc-900"
                                ></div>
                            </div>

                            <div
                                class="relative flex items-center gap-6 lg:items-end "
                            >
                            
                                <h1 class="text-primarty text-3xl font-bold">CADASTRE KEDOUGOU</h1>
                                <svg
                                    class="size-6 shrink-0 stroke-primary"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"
                                    />
                                </svg>
                            </div>
                        </a>
                    </div>
                </main> 
            </div> 
            <footer class="py-16 text-center text-sm text-primary-txt font-bold mt-8">
                Programme de Gestion de Base de données v-1   
            </footer>
        </div>

    </div>

</template>
