<template>
  <div class="image-container relative overflow-hidden" :class="containerClass">
    <img
      :src="currentSrc"
      :alt="alt"
      :class="imageClass"
      @load="onLoad"
      @error="onError"
      loading="lazy"
    />

    <!-- Loading state -->
    <div
      v-if="isLoading"
      class="absolute inset-0 bg-gray-100 animate-pulse flex items-center justify-center"
    >
      <div class="text-gray-400 text-sm">Cargando...</div>
    </div>

    <!-- Error state -->
    <div
      v-if="hasError && !isLoading"
      class="absolute inset-0 bg-gradient-to-br from-red-100 to-red-200 flex flex-col items-center justify-center text-red-600 text-xs p-2 text-center"
    >
      <i class="pi pi-image text-2xl mb-2 opacity-50"></i>
      <span class="font-medium">{{ errorText }}</span>
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
    default: 'Imagen'
  },
  fallbackText: {
    type: String,
    default: ''
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: 'w-full h-full object-cover'
  }
})

const isLoading = ref(true)
const hasError = ref(false)
const currentSrc = ref('')
const errorAttempts = ref(0)
const maxRetries = 3

const errorText = computed(() => {
  return props.fallbackText || props.alt || 'Sin imagen'
})

const createFallbackImage = (text) => {
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')

  canvas.width = 300
  canvas.height = 200

  // Gradient background
  const gradient = ctx.createLinearGradient(0, 0, 300, 200)
  gradient.addColorStop(0, '#ef4444')
  gradient.addColorStop(1, '#dc2626')

  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, 300, 200)

  // Text
  ctx.fillStyle = 'white'
  ctx.font = 'bold 14px Arial'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'

  // Wrap text
  const maxWidth = 280
  const words = text.split(' ')
  let line = ''
  const lines = []

  for (let n = 0; n < words.length; n++) {
    const testLine = line + words[n] + ' '
    const metrics = ctx.measureText(testLine)
    const testWidth = metrics.width
    if (testWidth > maxWidth && n > 0) {
      lines.push(line)
      line = words[n] + ' '
    } else {
      line = testLine
    }
  }
  lines.push(line)

  // Draw lines
  const startY = 100 - (lines.length * 10)
  lines.forEach((line, index) => {
    ctx.fillText(line.trim(), 150, startY + (index * 20))
  })

  return canvas.toDataURL()
}

const onLoad = () => {
  isLoading.value = false
  hasError.value = false
}

const onError = () => {
  isLoading.value = false
  errorAttempts.value++

  // Usar únicamente fallback local (Canvas)
  hasError.value = true
  currentSrc.value = createFallbackImage(errorText.value)
}

const initializeImage = () => {
  // Si no hay src, mostrar error inmediatamente
  if (!props.src) {
    isLoading.value = false
    hasError.value = true
    currentSrc.value = createFallbackImage(errorText.value)
    return
  }

  isLoading.value = true
  hasError.value = false
  errorAttempts.value = 0
  currentSrc.value = props.src
}

// Watch for prop changes
watch(() => props.src, initializeImage)

onMounted(initializeImage)
</script>

<style scoped>
.image-container {
  position: relative;
  background-color: #f3f4f6;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
