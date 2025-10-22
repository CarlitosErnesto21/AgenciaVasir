<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// Inicializar toast
const toast = useToast()

// Estado
const paquetes = ref([])
const loading = ref(true)
const error = ref(null)
const selectedPrecio = ref('Todos')

// Map backend paquete -> cliente view shape
const mapPaquete = (p) => {
  return {
    id: p.id,
    titulo: p.nombre || p.titulo || `Paquete ${p.id}`,
    descripcion: p.descripcion || p.descripcion_corta || p.nombre || '',
    precio: Number(p.precio) || 0,
    duracion: p.duracion || p.dias ? `${p.dias} días` : '',
    imagen: p.imagenes && p.imagenes.length ? `/storage/paquetes/${(typeof p.imagenes[0] === 'string' ? p.imagenes[0] : p.imagenes[0].nombre)}` : '',
    incluye: p.incluye ? (typeof p.incluye === 'string' ? p.incluye.split('|').filter(Boolean) : p.incluye) : [],
    destinos: p.destinos || [],
    personas: p.personas || 'N/A',
    destacado: p.destacado || false,
  }
}

const fetchPaquetes = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/api/paquetes')
    const data = Array.isArray(res.data) ? res.data : (res.data.data || res.data.paquetes || [])
    paquetes.value = data.map(mapPaquete)
  } catch (err) {
    console.error('Error cargando paquetes:', err)
    error.value = err?.response?.data?.message || err.message || 'Error desconocido'
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los paquetes.', life: 5000 })
  } finally {
    loading.value = false
  }
}

onMounted(fetchPaquetes)


// Paquetes filtrados
const paquetesFiltrados = computed(() => {
  if (loading.value) return []
  let filtrados = paquetes.value
  
  if (selectedPrecio.value !== 'Todos') {
    if (selectedPrecio.value === 'bajo') {
      filtrados = filtrados.filter(p => p.precio < 100)
    } else if (selectedPrecio.value === 'medio') {
      filtrados = filtrados.filter(p => p.precio >= 100 && p.precio < 150)
    } else if (selectedPrecio.value === 'alto') {
      filtrados = filtrados.filter(p => p.precio >= 150)
    }
  }
  
  return filtrados
})

// Funciones para botones
const verDetalles = (paquete) => {
  toast.add({
    severity: 'info',
    summary: `Detalles de ${paquete.titulo}`,
    detail: `Duración: ${paquete.duracion} | Destinos: ${paquete.destinos.join(', ')} | Incluye: ${paquete.incluye.join(', ')} | Precio: $${paquete.precio} por persona`,
    life: 8000
  })
}

