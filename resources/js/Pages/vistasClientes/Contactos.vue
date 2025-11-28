<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { usePage } from '@inertiajs/vue3'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faMapLocationDot, faHotel, faQuestionCircle, faCreditCard, faBullseye, faRocket, faArrowRight,
    faEnvelope, faLocation, faSearch, faPhone } from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'

// Inicializar toast
const toast = useToast()

// Obtener datos de la página
const page = usePage()
const config = computed(() => page.props.config || {})
const adminEmail = computed(() => config.value.admin_email || 'vasirtours19@gmail.com')
const busquedaFAQ = ref('')

// Estado para controlar qué FAQ está abierta
const faqAbierta = ref(null)

// Preguntas frecuentes organizadas por categorías
const preguntasFrecuentes = ref([
  {
  categoria: { icono: faHotel, texto: 'Servicios Generales' },
    preguntas: [
      {
        id: 1,
        pregunta: '¿Qué tipo de experiencias turísticas ofrece VASIR?',
        respuesta: 'Ofrecemos tours culturales, de naturaleza, gastronómicos y de aventura en El Salvador, con un enfoque sostenible y auténtico. Nuestros itinerarios buscan que vivás el país desde adentro, conectando con su gente, sus tradiciones y su sabor.'
      },
      {
        id: 2,
        pregunta: '¿VASIR solo ofrece tours o también transporte privado?',
        respuesta: 'Además de nuestros tours completos, también brindamos servicio de transporte privado con chofer para grupos o viajeros individuales, tanto dentro como fuera de El Salvador. Este servicio se adapta a tu itinerario y necesidades.'
      },
      {
        id: 9,
        pregunta: '¿Qué otros servicios ofrecen?',
        respuesta: 'Además de tours y transporte, ofrecemos venta de boletos aéreos, reservas de hospedaje, trámites de visa americana, organización de eventos corporativos, viajes educativos y experiencias para grupos especiales.'
      }
    ]
  },
  {
  categoria: { icono: faMapLocationDot, texto: 'Ubicación y Logística' },
    preguntas: [
      {
        id: 3,
        pregunta: '¿Dónde están ubicados y desde dónde salen los tours?',
        respuesta: 'Nuestra base está en Chalatenango, El Salvador, pero operamos salidas desde San Salvador y otros puntos del país según la ruta y el grupo. También podemos coordinar recogidas en aeropuertos y hoteles.'
      },
      {
        id: 7,
        pregunta: '¿Cómo puedo reservar?',
        respuesta: 'Puedes reservar directamente desde nuestra página web, por WhatsApp o redes sociales.'
      },
      {
        id: 8,
        pregunta: '¿Atienden a viajeros internacionales?',
        respuesta: 'Sí. Nuestros tours están diseñados tanto para visitantes locales como internacionales, y los ofrecemos en español e inglés. Si necesitas otro idioma, podemos coordinarlo.'
      }
    ]
  },
  {
    categoria: { icono: faCreditCard, texto: 'Pagos y Precios' },
    preguntas: [
      {
        id: 4,
        pregunta: '¿Qué métodos de pago aceptan?',
        respuesta: 'Aceptamos pagos en efectivo, transferencia bancaria, depósito en cuenta, pagos con tarjeta (Visa, Mastercard, American Express, Diners Club, Discover Network) y plataformas electrónicas como PayPal. Para clientes internacionales recomendamos PayPal o tarjeta para mayor facilidad.'
      },
      {
        id: 11,
        pregunta: '¿Qué precio tiene el trámite de Visa Americana?',
        respuesta: '✅ Asesoría $15.00\n✅ Paquete básico (llenado del formulario y programación de cita) $55.00\n✅ Paquete full (llenado de formulario, programación de cita, pago en Banco, asesoría personalizada y seguimiento) $80.00\n\nAparte de esto, lo que se cancela a la Embajada es de $185.00'
      }
    ]
  },
  {
    categoria: { icono: faBullseye, texto: 'Personalización y Destinos' },
    preguntas: [
      {
        id: 5,
        pregunta: '¿Puedo personalizar mi viaje?',
        respuesta: 'Sí. Diseñamos experiencias a tu medida: tours de un día, paquetes de varios días, viajes en pareja, familiares o corporativos, con actividades adaptadas a tus intereses.'
      },
      {
        id: 6,
        pregunta: '¿Ofrecen viajes internacionales o conexiones con otros países?',
        respuesta: 'Sí. Además de destinos en El Salvador, podemos coordinar experiencias hacia cualquier parte del mundo, incluyendo transporte, guías y actividades en el destino.'
      },
      {
        id: 10,
        pregunta: '¿Puedo reservar para Hoteles Decameron?',
        respuesta: 'Claro que sí, somos una Agencia autorizada por Hoteles Decameron.'
      }
    ]
  }
])

