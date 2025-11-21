<script setup>
import Catalogo from '../Catalogo.vue'
import ModalAuthRequerido from './Modales/ModalAuthRequerido.vue'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router, usePage, Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faMapMarkerAlt, faChevronLeft, faChevronRight, faImage, faXmark, faPause, faPlay, faPlane, faEnvelope, faInfoCircle, faSearch, faTimes, faCheck, faSpinner, faSave } from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Toast from 'primevue/toast'
import DatePicker from 'primevue/datepicker'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'

// Recibir los props del controlador (opcional, como fallback)
const props = defineProps({
  hoteles: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const user = computed(() => page.props.auth.user)
const config = computed(() => page.props.config || {})

const toast = useToast()

// Estados reactivos principales
const hoteles = ref([])
const loading = ref(true)
const error = ref(null)

// Variable para búsqueda
const searchQuery = ref('')

// URL de la API para hoteles
const url = "/api/hoteles"

// Estados para modal de reserva de hotel
const showReservaModal = ref(false)
const hotelSeleccionado = ref(null)
const procesandoReserva = ref(false)
const tieneClienteExistente = ref(false)

// Estados para modal de autenticación requerida
const showAuthModal = ref(false)
const hotelParaReserva = ref(null)

// Datos del formulario de reserva
const reservaForm = ref({
  fecha_entrada: null,
  fecha_salida: null,
  cantidad_personas: 1,
  cantidad_habitaciones: 1,
  cliente_data: {
    numero_identificacion: '',
    fecha_nacimiento: null,
    genero: '',
    direccion: '',
    telefono: '',
    tipo_documento: 'DUI'
  }
})

// Los tipos de documento ahora son ENUM: DUI, PASAPORTE

// Estado de validación del teléfono
const telefonoValidation = ref({
  isValid: false,
  country: null,
  formattedNumber: '',
  mensaje: ''
})

// Estado de validación del documento
const documentoValidation = ref({
  isValid: true,
  mensaje: ''
})

// Estado de validación de formato en tiempo real
const formatoValidation = ref({
  isValid: true,
  mensaje: ''
})



// Computed properties para hoteles filtrados por búsqueda
const hotelesDisponibles = computed(() => {
  let filtrados = hoteles.value.filter(hotel => hotel.estado === 'activo')

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtrados = filtrados.filter(hotel => {
      const nombre = hotel.nombre?.toLowerCase() || ''
      const provincia = hotel.provincia?.nombre?.toLowerCase() || ''
      const pais = hotel.pais?.nombre?.toLowerCase() || ''
      const direccion = hotel.direccion?.toLowerCase() || ''

      return nombre.includes(query) ||
             provincia.includes(query) ||
             pais.includes(query) ||
             direccion.includes(query)
    })
  }

  return filtrados
})

const hotelesNoDisponibles = computed(() => {
  return hoteles.value.filter(hotel => hotel.estado === 'inactivo')
})

// Función para obtener hoteles desde la API
const obtenerHoteles = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`);
    }

    const data = await response.json()
    hoteles.value = data || []

  } catch (err) {
    console.error('Error al obtener hoteles:', err)
    error.value = err.message
    // Usar props como fallback si hay error en la API
    hoteles.value = props.hoteles || []
  } finally {
    loading.value = false
  }
}

// Funciones para generar mensajes personalizados de WhatsApp
function generarMensajeWhatsApp(tipo = 'general') {
  const mensajes = {
    general: 'Hola VASIR, me gustaría recibir información sobre sus servicios turísticos. ¿Podrían ayudarme?',
    aerolíneas: 'Hola VASIR, necesito información sobre vuelos y tarifas de aerolíneas. ¿Podrían ayudarme?',

  }
  return encodeURIComponent(mensajes[tipo] || mensajes.general)
}

// Función para abrir WhatsApp con mensaje personalizado
function abrirWhatsApp(tipo = 'general') {
  // Verificar si el usuario está autenticado y su rol
  const user = page.props.auth?.user

  // Verificar si el usuario tiene roles de Administrador o Empleado
  if (user && user.roles && user.roles.length > 0) {
    const userRoles = user.roles.map(role => typeof role === 'string' ? role : role.name || role.rol)

    if (userRoles.includes('Administrador') || userRoles.includes('Empleado')) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Esta acción está disponible solo para clientes. Los administradores y empleados no pueden realizar consultas de WhatsApp como clientes.',
        life: 5000
      })
      return
    }
  }

  const numeroWhatsApp = '50379858777'
  const mensaje = generarMensajeWhatsApp(tipo)
  const url = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`
  window.open(url, '_blank')
}


// Ya no necesitamos cargar tipos de documento desde API - ahora usamos ENUM

// Variables para el carrusel de imágenes
const showImageDialog = ref(false)
const selectedHotelImages = ref([])
const currentImageIndex = ref(0)
const galleryIntervalId = ref(null)
const isGalleryAutoPlaying = ref(true)

// Variables para carruseles automáticos en las cards
const cardImageIndices = ref({})
const intervalIds = ref({})

// Función para obtener la imagen actual del carrusel automático
const obtenerImagenActual = (hotel) => {
  if (!hotel.imagenes || hotel.imagenes.length === 0) {
    return 'https://via.placeholder.com/400x300/ef4444/ffffff?text=Sin+Imagen'
  }

  // Asegurarse de que el hotel tenga un ID para el índice
  if (!hotel.id) return `/storage/hoteles/${typeof hotel.imagenes[0] === 'string' ? hotel.imagenes[0] : hotel.imagenes[0].nombre}`;

  // Si solo tiene una imagen, mostrar esa
  if (hotel.imagenes.length === 1) {
    const nombreImagen = typeof hotel.imagenes[0] === 'string' ? hotel.imagenes[0] : hotel.imagenes[0].nombre
    return `/storage/hoteles/${nombreImagen}`
  }

  // Si tiene múltiples imágenes, usar el índice del carrusel
  const currentIndex = cardImageIndices.value[hotel.id] || 0
  const imagen = hotel.imagenes[currentIndex]
  const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre

  return `/storage/hoteles/${nombreImagen}`;
}

// Función para inicializar carrusel automático
const inicializarCarrusel = (hotel) => {
  if (!hotel.imagenes || hotel.imagenes.length <= 1 || !hotel.id) return

  // Inicializar índice si no existe
  if (!(hotel.id in cardImageIndices.value)) {
    cardImageIndices.value[hotel.id] = 0
  }

  // Crear intervalo para cambiar imágenes automáticamente
  if (!(hotel.id in intervalIds.value)) {
    intervalIds.value[hotel.id] = setInterval(() => {
      // Validación adicional de seguridad
      if (hotel.imagenes && hotel.imagenes.length > 0) {
        cardImageIndices.value[hotel.id] = (cardImageIndices.value[hotel.id] + 1) % hotel.imagenes.length
      }
    }, 3000) // Cambiar cada 3 segundos
  }
}

// Función para detener todos los carruseles
const detenerTodosLosCarruseles = () => {
  Object.keys(intervalIds.value).forEach(hotelId => {
    clearInterval(intervalIds.value[hotelId])
  })
  intervalIds.value = {}
  detenerCarruselGaleria() // También detener el carrusel de la galería
}

