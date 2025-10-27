<script setup>
import Catalogo from '../Catalogo.vue'
import FiltrosPanel from './TiendaViews/FiltrosPanel.vue'
import SearchBar from './TiendaViews/SearchBar.vue'
import ProductGrid from './TiendaViews/ProductGrid.vue'
import PaginationControls from './TiendaViews/PaginationControls.vue'
import EmptyState from './TiendaViews/EmptyState.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import CarritoCompras from './TiendaViews/CarritoCompras.vue'
import CarritoButton from './TiendaViews/CarritoButton.vue'
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { usePage } from '@inertiajs/vue3'
import { useCarritoStore } from '@/stores/carrito'

const toast = useToast()
const page = usePage()
const carrito = useCarritoStore()

// 📊 Estados reactivos
const products = ref([])
const categories = ref([])
const loading = ref(true)
const loadingCategories = ref(true)

// 🔐 Estado del modal de autenticación
const mostrarModalAuth = ref(false)
const productoSeleccionado = ref(null)

// 🔍 Filtros
const selectedCategories = ref([])
const minPrice = ref('')
const maxPrice = ref('')
const searchTerm = ref('')
const showMobileFilters = ref(false)

// 📄 Paginación
const currentPage = ref(1)
const itemsPerPage = ref(8) // Número de productos por página (2 filas x 4 columnas)
const totalPages = ref(0)

// 📊 Cargar datos al montar el componente
onMounted(async () => {
  await Promise.all([
    cargarProductos(),
    cargarCategorias()
  ])

  // Verificar estado de autenticación para el carrito
  carrito.verificarEstadoAutenticacion()
})



// 🚀 Función para cargar productos desde la API
const cargarProductos = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/productos')

    // Mapear los datos para que coincidan con la estructura esperada
    products.value = (response.data.data || response.data || []).map(producto => ({
      id: producto.id,
      nombre: producto.nombre,
      descripcion: producto.descripcion,
      precio: parseFloat(producto.precio),
      stock_actual: producto.stock_actual,
      categoria: producto.categoria?.nombre || 'Sin categoría',
      categoria_id: producto.categoria_id,
      // Manejar imágenes - solo guardar el nombre del archivo
      imagen: producto.imagenes && producto.imagenes.length > 0
        ? (typeof producto.imagenes[0] === 'string'
           ? producto.imagenes[0]
           : producto.imagenes[0].nombre)
        : null,
      imagenes: producto.imagenes || []
    })).filter(producto => producto.stock_actual > 0) // Solo productos en stock

  } catch (error) {
    console.error('Error al cargar productos:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los productos',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// 🏷️ Función para cargar categorías desde la API
const cargarCategorias = async () => {
  try {
    loadingCategories.value = true
    const response = await axios.get('/api/categorias-productos')

    categories.value = (response.data.data || response.data || []).map(categoria => ({
      id: categoria.id,
      nombre: categoria.nombre,
      // Contar productos por categoría
      cantidad: 0 // Se calculará en el computed
    }))

  } catch (error) {
    console.error('Error al cargar categorías:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las categorías',
      life: 3000
    })
  } finally {
    loadingCategories.value = false
  }
}

// 🔍 Categorías con conteo de productos
const categoriasConConteo = computed(() => {
  return categories.value.map(categoria => ({
    ...categoria,
    cantidad: products.value.filter(producto =>
      producto.categoria === categoria.nombre
    ).length
  })).filter(categoria => categoria.cantidad > 0) // Solo mostrar categorías con productos
})

// 🔍 Productos filtrados (sin paginación)
const allFilteredProducts = computed(() => {
  let filtered = [...products.value]

  // Filtrar por término de búsqueda
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(product =>
      product.nombre.toLowerCase().includes(search) ||
      product.descripcion.toLowerCase().includes(search) ||
      product.categoria.toLowerCase().includes(search)
    )
  }

  // Filtrar por categorías seleccionadas
  if (selectedCategories.value.length > 0) {
    filtered = filtered.filter(product =>
      selectedCategories.value.includes(product.categoria)
    )
  }

  // Filtrar por precio mínimo
  if (minPrice.value && !isNaN(minPrice.value)) {
    filtered = filtered.filter(product =>
      product.precio >= parseFloat(minPrice.value)
    )
  }

  // Filtrar por precio máximo
  if (maxPrice.value && !isNaN(maxPrice.value)) {
    filtered = filtered.filter(product =>
      product.precio <= parseFloat(maxPrice.value)
    )
  }

  return filtered
})

// 📄 Cálculo del total de páginas
const totalPagesComputed = computed(() => {
  return Math.ceil(allFilteredProducts.value.length / itemsPerPage.value)
})

// 📄 Productos paginados para mostrar
const filteredProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return allFilteredProducts.value.slice(start, end)
})

// 📊 Estadísticas de precios para ayudar al usuario
const preciosStats = computed(() => {
  if (products.value.length === 0) return { min: 0, max: 0, promedio: 0 }

  const precios = products.value.map(p => p.precio)
  return {
    min: Math.min(...precios),
    max: Math.max(...precios),
    promedio: precios.reduce((a, b) => a + b, 0) / precios.length
  }
})

// 🔧 Funciones para filtros
const toggleCategory = (categoryName) => {
  const index = selectedCategories.value.indexOf(categoryName)
  if (index > -1) {
    selectedCategories.value.splice(index, 1)
  } else {
    selectedCategories.value.push(categoryName)
  }
  currentPage.value = 1 // Resetear a la primera página cuando cambian los filtros
}

const clearFilters = () => {
  selectedCategories.value = []
  minPrice.value = ''
  maxPrice.value = ''
  searchTerm.value = ''
  currentPage.value = 1 // Resetear a la primera página

  toast.add({
    severity: 'info',
    summary: 'Filtros limpiados',
    detail: 'Se han removido todos los filtros',
    life: 2000
  })
}

