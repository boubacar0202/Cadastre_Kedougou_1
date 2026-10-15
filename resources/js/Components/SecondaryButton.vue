<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-xl border border-primary-only bg-white px-4 py-2 text-sm font-semibold normal-case tracking-normal text-primary-txt shadow-sm transition duration-150 ease-in-out hover:bg-primary-form focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
