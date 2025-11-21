<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        // Usar setTimeout para evitar conflictos con otros elementos que tienen autofocus
        setTimeout(() => {
            // Verificar que no haya otro elemento ya enfocado
            if (!document.activeElement || document.activeElement === document.body) {
                input.value.focus();
            }
        }, 100);
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        :class="[$attrs.class]"
        v-bind="$attrs"
        v-model="model"
        ref="input"
    />
</template>
