<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Donnee" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Donnees
            </h2>
           
        </template>
        <button type="button" class="btn btn-primary">Ajouter</button>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        Vous etes sur la pages reserver à la Donnee
                        <p>jhzfhui</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