const applyPriceFilter = () => {
  currentPage.value = 1 // Resetear a la primera página cuando se aplican filtros
  // Los filtros se aplican automáticamente por la reactividad
  toast.add({
    severity: 'success',
    summary: 'Filtros aplicados',
    detail: `Mostrando ${allFilteredProducts.value.length} productos`,
    life: 2000
  })
}

// 📄 Funciones de paginación
const goToPage = (page) => {
  if (page >= 1 && page <= totalPagesComputed.value) {
    currentPage.value = page
  }
}

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1)
  }
}

const goToNextPage = () => {
  if (currentPage.value < totalPagesComputed.value) {
    goToPage(currentPage.value + 1)
  }
}

//  Watchers para resetear la paginación cuando cambien los filtros
watch(searchTerm, () => {
  currentPage.value = 1
})

watch([minPrice, maxPrice], () => {
  currentPage.value = 1
})

// 🛒 Funciones para los botones
const comprarProducto = (producto) => {
  if (producto.stock_actual <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Sin stock',
      detail: `El producto "${producto.nombre}" no está disponible`,
      life: 3000
    })
    return
  }

  // Verificar si el usuario está autenticado
  const user = page.props.auth?.user
  if (!user) {
    // Mostrar modal de autenticación
    productoSeleccionado.value = producto
    mostrarModalAuth.value = true
    return
  }

  // Agregar al carrito usando Pinia
  carrito.agregarProducto(producto)

  // Opcional: Mostrar el carrito brevemente
  setTimeout(() => {
    carrito.mostrarCarrito()
  }, 500)
}

const verDetalles = (producto) => {
  // Aquí podrías navegar a una página de detalles del producto
  toast.add({
    severity: 'info',
    summary: 'Detalles del producto',
    detail: `${producto.nombre} - $${producto.precio.toFixed(2)} - ${producto.categoria} - Stock: ${producto.stock_actual}`,
    life: 5000
  })
}

// 🔄 Función para recargar datos (solo para el botón de estado vacío)
const recargarDatos = async () => {
  await Promise.all([
    cargarProductos(),
    cargarCategorias()
  ])

  toast.add({
    severity: 'success',
    summary: 'Datos actualizados',
    detail: 'La información se ha actualizado correctamente',
    life: 2000
  })
}
</script>

<template>
  <Catalogo>
    <!-- Toast para notificaciones -->
    <Toast class="z-[9999]" />

    <div class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-red-50/30 w-full pt-20 md:pt-28 lg:pt-32 xl:pt-36">
      <!-- Header Professional - Full Width -->
      <div class="w-full mb-4 -mt-0 md:-mt-3">
        <div class="bg-gradient-to-r from-red-600 via-red-500 to-blue-600 text-white text-center py-4 sm:py-6 relative shadow-xl">
          <!-- Botón del carrito en la esquina superior derecha -->
          <div class="absolute top-4 right-4">
            <CarritoButton />
          </div>

          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">🛍️ Productos</h1>
          <p class="text-base sm:text-lg text-red-100 px-4">Encuentra los mejores productos para tu viaje</p>
        </div>
      </div>

      <!-- Content Container -->
      <div class="w-full px-2 sm:px-4 pb-4 sm:pb-4 lg:pb-4">

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-8">
          <!-- Panel de Filtros -->
          <FiltrosPanel
            :categories="categoriasConConteo"
            :selectedCategories="selectedCategories"
            :minPrice="minPrice"
            :maxPrice="maxPrice"
            :showMobileFilters="showMobileFilters"
            :loadingCategories="loadingCategories"
            :preciosStats="preciosStats"
            @update:selectedCategories="selectedCategories = $event"
            @update:minPrice="minPrice = $event"
            @update:maxPrice="maxPrice = $event"
            @update:showMobileFilters="showMobileFilters = $event"
            @toggle-category="toggleCategory"
            @clear-filters="clearFilters"
            @apply-price-filter="applyPriceFilter"
          />

          <!-- Grid de Productos -->
          <div class="flex-1">
            <!-- Barra de búsqueda e información -->
            <SearchBar
              :searchTerm="searchTerm"
              :allFilteredProducts="allFilteredProducts"
              :selectedCategories="selectedCategories"
              :minPrice="minPrice"
              :maxPrice="maxPrice"
              @update:searchTerm="searchTerm = $event"
            />

            <!-- Grid de productos -->
            <ProductGrid
              :loading="loading"
              :filteredProducts="filteredProducts"
              @comprar-producto="comprarProducto"
              @ver-detalles="verDetalles"
            />

            <!-- Controles de paginación -->
            <PaginationControls
              :currentPage="currentPage"
              :totalPagesComputed="totalPagesComputed"
              :allFilteredProducts="allFilteredProducts"
              :itemsPerPage="itemsPerPage"
              @update:currentPage="currentPage = $event"
              @update:itemsPerPage="itemsPerPage = $event"
              @go-to-page="goToPage"
              @go-to-previous-page="goToPreviousPage"
              @go-to-next-page="goToNextPage"
            />

            <!-- Estado vacío -->
            <EmptyState
              :loading="loading"
              :filteredProducts="filteredProducts"
              :products="products"
              @clear-filters="clearFilters"
              @recargar-datos="recargarDatos"
            />
          </div>
        </div>
      </div> <!-- End Content Container -->
    </div> <!-- End Main Background -->

    <!-- Modal de autenticación requerida -->
    <ModalAuthRequerido
      v-model:visible="mostrarModalAuth"
      :productoInfo="productoSeleccionado"
    />

    <!-- Carrito de compras -->
    <CarritoCompras />
  </Catalogo>
</template>
