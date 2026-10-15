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

const totalCourriersArrivees = computed(() => page.props.totalCourriersArrivees ?? 0) 
const totalCourriersDeparts = computed(() => page.props.totalCourriersDeparts ?? 0) 
const totalInstances = computed(() => page.props.totalInstances ?? 0)
const displayCountArrivee = ref(0) 
const displayCountDepart = ref(0)
const displayCountInstance = ref(0)
// const displayCountSansNicad = ref(0)


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


onMounted(() => {
    const target = totalCourriersArrivees.value
    const targetDepart = totalCourriersDeparts.value
    const targetInstance = totalInstances.value
 
    let current = 0
    const step = Math.max(1, Math.floor(target / 70))

    const interval = setInterval(() => {
        current += step
        if (current >= target) {
        current = target
        clearInterval(interval)
        }
        displayCountArrivee.value = current
    }, 30)

    const intervalDepart = setInterval(() => {
        current += step
        if (current >= targetDepart) {
        current = targetDepart
        clearInterval(intervalDepart)
        }
        displayCountDepart.value = current
    }, 30) 

    const intervalInstance = setInterval(() => {
        current += step
        if (current >= targetInstance) {
        current = targetInstance
        clearInterval(intervalInstance)
        }
        displayCountInstance.value = current
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
                <div class="flex min-h-[calc(100vh-8rem)] flex-col items-center justify-center gap-8 px-4 py-8">
                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCount }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">DOSSIERS</h1>
                        </div>
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountDelim }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">DELIMITATIONS</h1>
                        </div>
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountNicad }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">ONT DES NICAD</h1>
                        </div>
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountSansNicad }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">SANS NICAD</h1>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountArrivee }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">COURRIERS ARRIVÉES</h1>
                        </div>
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountDepart }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">COURRIERS DÉPARTS</h1>
                        </div>
                        <div class="flex h-52 w-52 flex-col items-center justify-center rounded-full border-8 border-primary-only bg-white/80 text-center shadow-lg">
                            <span class="text-7xl font-bold text-primary-txt">{{ displayCountInstance }}</span>
                            <h1 class="px-6 text-sm font-semibold text-primary-txt">COURRIERS INSTANCES</h1>
                        </div>
                    </div>
                </div> 
            </div> 
    </AuthenticatedLayout>
</template>