// Watch para manejar teléfono precargado
watch(() => reservaForm.value.cliente_data.telefono, (nuevoTelefono, telefonoAnterior) => {
  // Si hay un teléfono precargado y es diferente del anterior
  if (nuevoTelefono && nuevoTelefono !== telefonoAnterior && tieneClienteExistente.value) {
    // Marcar como válido si viene de datos precargados
    telefonoValidation.value = {
      isValid: true,
      country: { name: 'Válido', code: '' },
      formattedNumber: nuevoTelefono,
      mensaje: 'Número válido (guardado previamente)'
    }
  }
}, { immediate: true })

// Lifecycle hooks
onMounted(async () => {
  // Obtener hoteles desde la API
  await obtenerHoteles()

  // Inicializar carruseles para todos los hoteles con múltiples imágenes
  hoteles.value.forEach(hotel => {
    if (hotel.imagenes && hotel.imagenes.length > 1) {
      inicializarCarrusel(hotel)
    }
  })


})

onUnmounted(() => {
  detenerTodosLosCarruseles()
})

// Función para obtener todas las imágenes
const obtenerTodasLasImagenes = (imagenes) => {
  if (!imagenes || imagenes.length === 0) {
    return ['https://via.placeholder.com/400x300/ef4444/ffffff?text=Sin+Imagen']
  }

  return imagenes.map(imagen => {
    const nombreImagen = typeof imagen === 'string' ? imagen : imagen.nombre
    return `/storage/hoteles/${nombreImagen}`;
  })
}

// Función para mostrar galería de imágenes
const mostrarGaleria = (hotel) => {
  selectedHotelImages.value = obtenerTodasLasImagenes(hotel.imagenes)
  currentImageIndex.value = 0
  showImageDialog.value = true
  isGalleryAutoPlaying.value = true

  // Iniciar carrusel automático en la galería si hay múltiples imágenes
  if (selectedHotelImages.value.length > 1) {
    iniciarCarruselGaleria()
  }
}

// Función para alternar play/pausa del carrusel
const toggleGalleryAutoPlay = () => {
  if (isGalleryAutoPlaying.value) {
    detenerCarruselGaleria()
    isGalleryAutoPlaying.value = false
  } else {
    iniciarCarruselGaleria()
    isGalleryAutoPlaying.value = true
  }
}

// Función para iniciar carrusel automático en la galería
const iniciarCarruselGaleria = () => {
  detenerCarruselGaleria() // Limpiar cualquier intervalo anterior
  galleryIntervalId.value = setInterval(() => {
    siguienteImagen()
  }, 4000) // Cambiar cada 4 segundos
}

// Función para detener carrusel automático en la galería
const detenerCarruselGaleria = () => {
  if (galleryIntervalId.value) {
    clearInterval(galleryIntervalId.value)
    galleryIntervalId.value = null
  }
}

// Funciones para navegar en el carrusel
const siguienteImagen = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % selectedHotelImages.value.length
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

