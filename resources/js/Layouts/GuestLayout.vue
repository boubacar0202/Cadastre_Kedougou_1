<script setup>
import { Link, usePage } from '@inertiajs/vue3'; 
import { toast } from "@/Components/AppToast";  
import { onMounted } from 'vue';

const page = usePage(); 

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
        class="h-screen w-screen flex items-center justify-center bg-center bg-cover overflow-hidden"
        style="background-image: url('/pageAcceuil.jpg')"
    >
        <!-- Contenu centré -->
        <div class="w-full max-w-md bg-white/90 backdrop-blur-sm p-6 rounded-xl shadow-lg m-2 sm:max-w-md sm:rounded-xl">
            <slot />
        </div>
    </div>
</template>


