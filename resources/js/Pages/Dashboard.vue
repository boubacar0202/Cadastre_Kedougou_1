<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import "flowbite";
import { vLazyImg } from 'maz-ui'

</script>

<template>
    <Head title="Dashboard">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-primary-txt"
            >
                Dashboard
            </h2>
        </template>
        <div class="text-center">
  
        
            <div class="container">
                <img
                    class="flex flex-center rounded"
                    v-lazy-img="'https://upload.wikimedia.org/wikipedia/commons/9/93/Map_of_the_departments_of_the_K%C3%A9dougou_region_of_Senegal.png'"
                />
            </div>
        </div>
               
       
    </AuthenticatedLayout>
</template>