const reservarPaquete = (paquete) => {
  toast.add({
    severity: 'success',
    summary: `Paquete "${paquete.titulo}" seleccionado`,
    detail: `Precio: $${paquete.precio} | Duración: ${paquete.duracion} | Personas: ${paquete.personas}`,
    life: 5000
  })
}
</script>
<template>
  <Catalogo>
    <Toast />
    <div class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen pt-20 sm:pt-20 md:pt-28 lg:pt-32 xl:pt-28">
      <div class="w-full px-1 sm:px-1 lg:px-2 max-w-7xl mx-auto">
        <!-- Header Profesional con Stats Integradas -->
        <div class="mb-3 sm:mb-4">
          <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-red-600 via-purple-600 to-blue-600 text-white text-center py-4 sm:py-6">
              <div class="flex items-center justify-center gap-3 mb-1">
                <img src="/images/sv.png" alt="Bandera El Salvador" class="w-8 h-8 sm:w-12 sm:h-12 shadow-lg rounded-full border-2 border-white/30" />
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">📦 Paquetes Turísticos</h1>
              </div>
              <p class="text-base sm:text-lg text-red-100 px-4">Experiencias completas diseñadas para crear recuerdos inolvidables</p>
            </div>

            <!-- Stats integradas en el header -->
            <div v-if="paquetes.length > 0" class="bg-white py-3 px-3">
              <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-3 md:grid-cols-3 gap-2 md:gap-6">
                  <div class="text-center bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <h3 class="text-2xl font-bold text-red-600">{{ paquetes.length }}</h3>
                    <p class="text-sm text-gray-600">Paquetes</p>
                  </div>
                  <div class="text-center bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <h3 class="text-2xl font-bold text-blue-600">Desde ${{ paquetes[0]?.precio | currency }}</h3>
                    <p class="text-sm text-gray-600">Precios</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estados: carga / error / vacío -->
        <div v-if="loading && paquetes.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-red-200 border-t-red-600 mb-4"></div>
            <p class="text-lg font-semibold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent">Cargando paquetes...</p>
            <p class="text-sm text-gray-500 mt-2">Preparando las mejores experiencias para ti</p>
          </div>
        </div>

        <div v-else-if="error && paquetes.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 text-red-700 px-8 py-6 rounded-xl shadow-lg max-w-md mx-auto">
            <div class="text-4xl mb-4">⚠️</div>
            <h3 class="text-xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent mb-3">No se pudieron cargar los paquetes</h3>
            <p class="text-sm text-red-600 leading-relaxed">Por favor, intenta recargar la página o contacta con nosotros.</p>
          </div>
        </div>

        <div v-else-if="!loading && paquetes.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-indigo-50 border-2 border-red-200 rounded-xl shadow-lg p-8 max-w-lg mx-auto">
            <div class="text-6xl mb-4">📦</div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-indigo-600 bg-clip-text text-transparent mb-3">No hay paquetes disponibles</h3>
            <p class="text-gray-600 mb-4 leading-relaxed">Próximamente tendremos nuevos paquetes turísticos.</p>
            <p class="text-sm text-gray-500">Mientras tanto, puedes explorar nuestros tours o contactarnos para solicitudes personalizadas.</p>
          </div>
        </div>

        <!-- Paquetes Grid -->
        <div v-if="paquetesFiltrados.length > 0" class="mb-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="paquete in paquetesFiltrados"
              :key="paquete.id"
              class="bg-gradient-to-br from-white to-gray-50 hover:from-gray-50 hover:to-white border-2 border-gray-200 hover:border-red-300 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col min-h-[360px] sm:min-h-[380px] transform hover:-translate-y-2 hover:scale-[1.02] overflow-hidden rounded-xl"
            >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-200">
                  <div v-if="paquete.imagen" class="object-cover h-full w-full bg-center bg-cover" :style="{ backgroundImage: `url(${paquete.imagen})` }"></div>
                  <div v-else class="h-full w-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                    <div class="text-center text-gray-500">
                      <i class="fas fa-image text-4xl mb-2"></i>
                    </div>
                  </div>
                  <div class="absolute top-2 right-2 bg-green-600 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                    ${{ paquete.precio }}
                  </div>
                  <div class="absolute bottom-2 left-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs">
                    {{ paquete.duracion }}
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-12 flex items-start px-4 pt-3 cursor-pointer hover:bg-gray-50 transition-all duration-300 rounded-lg mx-2">
                  <span class="text-lg font-bold text-gray-800 leading-tight line-clamp-2">{{ paquete.titulo }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <p class="text-gray-600 text-sm line-clamp-2 mb-3">{{ paquete.descripcion }}</p>
                  <div class="flex items-center text-xs text-gray-600 mb-2">
                    <span class="w-1 h-1 bg-blue-500 rounded-full mr-2"></span>
                    <strong>Personas:</strong> {{ paquete.personas }}
                  </div>
                  <div class="mb-2">
                    <p class="text-xs font-semibold text-gray-700 mb-1">Incluye:</p>
                    <ul class="text-xs text-gray-600">
                      <li v-for="item in paquete.incluye.slice(0, 2)" :key="item" class="flex items-center">
                        <span class="w-1 h-1 bg-green-500 rounded-full mr-2"></span>
                        {{ item }}
                      </li>
                      <li v-if="paquete.incluye.length > 2" class="text-gray-400">+ {{ paquete.incluye.length - 2 }} servicios más...</li>
                    </ul>
                  </div>
                </div>
              </template>

              <template #footer>
                <div class="flex gap-2 mt-4 px-4 pb-4">
                  <Button
                    label="Reservar"
                    @click="reservarPaquete(paquete)"
                    class="!bg-red-600 !border-none !px-3 !py-2 !text-white !text-sm font-semibold rounded hover:!bg-red-700 transition-all flex-1 shadow-sm"
                    size="small"
                  />
                  <Button
                    label="Detalles"
                    @click="verDetalles(paquete)"
                    outlined
                    class="!border-red-600 !text-red-600 !px-3 !py-2 !text-sm font-semibold rounded hover:!bg-red-50 transition-all"
                    size="small"
                  />
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- CTA final -->
        <div class="mt-8 text-center">
          <span class="text-gray-600">¿Buscas algo personalizado? <a href="/contactos" class="text-red-600 hover:underline font-semibold">Contáctanos para paquetes a medida</a></span>
        </div>
      </div>
    </div>
  </Catalogo>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