const imagenAnterior = () => {
  currentImageIndex.value = currentImageIndex.value === 0
    ? selectedHotelImages.value.length - 1
    : currentImageIndex.value - 1
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

const irAImagen = (index) => {
  currentImageIndex.value = index
  // Reiniciar el timer automático solo si está activado
  if (showImageDialog.value && selectedHotelImages.value.length > 1 && isGalleryAutoPlaying.value) {
    iniciarCarruselGaleria()
  }
}

// Funciones para los botones
const contactarHotel = (hotel) => {
  // Verificar roles para restricción
  if (user.value && user.value.roles && Array.isArray(user.value.roles)) {
    const tieneRolRestringido = user.value.roles.some(role =>
      role.name === 'Administrador' || role.name === 'Empleado'
    )

    if (tieneRolRestringido) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Solo las cuentas de Cliente pueden contactar hoteles por WhatsApp desde esta sección.',
        life: 5000
      })
      return
    }
  }

  const mensaje = `Hola, estoy interesado/a en obtener más información sobre el hotel "${hotel.nombre}" ubicado en ${hotel.direccion}. ¿Podrían proporcionarme detalles sobre disponibilidad, precios y servicios? Gracias.`
  const whatsappUrl = `https://wa.me/50379858777?text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
}

const navegarADetalle = (hotel) => {
  router.visit(`/hoteles/${hotel.id}`)
}

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  searchQuery.value = ''
}

// Función para cargar datos del cliente existente
const cargarDatosCliente = async () => {
  if (!user.value) {
    return null
  }

  // Verificar primero si el usuario tiene datos usando la API de verificación
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    const verificacionResponse = await fetch('/api/verificar-datos-cliente', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': token || '',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })

    if (verificacionResponse.ok) {
      const verificacion = await verificacionResponse.json()

      // Si no tiene datos completos, no hacer la segunda petición
      if (!verificacion.tiene_datos_completos) {
        return null
      }

      // Si tiene datos completos, obtenerlos
      const response = await fetch('/api/clientes/mi-perfil', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
          'X-CSRF-TOKEN': token || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      })

      if (response.ok) {
        const data = await response.json()
        return data.cliente || data || null
      }
    }

    return null
  } catch (error) {
    // Solo logear errores de red/conexión reales
    console.error('[Reservaciones] Error de conexión al cargar datos del cliente:', error)
    return null
  }
}

// Funciones para manejo de reservas de hoteles
const abrirModalReserva = async (hotel) => {
  if (!user.value) {
    // Guardar el hotel para la reserva pendiente y mostrar modal de auth
    hotelParaReserva.value = hotel
    showAuthModal.value = true
    return
  }

  // Verificar roles para restricción
  if (user.value.roles && Array.isArray(user.value.roles)) {
    const tieneRolRestringido = user.value.roles.some(role =>
      role.name === 'Administrador' || role.name === 'Empleado'
    )

    if (tieneRolRestringido) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Solo las cuentas de Cliente pueden realizar reservas de hoteles. Por favor, utilice una cuenta de Cliente para continuar.',
        life: 5000
      })
      return
    }
  }

  hotelSeleccionado.value = hotel
  showReservaModal.value = true

  // Resetear estado
  tieneClienteExistente.value = false

  // Inicializar formulario con datos básicos
  reservaForm.value = {
    fecha_entrada: null,
    fecha_salida: null,
    cantidad_personas: 1,
    cantidad_habitaciones: 1,
    cliente_data: {
      numero_identificacion: '',
      fecha_nacimiento: null,
      genero: '',
      direccion: '',
      telefono: '',
      tipo_documento: 'DUI'
    }
  }

  // Cargar datos existentes del cliente si está logueado
  try {
    const clienteExistente = await cargarDatosCliente()

    if (clienteExistente) {
      tieneClienteExistente.value = true

      // Formatear fecha de nacimiento para Calendar
      let fechaNacimientoFormateada = null
      if (clienteExistente.fecha_nacimiento) {
        try {
          fechaNacimientoFormateada = new Date(clienteExistente.fecha_nacimiento)
        } catch (error) {
          fechaNacimientoFormateada = null
        }
      }

    // Actualizar formulario con datos existentes
    reservaForm.value.cliente_data = {
      numero_identificacion: clienteExistente.numero_identificacion || '',
      fecha_nacimiento: fechaNacimientoFormateada,
      genero: clienteExistente.genero || '',
      direccion: clienteExistente.direccion || '',
      telefono: clienteExistente.telefono || '',
      tipo_documento: clienteExistente.tipo_documento || 'DUI'
    }

    // Verificar si la fecha de nacimiento necesita corrección
    const hoy = new Date()
    const fechaNac = new Date(clienteExistente.fecha_nacimiento)

    if (fechaNac >= hoy) {
      toast.add({
        severity: 'warn',
        summary: 'Revisar Datos',
        detail: 'Sus datos han sido precargados, pero la fecha de nacimiento debe ser corregida (debe ser anterior a hoy)',
        life: 6000
      })
    } else {
      toast.add({
        severity: 'info',
        summary: 'Datos Precargados',
        detail: 'Sus datos personales han sido cargados automáticamente. Puede modificarlos si es necesario.',
        life: 4000
      })
    }
    }
  } catch (error) {
    // Si no se pueden cargar los datos, continuamos sin datos precargados
    tieneClienteExistente.value = false
  }
}

const cerrarModalReserva = () => {
  showReservaModal.value = false
  hotelSeleccionado.value = null
}

// Función para navegar al perfil del usuario
const navegarAlPerfil = () => {
  router.visit('/profile')
}

const crearReservaHotel = async () => {
  try {
    procesandoReserva.value = true

    // Validaciones básicas
    if (!reservaForm.value.fecha_entrada || !reservaForm.value.fecha_salida) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'Las fechas de entrada y salida son requeridas',
        life: 4000
      })
      return
    }

    if (new Date(reservaForm.value.fecha_entrada) >= new Date(reservaForm.value.fecha_salida)) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'La fecha de salida debe ser posterior a la fecha de entrada',
        life: 4000
      })
      return
    }

    if (!reservaForm.value.cliente_data.numero_identificacion || !reservaForm.value.cliente_data.numero_identificacion.trim()) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'El número de identificación es requerido',
        life: 4000
      })
      return
    }

    // Validar formato del número de identificación
    if (!validarNumeroIdentificacion()) {
      return
    }

    // Validar documento duplicado
    if (!tieneClienteExistente.value && reservaForm.value.cliente_data.numero_identificacion && !documentoValidation.value.isValid) {
      toast.add({
        severity: 'warn',
        summary: 'Documento no disponible',
        detail: documentoValidation.value.mensaje || 'Este número de identificación no está disponible.',
        life: 5000
      })
      return
    }

    if (!reservaForm.value.cliente_data.telefono) {
      toast.add({
        severity: 'error',
        summary: 'Error de Validación',
        detail: 'El número de teléfono es requerido',
        life: 4000
      })
      return
    }

    // Solo validar formato si el teléfono fue modificado (no viene de datos precargados)
    if (reservaForm.value.cliente_data.telefono && telefonoValidation.value.isValid === false && telefonoValidation.value.mensaje !== 'Número válido (guardado previamente)') {
      // Si el mensaje contiene información sobre duplicado, usar mensaje específico
      if (telefonoValidation.value.mensaje && (telefonoValidation.value.mensaje.includes('registrado') || telefonoValidation.value.mensaje.includes('diferente'))) {
        toast.add({
          severity: 'warn',
          summary: 'Teléfono ya registrado',
          detail: telefonoValidation.value.mensaje,
          life: 5000
        })
      } else {
        toast.add({
          severity: 'error',
          summary: 'Teléfono inválido',
          detail: 'Por favor, ingrese un número de teléfono válido',
          life: 4000
        })
      }
      return
    }

    // Validar cantidad de personas (máximo 10)
    if (reservaForm.value.cantidad_personas > 10) {
      toast.add({
        severity: 'error',
        summary: 'Cantidad de personas excede el límite',
        detail: 'La cantidad máxima de personas permitida es 10',
        life: 4000
      })
      return
    }

    // Validar fecha de nacimiento (debe ser mayor de 18 años)
    if (reservaForm.value.cliente_data.fecha_nacimiento) {
      const validacionEdad = validarEdadMinima(reservaForm.value.cliente_data.fecha_nacimiento)
      if (!validacionEdad.esValido) {
        toast.add({
          severity: 'error',
          summary: 'Edad insuficiente',
          detail: validacionEdad.mensaje + '. Los usuarios deben ser mayores de edad para realizar reservas.',
          life: 6000
        })
        return
      }
    }

    // Preparar datos para enviar
    const datosReserva = {
      hotel_id: hotelSeleccionado.value.id,
      fecha_entrada: reservaForm.value.fecha_entrada,
      fecha_salida: reservaForm.value.fecha_salida,
      cantidad_personas: reservaForm.value.cantidad_personas,
      cantidad_habitaciones: reservaForm.value.cantidad_habitaciones,
      cliente_data: reservaForm.value.cliente_data
    }

    // Aquí se haría la llamada a la API para crear la reserva de hotel
    const response = await fetch('/api/reservas/hotel', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'same-origin',
      body: JSON.stringify(datosReserva)
    })

    if (!response.ok) {
      const errorData = await response.json()
      console.error('[Reservaciones] Error del servidor:', errorData)

      // Si hay errores de validación específicos, mostrarlos
      if (errorData.errors) {
        const errores = Object.values(errorData.errors).flat().join(', ')
        throw new Error(`Error de validación: ${errores}`)
      }

      throw new Error(errorData.message || 'Error al crear la reserva')
    }

    const data = await response.json()

    toast.add({
      severity: 'success',
      summary: 'Reserva Creada',
      detail: 'Su reserva ha sido creada exitosamente. Recibirá una confirmación por email.',
      life: 6000
    })

    cerrarModalReserva()

  } catch (error) {
    console.error('Error al crear reserva de hotel:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'No se pudo crear la reserva. Intente nuevamente.',
      life: 4000
    })
  } finally {
    procesandoReserva.value = false
  }
}

// Función para obtener fecha mínima (hoy)
const getFechaMinima = () => {
  return new Date()
}

// Función para obtener fecha mínima de salida (día después de entrada)
const getFechaMinimaComputada = computed(() => {
  if (!reservaForm.value.fecha_entrada) return getFechaMinima()

  const fechaEntrada = new Date(reservaForm.value.fecha_entrada)
  fechaEntrada.setDate(fechaEntrada.getDate() + 1)
  return fechaEntrada
})

// Función para obtener fecha máxima de nacimiento (debe tener al menos 18 años)
const getFechaMaximaNacimiento = () => {
  const fechaMaxima = new Date()
  fechaMaxima.setFullYear(fechaMaxima.getFullYear() - 18)
  return fechaMaxima
}

// Función para validar edad mínima de 18 años
const validarEdadMinima = (fechaNacimiento) => {
  if (!fechaNacimiento) return { esValido: true, mensaje: '' }

  const hoy = new Date()
  const fechaNac = new Date(fechaNacimiento)
  const edad = hoy.getFullYear() - fechaNac.getFullYear()
  const mesNacimiento = fechaNac.getMonth()
  const diaNacimiento = fechaNac.getDate()
  const mesActual = hoy.getMonth()
  const diaActual = hoy.getDate()

  // Ajustar la edad si aún no ha cumplido años este año
  const edadReal = edad - ((mesActual < mesNacimiento || (mesActual === mesNacimiento && diaActual < diaNacimiento)) ? 1 : 0)

  if (edadReal < 18) {
    return {
      esValido: false,
      mensaje: `Debe ser mayor de edad (18 años). Edad actual: ${edadReal} años`
    }
  }

  return { esValido: true, mensaje: '' }
}

// Función para validar la unicidad del teléfono
const validarTelefonoUnico = async (telefono) => {
  if (!telefono) return { esValido: true, mensaje: '' }

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

    const response = await fetch('/api/clientes/validar-telefono', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ telefono })
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    return {
      esValido: data.disponible,
      mensaje: data.disponible ? 'Teléfono disponible' : data.mensaje || 'Este teléfono ya está registrado'
    }
  } catch (error) {
    console.error('[Reservaciones] Error al validar teléfono:', error)
    return { esValido: true, mensaje: 'Error al validar teléfono' }
  }
}

// Validar que el documento no esté duplicado
const validarDocumentoUnico = async (numeroIdentificacion) => {
  if (!numeroIdentificacion || numeroIdentificacion.length < 3) return

  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

    const response = await fetch('/api/clientes/validar-documento', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`,
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        numero_identificacion: numeroIdentificacion
      })
    })

    if (!response.ok) {
      throw new Error(`Error ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    if (data.disponible) {
      documentoValidation.value.mensaje = '✓ Número de identificación disponible'
      documentoValidation.value.isValid = true
    } else {
      documentoValidation.value.isValid = false
      documentoValidation.value.mensaje = data.message || 'Este número de identificación ya está registrado'
    }
  } catch (error) {
    console.error('[Reservaciones] Error validando documento:', error)
    documentoValidation.value.isValid = false
    if (error.response?.status === 403) {
      documentoValidation.value.mensaje = 'No tienes permisos para validar este documento'
    } else if (error.response?.status === 401) {
      documentoValidation.value.mensaje = 'Error de autenticación'
    } else {
      documentoValidation.value.mensaje = 'Error al validar documento'
    }
  }
}

// Función de validación del teléfono
const onValidate = async (phoneObject) => {
  try {
    if (phoneObject && typeof phoneObject === 'object') {
      telefonoValidation.value.isValid = phoneObject.valid === true
      telefonoValidation.value.country = { name: phoneObject.country, code: phoneObject.countryCode }
      telefonoValidation.value.formattedNumber = phoneObject.formatted || ''

      // Actualizar el modelo inmediatamente como hace EmpleadoController
      if (phoneObject.valid === true && phoneObject.formatted) {
        reservaForm.value.cliente_data.telefono = phoneObject.formatted
      }

      // Si los datos están precargados, no validar duplicados (ya son del mismo cliente)
      if (tieneClienteExistente.value) {
        if (phoneObject.valid === true) {
          telefonoValidation.value.mensaje = 'Número válido (guardado previamente)'
        } else if (reservaForm.value.cliente_data.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else {
          telefonoValidation.value.mensaje = ''
        }
      } else {
        // Solo validar duplicados para nuevos clientes
        if (reservaForm.value.cliente_data.telefono && phoneObject.valid === false) {
          telefonoValidation.value.mensaje = 'Número de teléfono inválido para ' + phoneObject.country
        } else if (phoneObject.valid === true) {
          // Validar con el teléfono ya actualizado en el modelo
          const validacionUnicidad = await validarTelefonoUnico(reservaForm.value.cliente_data.telefono)
          if (!validacionUnicidad.esValido) {
            telefonoValidation.value.isValid = false
            telefonoValidation.value.mensaje = validacionUnicidad.mensaje
          } else {
            telefonoValidation.value.mensaje = 'Número válido para ' + phoneObject.country
          }
        } else {
          telefonoValidation.value.mensaje = ''
        }
      }
    }
  } catch (error) {
    telefonoValidation.value.mensaje = 'Error en validación'
  }
}

// Función para formatear DUI automáticamente
const formatearDUI = (valor) => {
  // Solo permitir números (eliminar TODO lo que no sea dígito)
  const soloNumeros = valor.replace(/[^0-9]/g, '')

  // Limitar a 9 dígitos máximo
  const numerosLimitados = soloNumeros.substring(0, 9)

  // Agregar guión automáticamente después del 8vo dígito
  if (numerosLimitados.length > 8) {
    return numerosLimitados.substring(0, 8) + '-' + numerosLimitados.substring(8)
  }

  return numerosLimitados
}

// Función para formatear PASAPORTE automáticamente
const formatearPasaporte = (valor) => {
  // Solo permitir A-Z y 0-9, convertir a mayúsculas, máximo 9 caracteres
  return valor.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 9)
}

// Función para manejar entrada de texto según tipo de documento
const manejarEntradaDocumento = (event) => {
  const valor = event.target.value
  let valorFormateado = ''

  if (reservaForm.value.cliente_data.tipo_documento === 'DUI') {
    valorFormateado = formatearDUI(valor)
    reservaForm.value.cliente_data.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  } else if (reservaForm.value.cliente_data.tipo_documento === 'PASAPORTE') {
    valorFormateado = formatearPasaporte(valor)
    reservaForm.value.cliente_data.numero_identificacion = valorFormateado
    // Actualizar el valor del input inmediatamente
    event.target.value = valorFormateado
  }

  // Validar después de formatear
  validarNumeroIdentificacion()

  // Validar duplicados si el formato es válido y no es cliente existente
  if (reservaForm.value.cliente_data.numero_identificacion && reservaForm.value.cliente_data.numero_identificacion.length >= 3) {
    const isValidFormat = (reservaForm.value.cliente_data.tipo_documento === 'DUI' && /^\d{8}-\d{1}$/.test(reservaForm.value.cliente_data.numero_identificacion)) ||
                         (reservaForm.value.cliente_data.tipo_documento === 'PASAPORTE' && /^[A-Z0-9]{6,9}$/.test(reservaForm.value.cliente_data.numero_identificacion))

    if (isValidFormat && !tieneClienteExistente.value) {
      validarDocumentoUnico(reservaForm.value.cliente_data.numero_identificacion)
    } else {
      documentoValidation.value.mensaje = ''
      documentoValidation.value.isValid = true
    }
  }
}

// Validación en tiempo real del número de identificación
// Función para validar formato de documento en tiempo real (sin toasts)
const validarFormatoDocumento = (numeroIdentificacion, tipoDocumento) => {
  if (!numeroIdentificacion || !tipoDocumento) {
    formatoValidation.value.mensaje = ''
    formatoValidation.value.isValid = true
    return
  }

  if (tipoDocumento === 'DUI') {
    const duiRegex = /^\d{8}-\d{1}$/
    if (!duiRegex.test(numeroIdentificacion)) {
      formatoValidation.value.mensaje = 'El DUI debe tener 9 dígitos (formato: 12345678-9)'
      formatoValidation.value.isValid = false
    } else {
      formatoValidation.value.mensaje = ''
      formatoValidation.value.isValid = true
    }
  } else if (tipoDocumento === 'PASAPORTE') {
    const pasaporteRegex = /^[A-Z0-9]{6,9}$/
    if (!pasaporteRegex.test(numeroIdentificacion)) {
      formatoValidation.value.mensaje = 'El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)'
      formatoValidation.value.isValid = false
    } else {
      formatoValidation.value.mensaje = ''
      formatoValidation.value.isValid = true
    }
  }
}

const validarNumeroIdentificacion = () => {
  if (!reservaForm.value.cliente_data.numero_identificacion) {
    return true // No hay error si está vacío, se maneja en validación de campos requeridos
  }

  // Usar la validación silenciosa
  validarFormatoDocumento(
    reservaForm.value.cliente_data.numero_identificacion,
    reservaForm.value.cliente_data.tipo_documento
  )

  // Retornar el resultado sin mostrar toasts (solo para validación de envío)
  return formatoValidation.value.isValid
}

// Watchers para validación en tiempo real del documento
watch(() => reservaForm.value.cliente_data.numero_identificacion, (newValue) => {
  if (newValue && reservaForm.value.cliente_data.tipo_documento) {
    // Validar formato en tiempo real (sin toasts)
    validarFormatoDocumento(newValue, reservaForm.value.cliente_data.tipo_documento)

    // Validar duplicados si el formato es válido y no es cliente existente
    if (newValue.length >= 3 && formatoValidation.value.isValid) {
      if (!tieneClienteExistente.value) {
        validarDocumentoUnico(newValue)
      } else {
        documentoValidation.value.mensaje = ''
        documentoValidation.value.isValid = true
      }
    }
  } else {
    documentoValidation.value.mensaje = ''
    documentoValidation.value.isValid = true
  }
})

watch(() => reservaForm.value.cliente_data.tipo_documento, () => {
  // Solo limpiar número cuando cambia el tipo de documento si NO tiene datos precargados
  // Esto evita que se borren los datos cuando se cargan desde el cliente existente
  if (!tieneClienteExistente.value) {
    reservaForm.value.cliente_data.numero_identificacion = ''
  }
  documentoValidation.value.mensaje = ''
  documentoValidation.value.isValid = true
  formatoValidation.value.mensaje = ''
  formatoValidation.value.isValid = true
})

// Watch para validación de fecha de nacimiento en tiempo real
watch(() => reservaForm.value.cliente_data.fecha_nacimiento, (nuevaFecha) => {
  if (nuevaFecha) {
    const validacionEdad = validarEdadMinima(nuevaFecha)
    if (!validacionEdad.esValido) {
      toast.add({
        severity: 'error',
        summary: 'Edad insuficiente',
        detail: validacionEdad.mensaje + '. Los usuarios deben ser mayores de edad para realizar reservas.',
        life: 4000
      })
    }
  }
})
</script>

<template>
  <Catalogo>
    <Toast />
    <!-- Header Profesional con Stats Integradas - Ancho completo de la pantalla -->
    <div class="w-full bg-gradient-to-br from-white to-gray-50 shadow-xl overflow-hidden border-b border-gray-200 mb-3 sm:mb-4 mt-20 sm:mt-20 md:mt-28 lg:mt-32 xl:mt-32">
      <!-- Header con gradiente -->
      <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-red-600 text-white text-center py-4 sm:py-6">
        <div class="flex items-center justify-center gap-3 mb-1">
          <FontAwesomeIcon :icon="faImage" class="w-8 h-8 sm:w-12 sm:h-12 text-yellow-300 shadow-lg" />
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
            Hoteles Disponibles
          </h1>
        </div>
        <p class="text-base sm:text-lg text-blue-100 px-4">Descubre los mejores hoteles para tu estadía perfecta.</p>
      </div>


    </div>

    <!-- Contenido principal con padding -->
    <div class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen px-4 sm:px-6 lg:px-8 pb-8">
      <div class="w-full max-w-7xl mx-auto">

        <!-- Estado de carga -->
        <div v-if="loading && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 max-w-md mx-auto border border-gray-200">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
            <p class="text-lg font-semibold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Cargando hoteles...</p>
            <p class="text-sm text-gray-500 mt-2">Preparando los mejores alojamientos para ti</p>
          </div>
        </div>

        <!-- Estado de error -->
        <div v-else-if="error && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-red-50 to-orange-50 border-2 border-red-200 text-red-700 px-8 py-6 rounded-xl shadow-lg max-w-md mx-auto">
            <div class="text-4xl mb-4">⚠️</div>
            <h3 class="text-xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent mb-3">No se pudieron cargar los hoteles</h3>
            <p class="text-sm text-red-600 leading-relaxed">Por favor, intenta recargar la página o contacta con nosotros.</p>
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!loading && hoteles.length === 0" class="text-center py-12">
          <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl shadow-lg p-8 max-w-lg mx-auto">
            <div class="text-6xl mb-4">🏨</div>
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-3">No hay hoteles disponibles</h3>
            <p class="text-gray-600 mb-4 leading-relaxed">Próximamente tendremos nuevos hoteles disponibles.</p>
            <p class="text-sm text-gray-500">Mientras tanto, puedes explorar nuestros tours.</p>
          </div>
        </div>

        <!-- Barra de búsqueda -->
        <div v-if="hoteles.length > 0" class="bg-white rounded-xl p-6 shadow-md border border-gray-200 mb-8">
          <div class="max-w-2xl mx-auto">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
              <FontAwesomeIcon :icon="faSearch" class="w-5 h-5 text-blue-600 mr-2" />
              Buscar Hoteles
            </h3>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <FontAwesomeIcon :icon="faSearch" class="w-5 h-5 text-gray-400" />
              </div>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar por nombre del hotel, provincia, país o ubicación..."
                class="w-full pl-12 pr-12 py-4 text-gray-700 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300 text-lg"
              />
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                <button
                  @click="limpiarBusqueda"
                  class="text-gray-400 hover:text-red-500 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Limpiar búsqueda"
                >
                  <FontAwesomeIcon :icon="faTimes" class="w-5 h-5" />
                </button>
              </div>
            </div>
            <div class="mt-3 text-center">
              <p class="text-sm text-gray-500">
                {{ searchQuery ? `Mostrando ${hotelesDisponibles.length} resultado${hotelesDisponibles.length !== 1 ? 's' : ''} para "${searchQuery}"` : `${hotelesDisponibles.length} hotel${hotelesDisponibles.length !== 1 ? 'es' : ''} disponible${hotelesDisponibles.length !== 1 ? 's' : ''}` }}
              </p>
            </div>
          </div>
        </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="hotel in hotelesDisponibles"
              :key="hotel.id"
              class="bg-gradient-to-br from-white to-gray-50 hover:from-gray-50 hover:to-white border-2 border-gray-200 hover:border-blue-300 shadow-lg hover:shadow-xl transition-all duration-300 flex flex-col min-h-[400px] sm:min-h-[450px] transform hover:-translate-y-2 hover:scale-[1.02] overflow-hidden rounded-xl cursor-pointer"
              @click="navegarADetalle(hotel)"
              >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-200 rounded-t-xl overflow-hidden group border-b border-gray-200">
                  <img
                    :src="obtenerImagenActual(hotel)"
                    :alt="hotel.nombre"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    loading="lazy"
                  />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                  <!-- Indicador de múltiples imágenes -->
                  <div v-if="hotel.imagenes && hotel.imagenes.length > 1" class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs flex items-center gap-1">
                    <FontAwesomeIcon :icon="faImage" class="w-3 h-3" />
                    {{ hotel.imagenes.length }}
                  </div>

                  <!-- Categoría badge -->
                  <div class="absolute top-2 left-2 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">
                    {{ hotel.categoria_hotel?.nombre || 'Sin categoría' }}
                  </div>

                  <!-- Provincia badge -->
                  <div class="absolute top-2 right-2 bg-purple-600 text-white px-2 py-1 rounded-full text-xs font-semibold shadow-lg flex items-center gap-1">
                    <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3" />
                    {{ hotel.provincia?.nombre || 'Sin provincia' }}
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-10 sm:h-12 flex items-start px-4 pt-3 hover:bg-gradient-to-r hover:from-gray-50 hover:to-blue-50 transition-all duration-300 rounded-lg mx-2">
                  <span class="font-bold text-gray-800 leading-tight line-clamp-2 text-sm sm:text-base">{{ hotel.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <div class="flex-grow space-y-3">
                    <!-- Descripción -->
                    <p class="text-gray-600 text-sm line-clamp-2">
                      {{ hotel.descripcion }}
                    </p>

                    <!-- Información del hotel -->
                    <div class="space-y-2">
                      <div>
                        <p class="text-xs font-semibold text-gray-700 mb-1">Dirección:</p>
                        <p class="text-xs text-gray-600 flex items-start gap-1">
                          <FontAwesomeIcon :icon="faMapMarkerAlt" class="w-3 h-3 text-purple-500 mt-0.5 flex-shrink-0" />
                          {{ hotel.direccion }}
                        </p>
                      </div>

                      <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                        <div>
                          <p class="font-semibold text-gray-700 mb-1">Provincia:</p>
                          <p>{{ hotel.provincia?.nombre || 'No especificada' }}</p>
                        </div>
                        <div>
                          <p class="font-semibold text-gray-700 mb-1">Categoría:</p>
                          <p>{{ hotel.categoria_hotel?.nombre || 'Sin categoría' }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Mensaje de click para ver detalles -->
                    <div class="text-center py-2">
                      <p class="text-xs text-blue-600 font-medium bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                        <i class="pi pi-mouse-pointer mr-1"></i>
                        Dar click para ver detalles
                      </p>
                    </div>
                  </div>

                  <!-- Botones de acción -->
                  <div class="flex gap-2 mt-4">
                    <Button
                      label="Reservar"
                      @click.stop="abrirModalReserva(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-blue-600 !text-white hover:!bg-blue-700"
                      size="small"
                    />
                    <Button
                      label="WhatsApp"
                      @click.stop="contactarHotel(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-green-600 !text-white hover:!bg-green-700"
                      size="small"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Hoteles No Disponibles -->
        <div v-if="hotelesNoDisponibles.length > 0" class="mb-8">
          <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white text-center py-4 px-6 rounded-t-xl mb-6">
            <h2 class="text-xl md:text-2xl font-bold">😔 Hoteles Temporalmente No Disponibles</h2>
            <p class="text-gray-200 text-sm mt-1">{{ hotelesNoDisponibles.length }} hotel{{ hotelesNoDisponibles.length !== 1 ? 'es' : '' }} temporalmente no disponible{{ hotelesNoDisponibles.length !== 1 ? 's' : '' }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
            <Card
              v-for="hotel in hotelesNoDisponibles"
              :key="hotel.id"
              class="border-2 border-gray-300 bg-gray-50 opacity-75 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col min-h-[400px] sm:min-h-[450px] overflow-hidden rounded-xl"
              >
              <template #header>
                <div class="relative w-full h-36 sm:h-40 bg-gradient-to-br from-gray-200 via-gray-150 to-gray-300 rounded-t-xl overflow-hidden group cursor-pointer border-b border-gray-300"
                     @click="mostrarGaleria(hotel)">
                  <img
                    :src="obtenerImagenActual(hotel)"
                    :alt="hotel.nombre"
                    class="w-full h-full object-cover filter grayscale"
                    loading="lazy"
                  />

                  <!-- Badge de no disponible -->
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <span class="bg-red-600 text-white px-4 py-2 rounded-full text-sm font-bold">No Disponible</span>
                  </div>
                </div>
              </template>

              <template #title>
                <div class="h-10 sm:h-12 flex items-start px-4 pt-3">
                  <span class="font-bold text-gray-600 leading-tight line-clamp-2 text-sm sm:text-base">{{ hotel.nombre }}</span>
                </div>
              </template>

              <template #content>
                <div class="flex-1 flex flex-col px-4 pb-4 min-h-0">
                  <div class="flex-grow space-y-3">
                    <p class="text-gray-500 text-sm line-clamp-2">
                      {{ hotel.descripcion }}
                    </p>

                    <div class="space-y-2">
                      <div>
                        <p class="text-xs font-semibold text-gray-600 mb-1">Dirección:</p>
                        <p class="text-xs text-gray-500">{{ hotel.direccion }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="flex gap-2 mt-4">
                    <Button
                      label="Reservar"
                      @click="abrirModalReserva(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-blue-600 !text-white hover:!bg-blue-700"
                      size="small"
                    />
                    <Button
                      label="Contactar"
                      @click="contactarHotel(hotel)"
                      class="!border-none !px-3 !py-2 !text-sm font-semibold rounded transition-all flex-1 shadow-sm !bg-gray-600 !text-white hover:!bg-gray-700"
                      size="small"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Sección de Aerolíneas  -->
        <div class="w-full mb-8 mt-6">
          <div class="bg-gradient-to-br from-white via-purple-50 to-indigo-50 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl border-2 border-gray-200 hover:shadow-2xl transition-all duration-300">
            <!-- Header responsivo -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-center py-3 sm:py-4 md:py-6 rounded-lg sm:rounded-xl mb-4 sm:mb-6 md:mb-8">
              <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold flex items-center justify-center gap-2">
                <FontAwesomeIcon :icon="faPlane" />
                <span class="text-center">Aerolíneas Asociadas</span>
              </h3>
            </div>

            <!-- Mensaje informativo -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-5 mb-4 sm:mb-6">
              <div class="text-center">
                <h4 class="text-sm sm:text-base md:text-lg font-bold text-blue-800 mb-2">
                  ¿Necesitas información sobre vuelos?
                </h4>
                <p class="text-xs sm:text-sm text-blue-600 mb-3 sm:mb-4">
                  Trabajamos con las mejores aerolíneas para ofrecerte las mejores tarifas y conexiones
                </p>
              </div>
            </div>

            <!-- Grid de logos de aerolíneas -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 md:gap-6 mb-4 sm:mb-6">
              <!-- Avianca -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_avianca.png" alt="Avianca" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">AVIANCA</div>
                </div>
              </div>

              <!-- Copa Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-300 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_copaAirlines.png" alt="Copa Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">COPA</div>
                </div>
              </div>

               <!-- Aero Mexico -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_aeroMexico.png" alt="Aero Mexico" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">AERO MEXICO</div>
                </div>
              </div>

              <!-- JetBlue Airways -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-800 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_jetBlue.png" alt="JetBlue Airways" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">JETBLUE</div>
                </div>
              </div>

              <!-- Iberia -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-yellow-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_iberia.png" alt="Iberia" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">IBERIA</div>
                </div>
              </div>

              <!-- Volaris -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-green-400 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_volaris.png" alt="Volaris" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">VOLARIS</div>
                </div>
              </div>

              <!-- Frontier Airlines-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-green-800 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_frontier.png" alt="Frontier Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">FRONTIER</div>
                </div>
              </div>

              <!-- Air Canada-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_airCanada.png" alt="Air Canada" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">AIR CANADA</div>
                </div>
              </div>

              <!-- Arajet Airlines-->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-purple-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_arajet.png" alt="Arajeet Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">ARAJEET</div>
                </div>
              </div>

              <!-- American Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_americanAirlines.png" alt="American" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-700 text-white px-2 py-1 rounded text-xs font-bold">AMERICAN</div>
                </div>
              </div>

              <!-- United Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-blue-900 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_unitedAirlines.png" alt="United Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-blue-800 text-white px-2 py-1 rounded text-xs font-bold">UNITED</div>
                </div>
              </div>

              <!-- Delta Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-red-500 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_delta.png" alt="Delta Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">DELTA</div>
                </div>
              </div>

              <!-- Spirit Airlines -->
              <div class="bg-white p-3 sm:p-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 border border-gray-200 hover:border-gray-900 transform hover:-translate-y-1">
                <div class="w-full h-12 sm:h-16 flex items-center justify-center">
                  <img src="/images/logosAerolineas/logo_spirit.png" alt="Spirit Airlines" class="max-h-full max-w-full object-contain"
                       onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                  <div class="hidden bg-yellow-500 text-black px-2 py-1 rounded text-xs font-bold">SPIRIT</div>
                </div>
              </div>
            </div>
            <!-- Botón de contacto WhatsApp para aerolíneas -->
            <div class="text-center">
              <p class="text-xs sm:text-sm text-gray-600">
                Consulta tarifas especiales y disponibilidad de vuelos con nuestras aerolíneas asociadas,
                a través de nuestro canal de WhatsApp.
              </p>
              <button
                @click="abrirWhatsApp('aerolíneas')"
                type="button"
                class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 sm:px-6 md:px-8 py-2 mt-4 sm:mt-11 rounded-lg sm:rounded-xl font-semibold text-sm sm:text-base transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2 group cursor-pointer"
              >
                <span>¿Necesitas información?</span>
                <span class="px-2 py-2 rounded-full">
                  <FontAwesomeIcon :icon="faWhatsapp" class="w-5 h-5 text-white" />
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Info adicional profesional -->
        <div class="w-full">
          <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
          <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center py-6">
              <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
                ¿Por qué reservar con nosotros?
              </h2>
              <p class="text-base sm:text-lg text-blue-100 px-4">Tu estadía perfecta está a un mensaje de distancia</p>
            </div>

            <!-- Contenido -->
            <div class="p-2 md:p-8">
              <div class="grid grid-cols-3 md:grid-cols-3 gap-2 md:gap-8">
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                  <div class="text-3xl md:text-4xl mb-2">🔍</div>
                  <h3 class="font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent mb-2 text-lg">Hoteles Verificados</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Todos nuestros hoteles están verificados y seleccionados cuidadosamente</p>
                </div>
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                  <div class="text-3xl md:text-4xl mb-2">💬</div>
                  <h3 class="font-bold bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent mb-2 text-lg">Atención Personalizada</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Te asesoramos personalmente para encontrar el alojamiento perfecto</p>
                </div>
                <div class="text-center p-2 md:p-6 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200">
                  <div class="text-3xl md:text-4xl mb-2">⚡</div>
                  <h3 class="font-bold bg-gradient-to-r from-red-600 to-red-700 bg-clip-text text-transparent mb-2 text-lg">Respuesta Rápida</h3>
                  <p class="text-gray-600 text-sm leading-relaxed">Respuesta inmediata por WhatsApp y email para tus consultas</p>
                </div>
              </div>
            </div>
        </div>
      </div>
    </div>

    <!-- Diálogo profesional para mostrar galería de imágenes -->
      <Dialog
        v-model:visible="showImageDialog"
        modal
        :closable="false"
        class="max-w-3xl w-full md:w-full mx-4 z-[99999] mt-16 sm:mt-24 md:mt-24 lg:mt-24 xl:mt-24 2xl:mt-24"
        :draggable="false"
        :pt="{
          root: { class: 'z-[99999]' },
          mask: { class: 'z-[99999]' },
          content: { class: 'p-0 overflow-hidden rounded-lg z-[99999]' },
          header: { class: 'p-0 border-none z-[99999]' }
        }"
      >
        <template #header>
          <div class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 rounded-lg flex items-center justify-between">
            <h3 class="text-md md:text-xl font-bold bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">Imágenes del hotel</h3>
            <div class="flex items-center gap-3">
              <button
                v-if="selectedHotelImages.length > 1"
                @click="toggleGalleryAutoPlay"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-2 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all border border-white/20 shadow-lg transform hover:scale-110"
                :title="isGalleryAutoPlaying ? 'Pausar carrusel automático' : 'Reanudar carrusel automático'"
              >
                <FontAwesomeIcon :icon="isGalleryAutoPlaying ? faPause : faPlay" class="w-4 h-4" />
              </button>
              <!-- Botón de cerrar personalizado y visible -->
              <button
                @click="showImageDialog = false; detenerCarruselGaleria()"
                class="bg-gradient-to-r from-red-600/80 to-red-700/80 backdrop-blur-sm text-white p-2 rounded-full hover:from-red-700/90 hover:to-red-800/90 transition-all border border-white/20 shadow-lg transform hover:scale-110"
                title="Cerrar galería"
              >
                <FontAwesomeIcon :icon="faXmark" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </template>

        <div class="bg-gradient-to-br from-gray-50 to-white p-0">
          <!-- Imagen principal -->
          <div class="relative h-72 bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl overflow-hidden mb-6 flex items-center justify-center shadow-lg border-2 border-gray-200">
            <img
              :src="selectedHotelImages[currentImageIndex]"
              alt="Hotel imagen"
              class="max-w-full max-h-full object-contain rounded-lg"
            />

            <!-- Botones de navegación profesionales -->
            <div v-if="selectedHotelImages.length > 1" class="absolute inset-0 flex items-center justify-between p-0 md:p-12">
              <button
                @click="imagenAnterior"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronLeft" class="w-5 h-5" />
              </button>
              <button
                @click="siguienteImagen"
                class="bg-gradient-to-r from-black/60 to-gray-800/60 backdrop-blur-sm text-white p-3 rounded-full hover:from-black/80 hover:to-gray-800/80 transition-all z-10 border border-white/20 shadow-lg transform hover:scale-110"
              >
                <FontAwesomeIcon :icon="faChevronRight" class="w-5 h-5" />
              </button>
            </div>

            <!-- Contador de imágenes profesional -->
            <div v-if="selectedHotelImages.length > 1"
                class="absolute bottom-4 right-4 bg-gradient-to-r from-black/80 to-gray-800/80 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium z-10 border border-white/20 shadow-lg">
              {{ currentImageIndex + 1 }} / {{ selectedHotelImages.length }}
            </div>

            <!-- Indicador de autoplay profesional -->
            <div v-if="selectedHotelImages.length > 1 && isGalleryAutoPlaying"
                class="absolute top-4 right-4 bg-gradient-to-r from-blue-500/90 to-blue-600/90 backdrop-blur-sm text-white px-3 py-2 rounded-full text-xs font-medium z-10 flex items-center gap-2 border border-blue-300/30 shadow-lg">
              <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
              <span>Auto</span>
            </div>
          </div>

          <!-- Miniaturas profesionales -->
          <div v-if="selectedHotelImages.length > 1" class="flex gap-3 overflow-x-auto pb-2 px-2">
            <button
              v-for="(imagen, index) in selectedHotelImages"
              :key="index"
              @click="irAImagen(index)"
              class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border-3 transition-all bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center shadow-md hover:shadow-lg transform hover:scale-110"
              :class="currentImageIndex === index ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300 hover:border-gray-400'"
            >
              <img
                :src="imagen"
                :alt="`Hotel imagen ${index + 1}`"
                class="max-w-full max-h-full object-contain rounded"
              />
            </button>
          </div>
        </div>
      </Dialog>

    <!-- Modal de Reserva de Hotel -->
    <Dialog
      v-model:visible="showReservaModal"
      modal
      header="Reservar Hotel"
      :closable="false"
      class="max-w-2xl w-full mx-4"
      :draggable="false"
    >
      <div v-if="hotelSeleccionado" class="space-y-6">
        <!-- Información del hotel seleccionado -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 rounded-lg">
          <h3 class="font-bold text-lg text-gray-800 mb-2">{{ hotelSeleccionado.nombre }}</h3>
          <p class="text-sm text-gray-600 mb-1">{{ hotelSeleccionado.direccion }}</p>
          <p class="text-sm text-gray-500">{{ hotelSeleccionado.provincia?.nombre }}, {{ hotelSeleccionado.pais?.nombre }}</p>
        </div>

        <!-- Mensaje informativo para datos precargados -->
        <div v-if="tieneClienteExistente" class="flex items-center justify-between mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center text-green-700">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">Datos personales precargados</span>
          </div>
          <button
            @click="navegarAlPerfil"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
            title="Editar mis datos personales en mi perfil"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Editar mis datos
          </button>
        </div>

        <!-- Formulario de reserva -->
        <form @submit.prevent="crearReservaHotel" class="space-y-4">
          <!-- Fechas de estadía -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Entrada</label>
              <DatePicker
                v-model="reservaForm.fecha_entrada"
                :minDate="getFechaMinima()"
                date-format="dd/mm/yy"
                placeholder="Seleccionar fecha de entrada"
                showIcon
                class="w-full"
                inputClass="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Salida</label>
              <DatePicker
                v-model="reservaForm.fecha_salida"
                :minDate="getFechaMinimaComputada"
                date-format="dd/mm/yy"
                placeholder="Seleccionar fecha de salida"
                showIcon
                class="w-full"
                inputClass="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Cantidad de personas y habitaciones -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de Personas</label>
              <input
                v-model.number="reservaForm.cantidad_personas"
                type="number"
                min="1"
                max="10"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de Habitaciones</label>
              <input
                v-model.number="reservaForm.cantidad_habitaciones"
                type="number"
                min="1"
                max="5"
                required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- Información del cliente -->
          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-semibold text-gray-800">Información Personal</h4>
              <div v-if="tieneClienteExistente" class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span>Datos precargados</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Número de Identificación</label>
                <input
                  :value="reservaForm.cliente_data.numero_identificacion"
                  @input="manejarEntradaDocumento"
                  type="text"
                  required
                  :maxlength="reservaForm.cliente_data.tipo_documento === 'DUI' ? 10 : 9"
                  :disabled="tieneClienteExistente"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
                  :placeholder="reservaForm.cliente_data.tipo_documento === 'DUI' ? 'Ingrese 9 dígitos (ej: 123456789)' : 'Ingrese su PASAPORTE (ej: A1B2C3D4)'"
                />
                <!-- Mensaje de validación de formato en tiempo real -->
                <small
                  v-if="formatoValidation.mensaje && !tieneClienteExistente"
                  :class="[
                    'block mt-1',
                    formatoValidation.isValid ? 'text-green-600' : 'text-red-500'
                  ]"
                >
                  {{ formatoValidation.mensaje }}
                </small>

                <!-- Mensaje de validación de duplicados -->
                <small
                  v-if="documentoValidation.mensaje && !tieneClienteExistente"
                  :class="[
                    'block mt-1',
                    documentoValidation.isValid ? 'text-green-600' : 'text-red-500'
                  ]"
                >
                  {{ documentoValidation.mensaje }}
                </small>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento</label>
                <select
                  v-model="reservaForm.cliente_data.tipo_documento"
                  required
                  :disabled="tieneClienteExistente"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
                >
                  <option value="" disabled>Seleccione un tipo</option>
                  <option value="DUI">DUI</option>
                  <option value="PASAPORTE">PASAPORTE</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
                <DatePicker
                  v-model="reservaForm.cliente_data.fecha_nacimiento"
                  :maxDate="getFechaMaximaNacimiento()"
                  date-format="dd/mm/yy"
                  placeholder="Seleccionar fecha de nacimiento (debe ser mayor de 18 años)"
                  showIcon
                  yearNavigator
                  yearRange="1920:2006"
                  :disabled="tieneClienteExistente"
                  class="w-full"
                  :inputClass="`w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 ${tieneClienteExistente ? 'bg-gray-100 cursor-not-allowed' : ''}`"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                <select
                  v-model="reservaForm.cliente_data.genero"
                  required
                  :disabled="tieneClienteExistente"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
                >
                  <option value="">Seleccione</option>
                  <option value="MASCULINO">Masculino</option>
                  <option value="FEMENINO">Femenino</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                <VueTelInput
                  v-model="reservaForm.cliente_data.telefono"
                  defaultCountry="SV"
                  :preferredCountries="['SV', 'GT', 'HN', 'CR', 'NI', 'PA', 'BZ']"
                  :validCharactersOnly="true"
                  :disabled="tieneClienteExistente"
                  :dropdownOptions="{
                    showDialCodeInSelection: true,
                    showFlags: true,
                    showSearchBox: true,
                    showDialCodeInList: true
                  }"
                  :inputOptions="{
                    placeholder: 'Número de teléfono'
                  }"
                  mode="international"
                  class="w-full border border-gray-300 rounded-lg"
                  :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
                  @validate="onValidate"
                />
                <!-- Mensaje de validación -->
                <p
                  v-if="telefonoValidation.mensaje"
                  :class="[
                    'text-xs mt-1 flex items-center',
                    telefonoValidation.isValid ? 'text-green-600' : 'text-red-600'
                  ]"
                >
                  <span class="mr-1">
                    {{ telefonoValidation.isValid ? '✓' : '⚠️' }}
                  </span>
                  {{ telefonoValidation.mensaje }}
                </p>
              </div>
              <div class="sm:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                <input
                  v-model="reservaForm.cliente_data.direccion"
                  type="text"
                  required
                  maxlength="200"
                  :disabled="tieneClienteExistente"
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="{ 'bg-gray-100 cursor-not-allowed': tieneClienteExistente }"
                  placeholder="Dirección completa"
                />
              </div>
            </div>
          </div>
        </form>
      </div>

      <template #footer>
        <div class="flex justify-center gap-4 w-full mt-6">
          <button
            class="bg-red-500 hover:bg-red-700 text-white border-none px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            @click="crearReservaHotel"
            :disabled="procesandoReserva"
          >
            <FontAwesomeIcon
              :icon="procesandoReserva ? faSpinner : faCheck"
              :class="[
                'h-5 text-white',
                { 'animate-spin': procesandoReserva }
              ]"
            />
            <span v-if="!procesandoReserva">Crear Reserva</span>
            <span v-else>Procesando...</span>
          </button>
          <button
            type="button"
            class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-all duration-200 ease-in-out flex items-center gap-2"
            @click="cerrarModalReserva"
            :disabled="procesandoReserva"
          >
            <FontAwesomeIcon :icon="faXmark" class="h-5" />
            Cancelar
          </button>
        </div>
      </template>
    </Dialog>

    <!-- Modal de Autenticación Requerida -->
    <ModalAuthRequerido
      v-model:visible="showAuthModal"
      :tour-info="hotelParaReserva ? { id: hotelParaReserva.id, nombre: hotelParaReserva.nombre, tipo: 'hotel' } : null"
    />
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

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
