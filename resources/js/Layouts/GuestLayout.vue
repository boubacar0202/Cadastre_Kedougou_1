<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useToast } from 'maz-ui';
import { onMounted } from 'vue';

const page = usePage();
const toast = useToast();

onMounted(() => {
  if (page.props.flash.success) {
    toast.success(page.props.flash.success);
  }
  if (page.props.flash.error) {
    toast.error(page.props.flash.error);
  }
});
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-white pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <!-- <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />   gray-100    -->
                <img src="/logo_logo.jpg" alt="Logo" class="h-20 w-auto">
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-gray-200 border border-gray-400 px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
