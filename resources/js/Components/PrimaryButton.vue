<template>
    <button
        v-bind="$attrs"
        :class="[
            'inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest transition duration-150 ease-in-out',
            $attrs.class
        ]"
    >
        <slot />
    </button>
</template>
