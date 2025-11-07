import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useCarritoStore = defineStore('carrito', () => {
  // 🛒 Estado del carrito
  const items = ref([])
  const isVisible = ref(false)
  const page = usePage()
  
  // Estado para la animación
  const lastAddedQuantity = ref(0)
  const triggerAnimation = ref(0)

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
    const cantidadAAgregar = producto.cantidadSeleccionada || 1

    if (existingItem) {
      // Si ya existe, validar stock antes de incrementar cantidad
      const nuevaCantidad = existingItem.cantidad + cantidadAAgregar
      if (nuevaCantidad <= existingItem.stock_actual) {
        existingItem.cantidad = nuevaCantidad
        // Trigger animation con la cantidad agregada
        lastAddedQuantity.value = cantidadAAgregar
        triggerAnimation.value++
        return { 
          success: true, 
          message: cantidadAAgregar === 1 
            ? 'Producto agregado al carrito' 
            : `${cantidadAAgregar} productos agregados al carrito`
        }
      } else {
        return { success: false, message: 'No hay suficiente stock disponible' }
      }
    } else {
      // Si no existe, validar que hay stock disponible
      if (producto.stock_actual >= cantidadAAgregar) {
        items.value.push({
          id: producto.id,
          nombre: producto.nombre,
          precio: producto.precio,
          imagen: producto.imagen,
          stock_actual: producto.stock_actual,
          cantidad: cantidadAAgregar
        })
        // Trigger animation con la cantidad agregada
        lastAddedQuantity.value = cantidadAAgregar
        triggerAnimation.value++
        return { 
          success: true, 
          message: cantidadAAgregar === 1 
            ? 'Producto agregado al carrito'
            : `${cantidadAAgregar} productos agregados al carrito`
        }
      } else {
        return { success: false, message: 'No hay suficiente stock disponible' }
      }
    }
  }

  const eliminarProducto = (productoId) => {
    const index = items.value.findIndex(item => item.id === productoId)
    if (index > -1) {
      items.value.splice(index, 1)
    }
  }

  const actualizarCantidad = (productoId, nuevaCantidad) => {
    const item = items.value.find(item => item.id === productoId)
    if (item) {
      if (nuevaCantidad <= 0) {
        eliminarProducto(productoId)
        return { success: true, message: 'Producto eliminado del carrito' }
      } else if (nuevaCantidad <= item.stock_actual) {
        item.cantidad = nuevaCantidad
        return { success: true, message: 'Cantidad actualizada' }
      } else {
        return { success: false, message: `Solo hay ${item.stock_actual} unidades disponibles` }
      }
    }
    return { success: false, message: 'Producto no encontrado en el carrito' }
  }

  const incrementarCantidad = (productoId) => {
    const item = items.value.find(item => item.id === productoId)
    if (item) {
      if (item.cantidad < item.stock_actual) {
        item.cantidad += 1
        return { success: true, message: 'Cantidad incrementada' }
      } else {
        return { success: false, message: 'No hay suficiente stock disponible' }
      }
    }
    return { success: false, message: 'Producto no encontrado en el carrito' }
  }

  const decrementarCantidad = (productoId) => {
    const item = items.value.find(item => item.id === productoId)
    if (item) {
      if (item.cantidad <= 1) {
        eliminarProducto(productoId)
      } else {
        item.cantidad -= 1
      }
    }
  }

  const limpiarCarrito = () => {
    items.value = []
  }

  const limpiarCarritoAlCerrarSesion = () => {
    items.value = []
    isVisible.value = false
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

  // � Estado temporal del carrito (sin persistencia)
  // El carrito ahora solo existe durante la sesión activa del navegador
  // Se limpia automáticamente al refrescar la página o cambiar de ruta

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

  // El carrito ahora se inicializa vacío siempre

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
    verificarEstadoAutenticacion,
    
    // Animación
    lastAddedQuantity,
    triggerAnimation
  }
})
