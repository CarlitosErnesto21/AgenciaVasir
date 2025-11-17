<template>
    <button
        :disabled="disabled"
        :class="[
            'inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out',
            disabled
                ? 'opacity-50 cursor-not-allowed bg-red-300 hover:bg-red-300 active:bg-red-300'
                : 'hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700'
        ]"
    >
        <slot />
    </button>
</template>

<script setup>
const props = defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>