// Funciones para generar mensajes personalizados de WhatsApp
function generarMensajeWhatsApp(tipo = 'general') {
  const mensajes = {
    general: 'Hola VASIR, me gustaría recibir información sobre sus servicios turísticos. ¿Podrían ayudarme?',
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

  // Verificar si el teléfono del administrador está disponible
  if (config.value.admin_phone && !config.value.admin_phone.includes('no disponible')) {
    const numeroWhatsApp = config.value.admin_phone.replace(/\D/g, '')
    const mensaje = generarMensajeWhatsApp(tipo)
    const url = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`
    window.open(url, '_blank')
  } else {
    toast.add({
      severity: 'info',
      summary: 'WhatsApp no disponible',
      detail: 'El contacto de WhatsApp no está disponible en este momento. Puede contactarnos por nuestras redes sociales.',
      life: 5000
    })
  }
}

// Función para abrir email con mensaje personalizado
function abrirEmail() {
  // Verificar si el usuario está autenticado y su rol
  const user = page.props.auth?.user

  // Verificar si el usuario tiene roles de Administrador o Empleado
  if (user && user.roles && user.roles.length > 0) {
    const userRoles = user.roles.map(role => typeof role === 'string' ? role : role.name || role.rol)

    if (userRoles.includes('Administrador') || userRoles.includes('Empleado')) {
      toast.add({
        severity: 'warn',
        summary: 'Acceso Restringido',
        detail: 'Esta acción está disponible solo para clientes. Los administradores y empleados no pueden realizar consultas por email como clientes.',
        life: 5000
      })
      return
    }
  }

  // Verificar si el email está disponible
  if (adminEmail.value && !adminEmail.value.includes('no disponible')) {
    // Detectar si es un dispositivo móvil
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)

    const subject = 'Consulta sobre servicios de VASIR'
    const body = `Hola,

Me interesa conocer más información sobre sus servicios de:
- Tours nacionales e internacionales
- Boletos aéreos
- Reservaciones de hoteles
- Artículos de viaje

Espero puedan ayudarme con más detalles.

Gracias.`

    if (isMobile) {
      // En móviles, usar mailto para abrir la app de correo predeterminada
      const mailtoUrl = `mailto:${adminEmail.value}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
      window.location.href = mailtoUrl
    } else {
      // En escritorio, abrir Gmail web
      const gmailUrl = `https://mail.google.com/mail/?view=cm&fs=1&to=${adminEmail.value}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
      window.open(gmailUrl, '_blank')
    }
  } else {
    toast.add({
      severity: 'warn',
      summary: 'Email no disponible',
      detail: 'El email del administrador no está disponible. Por favor, contáctenos por WhatsApp o redes sociales.',
      life: 5000
    })
  }
}

function toggleFAQ(id) {
  faqAbierta.value = faqAbierta.value === id ? null : id
}

// Computed para filtrar FAQs
const faqsFiltradas = computed(() => {
  if (!busquedaFAQ.value) return preguntasFrecuentes.value

  const termino = busquedaFAQ.value.toLowerCase()
  return preguntasFrecuentes.value.map(categoria => ({
    ...categoria,
    preguntas: categoria.preguntas.filter(p =>
      p.pregunta.toLowerCase().includes(termino) ||
      p.respuesta.toLowerCase().includes(termino)
    )
  })).filter(categoria => categoria.preguntas.length > 0)
})
</script>

<template>
  <Catalogo>
    <Toast />
    <!-- Header Professional - Ancho completo de la pantalla -->
    <div class="w-full bg-gradient-to-r from-blue-600 via-blue-600 to-red-500 text-white text-center py-4 sm:py-6 mt-20 md:mt-24 lg:mt-28 xl:mt-28 mb-6 sm:mb-8 shadow-xl">
      <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
        <FontAwesomeIcon :icon="faPhone" class="text-yellow-300 mr-2 vibrate-animation" />
        Contáctanos</h1>
      <p class="text-base sm:text-lg text-red-100 px-4">¿Tienes dudas? ¡Estamos aquí para ayudarte!</p>
    </div>

    <!-- Contenido principal con padding responsivo -->
    <div class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-red-50/30 min-h-screen px-3 sm:px-4 md:px-6 lg:px-8 pb-4 sm:pb-6 md:pb-8">
      <div class="max-w-7xl mx-auto">

        <!-- Grid responsivo: 1 columna en móvil, 2 columnas en desktop -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 md:gap-8">
          <!-- Columna izquierda: FAQs -->
          <div class="space-y-4 sm:space-y-6 md:space-y-8">
            <!-- Preguntas Frecuentes -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <!-- Header responsivo -->
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-3 sm:py-4 md:py-6 rounded-lg sm:rounded-xl mb-4 sm:mb-6 md:mb-8">
                <h2 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold flex items-center justify-center gap-2 sm:gap-3">
                  <FontAwesomeIcon :icon="faQuestionCircle" />
                  <span class="text-center">Preguntas Frecuentes</span>
                </h2>
              </div>

              <!-- Buscador de FAQs responsivo -->
              <div class="mb-4 sm:mb-6 md:mb-8">
                <div class="relative">
                  <InputText
                    v-model="busquedaFAQ"
                    placeholder="Buscar preguntas..."
                    class="w-full border-2 border-gray-300 focus:border-red-500 focus:ring-2 sm:focus:ring-4 focus:ring-red-200 rounded-lg sm:rounded-xl px-3 sm:px-4 py-2 sm:py-3 md:py-4 text-sm sm:text-base shadow-md transition-all duration-300"
                  />
                  <div class="absolute right-2 sm:right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <FontAwesomeIcon :icon="faSearch" class="text-base sm:text-lg"/>
                  </div>
                </div>
              </div>

              <!-- Lista de FAQs por categoría - completamente responsiva -->
              <div class="space-y-3 sm:space-y-4 md:space-y-6">
                <div v-for="(categoria, index) in faqsFiltradas" :key="categoria.categoria" class="space-y-2 sm:space-y-3 md:space-y-4">
                  <!-- Título de categoría responsivo -->
                  <h3 class="text-base sm:text-lg md:text-xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent border-b-2 border-red-200 pb-1 sm:pb-2 md:pb-3 flex items-center gap-2">
                    <template v-if="typeof categoria.categoria === 'object' && categoria.categoria.icono">
                      <FontAwesomeIcon :icon="categoria.categoria.icono" :class="`text-blue-700 bounce-animation-${(index % 4) + 1}`" />
                      <span>{{ categoria.categoria.texto }}</span>
                    </template>
                    <template v-else>
                      {{ categoria.categoria }}
                    </template>
                  </h3>

                  <!-- Preguntas individuales -->
                  <div v-for="faq in categoria.preguntas" :key="faq.id" class="bg-white rounded-lg sm:rounded-xl overflow-hidden shadow-md sm:shadow-lg border border-gray-200 hover:border-red-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <!-- Botón de pregunta responsivo -->
                    <button
                      @click="toggleFAQ(faq.id)"
                      class="w-full text-left p-3 sm:p-4 md:p-5 bg-gradient-to-r from-gray-50 to-white hover:from-red-50 hover:to-blue-50 transition-all duration-300 flex justify-between items-start sm:items-center gap-3"
                    >
                      <span class="font-semibold text-gray-800 text-xs sm:text-sm md:text-base leading-relaxed flex-1">{{ faq.pregunta }}</span>
                      <div class="w-6 h-6 sm:w-8 sm:h-8 bg-gradient-to-br from-red-500 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                        <span class="text-white font-bold text-sm sm:text-lg">
                          {{ faqAbierta === faq.id ? '−' : '+' }}
                        </span>
                      </div>
                    </button>

                    <!-- Respuesta expandible -->
                    <div v-if="faqAbierta === faq.id" class="p-3 sm:p-4 md:p-5 bg-gradient-to-br from-white to-gray-50 border-t-2 border-red-200">
                      <p class="text-gray-700 leading-relaxed whitespace-pre-line text-xs sm:text-sm md:text-base">{{ faq.respuesta }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mensaje cuando no hay resultados - responsivo -->
              <div v-if="faqsFiltradas.length === 0" class="text-center py-6 sm:py-8 md:py-12">
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg">
                  <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full mx-auto mb-3 sm:mb-4 flex items-center justify-center shadow-lg">
                    <FontAwesomeIcon :icon="faSearch" class="text-white text-lg sm:text-2xl"/>
                  </div>
                  <h3 class="text-base sm:text-lg md:text-xl font-semibold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent mb-2">No se encontraron resultados</h3>
                  <p class="text-gray-600 mb-3 sm:mb-4 text-xs sm:text-sm md:text-base">No se encontraron preguntas que coincidan con tu búsqueda.</p>
                  <button
                    @click="busquedaFAQ = ''"
                    class="bg-gradient-to-r from-red-600 to-blue-600 hover:from-red-700 hover:to-blue-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1 text-xs sm:text-sm md:text-base"
                  >
                    Ver todas las preguntas
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Columna derecha: Métodos de contacto -->
          <div class="space-y-4 sm:space-y-6 md:space-y-8">

            <!-- Métodos de contacto directo - completamente responsivo -->
            <div class="bg-gradient-to-br from-white via-red-50 to-blue-50 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <!-- Header responsivo -->
              <div class="bg-gradient-to-r from-blue-600 to-red-600 text-white text-center py-3 sm:py-4 md:py-6 rounded-lg sm:rounded-xl mb-4 sm:mb-6 md:mb-8">
                <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold flex items-center justify-center gap-2">
                  <FontAwesomeIcon :icon="faRocket"/>
                  <span class="text-center">Contacto Directo</span>
                </h3>
              </div>

              <!-- Lista de métodos de contacto - responsiva -->
              <div class="space-y-3 sm:space-y-4 md:space-y-5">
                <!-- WhatsApp -->
                <button
                  @click="abrirWhatsApp('general')"
                  class="w-full flex items-center p-3 sm:p-4 md:p-5 bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-200 rounded-lg sm:rounded-xl hover:from-green-100 hover:to-green-200 hover:border-green-300 transition-all duration-300 shadow-md sm:shadow-lg hover:shadow-xl transform hover:-translate-y-1 group cursor-pointer pulse-scale-whatsapp"
                >
                  <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mr-3 sm:mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <FontAwesomeIcon :icon="faWhatsapp" class="text-white text-base sm:text-2xl"/>

                  </div>
                  <div class="flex-1 text-left">
                    <p class="font-bold text-green-700 text-sm sm:text-base">WhatsApp</p>
                    <p class="text-xs sm:text-sm text-green-600">Clic para chatear</p>
                  </div>
                  <div class="text-green-600 opacity-75 group-hover:opacity-100 transition-opacity">
                    <FontAwesomeIcon :icon="faArrowRight" class="text-green-600"/>
                  </div>
                </button>

                <!-- Email -->
                <button
                  @click="abrirEmail()"
                  class="w-full flex items-center p-3 sm:p-4 md:p-5 bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-200 rounded-lg sm:rounded-xl hover:from-blue-100 hover:to-blue-200 hover:border-blue-300 transition-all duration-300 shadow-md sm:shadow-lg hover:shadow-xl transform hover:-translate-y-1 group cursor-pointer pulse-scale-email"
                >
                  <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-3 sm:mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <FontAwesomeIcon :icon="faEnvelope" class="text-white text-base sm:text-xl"/>
                  </div>
                  <div class="flex-1 text-left">
                    <p class="font-bold text-blue-700 text-sm sm:text-base">Email</p>
                    <p class="text-xs sm:text-sm text-blue-600 truncate">{{ adminEmail }}</p>
                  </div>
                </button>
              </div>
            </div>
             <div class="my-8 sm:my-10 md:my-12"></div>
            <!-- Mapa - completamente responsivo -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-lg sm:rounded-xl shadow-lg sm:shadow-xl border border-gray-200 overflow-hidden hover:shadow-2xl transition-all duration-300">
              <!-- Header del mapa -->
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white p-3 sm:p-4 md:p-5">
                <h3 class="text-base sm:text-lg md:text-xl font-bold flex items-center justify-center gap-2">
                  <span class="text-lg sm:text-xl md:text-2xl">
                    <FontAwesomeIcon :icon="faLocation" class="text-white"/>
                  </span>
                  <span class="text-center">Nuestra Ubicación</span>
                </h3>
              </div>
              <!-- Iframe del mapa responsivo -->
              <div class="relative">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d241.91319397188843!2d-88.9363812!3d14.0410515!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f636570efc5e09d%3A0xe884d67df04d7ff5!2sVASIR!5e0!3m2!1ses-419!2ssv!4v1749418509387!5m2!1ses-419!2ssv"
                  width="100%"
                  height="861"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Ubicación VASIR"
                  class="hover:opacity-90 transition-opacity duration-300 h-[661px] sm:h-[711px] md:h-[861px] lg:h-[961px] xl:h-[1011px]"
                >
                </iframe>
                <!-- Etiqueta flotante responsiva -->
                <div class="absolute bottom-2 sm:bottom-4 right-2 sm:right-4 bg-white/90 backdrop-blur-sm px-2 sm:px-3 py-1 sm:py-2 rounded-md sm:rounded-lg shadow-lg border border-gray-200">
                  <p class="text-xs sm:text-sm font-medium text-gray-800">
                    <FontAwesomeIcon :icon="faLocation" class="text-red-600 mr-1"/>
                    Chalatenango, El Salvador</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Espacio entre secciones -->
      </div>
    </div>
  </Catalogo>
</template>

<style scoped>
@keyframes pulse-scale {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.pulse-scale-whatsapp {
  animation: pulse-scale 2.5s ease-in-out infinite;
}

.pulse-scale-email {
  animation: pulse-scale 2.5s ease-in-out infinite;
  animation-delay: 1.25s;
}

@keyframes bounce-icon {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-8px);
  }
  60% {
    transform: translateY(-4px);
  }
}

.bounce-animation-1 {
  animation: bounce-icon 1.8s ease-in-out infinite;
}

.bounce-animation-2 {
  animation: bounce-icon 1.8s ease-in-out infinite;
  animation-delay: 0.3s;
}

.bounce-animation-3 {
  animation: bounce-icon 1.8s ease-in-out infinite;
  animation-delay: 0.6s;
}

.bounce-animation-4 {
  animation: bounce-icon 1.8s ease-in-out infinite;
  animation-delay: 0.9s;
}

@keyframes vibrate {
  0%, 70%, 100% {
    transform: translateX(0);
  }
  72%, 76%, 80% {
    transform: translateX(-4px);
  }
  74%, 78%, 82% {
    transform: translateX(4px);
  }
}

.vibrate-animation {
  animation: vibrate 2.5s ease-in-out infinite;
}
</style>
