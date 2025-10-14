import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useCarritoStore = defineStore('carrito', () => {
  // 🛒 Estado del carrito
  const items = ref([])
  const isVisible = ref(false)
  const page = usePage()

  // 📊 Computed properties
  const itemCount = computed(() => {
    return items.value.reduce((total, item) => total + item.cantidad, 0)
  })

  const totalPrice = computed(() => {
    return items.value.reduce((total, item) => total + (item.precio * item.cantidad), 0)
  })

  const isEmpty = computed(() => items.value.length === 0)

  // 🔧 Acciones del carrito
  const agregarProducto = (producto) => {
    const existingItem = items.value.find(item => item.id === producto.id)

    if (existingItem) {
      // Si ya existe, incrementar cantidad
      existingItem.cantidad += 1
    } else {
      // Si no existe, agregar nuevo item
      items.value.push({
        id: producto.id,
        nombre: producto.nombre,
        precio: producto.precio,
        imagen: producto.imagen,
        stock_actual: producto.stock_actual,
        cantidad: 1
      })
    }

    // Guardar en localStorage para persistencia
    guardarEnStorage()
  }

  const eliminarProducto = (productoId) => {
    const index = items.value.findIndex(item => item.id === productoId)
    if (index > -1) {
      items.value.splice(index, 1)
      guardarEnStorage()
    }
  }

  const actualizarCantidad = (productoId, nuevaCantidad) => {
    const item = items.value.find(item => item.id === productoId)
    if (item) {
      if (nuevaCantidad <= 0) {
        eliminarProducto(productoId)
      } else if (nuevaCantidad <= item.stock_actual) {
        item.cantidad = nuevaCantidad
        guardarEnStorage()
      }
    }
  }

  const incrementarCantidad = (productoId) => {
    const item = items.value.find(item => item.id === productoId)
    if (item && item.cantidad < item.stock_actual) {
      item.cantidad += 1
      guardarEnStorage()
    }
  }

  const decrementarCantidad = (productoId) => {
    const item = items.value.find(item => item.id === productoId)
    if (item) {
      if (item.cantidad <= 1) {
        eliminarProducto(productoId)
      } else {
        item.cantidad -= 1
        guardarEnStorage()
      }
    }
  }

  const limpiarCarrito = () => {
    items.value = []
    guardarEnStorage()
  }

  const limpiarCarritoAlCerrarSesion = () => {
    items.value = []
    isVisible.value = false
    localStorage.removeItem('carrito_agencia_vasir')
  }

  const verificarEstadoAutenticacion = () => {
    const user = page.props.auth?.user
    const carritoTieneItems = items.value.length > 0

    // Si no hay usuario logueado pero hay items en el carrito, limpiar
    if (!user && carritoTieneItems) {
      limpiarCarritoAlCerrarSesion()
    }
  }

  const toggleVisibility = () => {
    isVisible.value = !isVisible.value
  }

  const mostrarCarrito = () => {
    isVisible.value = true
  }

  const ocultarCarrito = () => {
    isVisible.value = false
  }

  // 💾 Persistencia en localStorage
  const guardarEnStorage = () => {
    localStorage.setItem('carrito_agencia_vasir', JSON.stringify(items.value))
  }

  const cargarDesdeStorage = () => {
    const savedCart = localStorage.getItem('carrito_agencia_vasir')
    if (savedCart) {
      try {
        items.value = JSON.parse(savedCart)
        // Verificar estado de autenticación después de cargar
        verificarEstadoAutenticacion()
      } catch (error) {
        console.error('Error al cargar carrito desde localStorage:', error)
        items.value = []
      }
    }
  }

  // 🔍 Utilidades
  const getItem = (productoId) => {
    return items.value.find(item => item.id === productoId)
  }

  const hasItem = (productoId) => {
    return items.value.some(item => item.id === productoId)
  }

  const getItemQuantity = (productoId) => {
    const item = getItem(productoId)
    return item ? item.cantidad : 0
  }

  // Cargar datos al inicializar el store
  cargarDesdeStorage()

  return {
    // Estado
    items,
    isVisible,

    // Computed
    itemCount,
    totalPrice,
    isEmpty,

    // Acciones
    agregarProducto,
    eliminarProducto,
    actualizarCantidad,
    incrementarCantidad,
    decrementarCantidad,
    limpiarCarrito,
    limpiarCarritoAlCerrarSesion,
    toggleVisibility,
    mostrarCarrito,
    ocultarCarrito,

    // Utilidades
    getItem,
    hasItem,
    getItemQuantity,
    cargarDesdeStorage,
    guardarEnStorage,
    verificarEstadoAutenticacion
  }
})
