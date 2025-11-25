<template>
    <Dialog
        v-model:visible="isVisible"
        :header="title"
        :modal="true"
        :style="dialogStyle"
        :closable="false"
        :draggable="false"
    >
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-1">
            <FontAwesomeIcon :icon="faExclamationTriangle" class="h-6 w-6 sm:h-8 sm:w-8 text-red-500 flex-shrink-0" />
            <div class="flex flex-col">
                <span class="text-gray-800 font-medium text-sm sm:text-base">{{ message }}</span>
                <span v-if="subtitle" class="text-red-600 text-xs sm:text-sm font-medium mt-1">{{ subtitle }}</span>
            </div>
        </div>
        <template #footer>
            <div class="flex flex-col sm:flex-row justify-center gap-3 w-full">
                <button
                    type="button"
                    class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white border-none px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base rounded-md transition-all duration-200 ease-in-out flex items-center justify-center gap-2"
                    @click="exitWithoutSaving"
                >
                    <FontAwesomeIcon :icon="faSignOut" class="h-4" />
                    <span>{{ exitText }}</span>
                </button>
                <button
                    type="button"
                    class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white border border-blue-500 px-4 sm:px-6 py-2 sm:py-3 text-sm sm:text-base rounded-md transition-all duration-200 ease-in-out flex items-center justify-center gap-2"
                    @click="continueEditing"
                >
                    <FontAwesomeIcon :icon="faPencil" class="h-4" />
                    <span>{{ continueText }}</span>
                </button>

            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import Dialog from 'primevue/dialog'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faExclamationTriangle, faPencil, faSignOut } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    visible: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Cambios sin guardar'
    },
    message: {
        type: String,
        default: '¡Tienes información sin guardar!'
    },
    subtitle: {
        type: String,
        default: '¿Deseas salir sin guardar?'
    },
    continueText: {
        type: String,
        default: 'Continuar'
    },
    exitText: {
        type: String,
        default: 'Salir sin guardar'
    }
})

const emit = defineEmits(['update:visible', 'continue-editing', 'exit-without-saving'])

// Variable reactiva para el ancho de ventana
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);

// Estilo responsive para el diálogo
const dialogStyle = computed(() => {
    if (windowWidth.value < 640) {
        return { width: '95vw', maxWidth: '350px' };
    } else if (windowWidth.value < 768) {
        return { width: '380px' };
    } else {
        return { width: '400px' };
    }
});

const isVisible = computed({
    get() {
        return props.visible
    },
    set(value) {
        emit('update:visible', value)
    }
})

const continueEditing = () => {
    emit('continue-editing')
    isVisible.value = false
}

const exitWithoutSaving = () => {
    emit('exit-without-saving')
    isVisible.value = false
}

// Listener para actualizar el tamaño de ventana
const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('resize', updateWindowWidth)
    }
})

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', updateWindowWidth)
    }
})
</script>
