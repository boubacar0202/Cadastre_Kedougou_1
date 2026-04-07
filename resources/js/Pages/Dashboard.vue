<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import "flowbite";
import { vLazyImg } from 'maz-ui'
import { usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue' 

const page = usePage()
const totalTerrains = computed(() => page.props.totalTerrains ?? 0) 
const totalDelimitation = computed(() => page.props.totalDelimitation ?? 0)
const totalNicad = computed(() => page.props.totalNicad ?? 0)
const totalSansNicad = computed(() => page.props.totalSansNicad ?? 0)
const displayCount = ref(0)
const displayCountDelim = ref(0)
const displayCountNicad = ref(0)
const displayCountSansNicad = ref(0)

onMounted(() => {
    const target = totalTerrains.value
    const targetDelim = totalDelimitation.value
    const targetNicad = totalNicad.value
    const targetSansNicad = totalSansNicad.value
    let current = 0
    const step = Math.max(1, Math.floor(target / 70))

    const interval = setInterval(() => {
        current += step
        if (current >= target) {
        current = target
        clearInterval(interval)
        }
        displayCount.value = current
    }, 30)

    const intervalDelim = setInterval(() => {
        current += step
        if (current >= targetDelim) {
        current = targetDelim
        clearInterval(intervalDelim)
        }
        displayCountDelim.value = current
    }, 30) 

    const intervalNicad = setInterval(() => {
        current += step
        if (current >= targetNicad) {
        current = targetNicad
        clearInterval(intervalNicad)
        }
        displayCountNicad.value = current
    }, 30)

    const intervalSansNicad = setInterval(() => {
        current += step
        if (current >= targetSansNicad) {
        current = targetSansNicad
        clearInterval(intervalSansNicad)
        }
        displayCountSansNicad.value = current
    }, 30)

})

</script> 

<template>
    <Head title="Accueil">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-primary-txt"
            >
                Accueil
            </h2>
            
        </template>

            <div
                class="min-h-screen bg-cover bg-center bg-no-repeat"
                style="background-image: url('https://upload.wikimedia.org/wikipedia/commons/9/93/Map_of_the_departments_of_the_K%C3%A9dougou_region_of_Senegal.png');"
            >
                <!-- contenu du Dashboard -->
                <div class="flex items-center italic justify-center min-h-screen gap-12 mt-8">
                    <div class="flex flex-col items-center justify-center h-56 w-56 rounded-full bg-white/80 border-8 border-white shadow-lg">
                        <span class="text-7xl font-bold text-primary-txt">
                            {{ displayCount }}
                        </span>
                        <h1 class="text-xl font-bold italic text-primary-txt">
                            DOSSIERS
                        </h1>
                    </div>
                    <div class="flex flex-col items-center justify-center h-56 w-56 rounded-full bg-white/80 border-8 border-white shadow-lg">
                        <span class="text-7xl font-bold text-primary-txt">
                            {{ displayCountDelim }}
                        </span>
                        <h1 class="text-xl font-bold italic text-primary-txt">
                            DELIMITATIONS
                        </h1>
                    </div>
                    <div class="flex flex-col items-center justify-center h-56 w-56 rounded-full bg-white/80 border-8 border-white shadow-lg">
                        <span class="text-7xl font-bold text-primary-txt">
                            {{ displayCountNicad }}
                        </span>
                        <h1 class="text-xl font-bold italic text-primary-txt">
                            ONT DES NICAD
                        </h1>
                    </div>
                    <div class="flex flex-col items-center justify-center h-56 w-56 rounded-full bg-white/80 border-8 border-white shadow-lg">
                        <span class="text-7xl font-bold text-primary-txt">
                            {{ displayCountSansNicad }}
                        </span>
                        <h1 class="text-xl font-bold italic text-primary-txt">
                            SANS NICAD
                        </h1>
                    </div>
                </div>
            </div> 
    </AuthenticatedLayout>
</template>
