<template>
    <div class="image-container relative">
        <img
            v-if="currentSrc"
            :src="currentSrc"
            :alt="alt"
            @error="handleImageError"
            :class="[
                'transition-opacity duration-300',
                imageClasses,
                imageLoaded ? 'opacity-100' : 'opacity-0'
            ]"
            @load="imageLoaded = true"
        />
        <div
            v-else
            :class="[
                'flex items-center justify-center bg-gray-200 text-gray-500',
                'transition-opacity duration-300 opacity-100',
                placeholderClasses
            ]"
        >
            <div class="text-center p-4">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-medium">{{ fallbackText || 'Imagen no disponible' }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
    src: {
        type: String,
        default: null
    },
    alt: {
        type: String,
        default: ''
    },
    fallbackText: {
        type: String,
        default: 'Imagen no disponible'
    },
    imageClasses: {
        type: String,
        default: 'w-full h-full object-cover'
    },
    placeholderClasses: {
        type: String,
        default: 'w-full h-full min-h-[200px]'
    }
})

// Estados reactivos
const currentSrc = ref(null)
const imageLoaded = ref(false)
const errorCount = ref(0)

// Inicializar imagen
const initializeImage = () => {
    imageLoaded.value = false
    errorCount.value = 0

    if (props.src) {
        // Si la imagen ya tiene el prefijo correcto, usarla directamente
        if (props.src.startsWith('/storage/') || props.src.startsWith('http')) {
            currentSrc.value = props.src
        } else {
            // Si no, agregar el prefijo /storage/
            currentSrc.value = `/storage/${props.src}`
        }
    } else {
        currentSrc.value = null
    }
}

// Manejar errores de carga de imagen
const handleImageError = () => {
    errorCount.value++

    if (errorCount.value === 1 && props.src && !props.src.startsWith('/storage/')) {
        // Primera vez que falla: intentar con la ruta antigua por compatibilidad
        currentSrc.value = `/images/${props.src}`
    } else {
        // Segunda vez que falla o ya es /storage/: mostrar placeholder
        currentSrc.value = null
        imageLoaded.value = false
    }
}

// Watchers
watch(() => props.src, () => {
    initializeImage()
}, { immediate: true })

// Lifecycle
onMounted(() => {
    initializeImage()
})
</script>

<style scoped>
.image-container {
    position: relative;
    overflow: hidden;
}

.image-container img {
    display: block;
    width: 100%;
    height: 100%;
}

/* Animación suave para el cambio de opacidad */
.transition-opacity {
    transition: opacity 300ms ease-in-out;
}
</style>
