<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { usePage } from '@inertiajs/vue3'

// Inicializar toast
const toast = useToast()

// Obtener datos de la página
const page = usePage()
const config = computed(() => page.props.config || {})
const adminEmail = computed(() => config.value.mail_from_address || 'vasirtours19@gmail.com')

const nombre = ref('')
const email = ref('')
const mensaje = ref('')
const busquedaFAQ = ref('')

// Estado para controlar qué FAQ está abierta
const faqAbierta = ref(null)

// Preguntas frecuentes organizadas por categorías
const preguntasFrecuentes = ref([
  {
    categoria: '🏢 Servicios Generales',
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
    categoria: '📍 Ubicación y Logística',
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
    categoria: '💳 Pagos y Precios',
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
    categoria: '🎯 Personalización y Destinos',
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

// Información de contacto (computed para reactividad)
const contactoInfo = computed(() => [
  {
    icono: '📍',
    titulo: 'Dirección',
    contenido: '2a Calle Oriente casa #12, Chalatenango, El Salvador',
    enlace: null
  },
  {
    icono: '⏰',
    titulo: 'Horarios',
    contenido: 'Lunes a Viernes: 8:00 AM - 4:00 PM\nSábados: 8:00 AM - 12:00 PM',
    enlace: null
  },
  {
    icono: '📱',
    titulo: 'WhatsApp',
    contenido: '+503 1234-5678',
    enlace: 'https://wa.me/50312345678'
  },
  {
    icono: '📧',
    titulo: 'Email',
    contenido: adminEmail.value,
    enlace: `mailto:${adminEmail.value}`
  }
])

// Funciones para generar mensajes personalizados de WhatsApp
function generarMensajeWhatsApp(tipo = 'general') {
  const mensajes = {
    general: 'Hola VASIR, me gustaría recibir información sobre sus servicios turísticos. ¿Podrían ayudarme?',
    aerolíneas: 'Hola VASIR, necesito información sobre vuelos y tarifas de aerolíneas. ¿Podrían ayudarme con cotizaciones?',

  }
  return encodeURIComponent(mensajes[tipo] || mensajes.general)
}

// Función para abrir WhatsApp con mensaje personalizado
function abrirWhatsApp(tipo = 'general') {
  const numeroWhatsApp = '50379858777'
  const mensaje = generarMensajeWhatsApp(tipo)
  const url = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`
  window.open(url, '_blank')
}

// Funciones existentes
function enviarFormulario(e) {
  e.preventDefault()
  toast.add({
    severity: 'success',
    summary: 'Mensaje enviado',
    detail: 'Mensaje enviado correctamente. Te responderemos pronto.',
    life: 5000
  })
  nombre.value = ''
  email.value = ''
  mensaje.value = ''
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
    <div class="w-full bg-gradient-to-r from-red-600 via-red-500 to-blue-600 text-white text-center py-6 sm:py-8 md:py-10 mt-20 sm:mt-20 md:mt-28 lg:mt-32 xl:mt-32 mb-6 sm:mb-8">
      <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">📞 Contáctanos</h1>
      <p class="text-base sm:text-lg text-red-100 mb-4 px-4">¿Tienes dudas? ¡Estamos aquí para ayudarte!</p>
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
                <h2 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-3">
                  <span class="text-xl sm:text-2xl md:text-3xl">❓</span>
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
                    <span class="text-base sm:text-lg">🔍</span>
                  </div>
                </div>
              </div>

              <!-- Lista de FAQs por categoría - completamente responsiva -->
              <div class="space-y-3 sm:space-y-4 md:space-y-6">
                <div v-for="categoria in faqsFiltradas" :key="categoria.categoria" class="space-y-2 sm:space-y-3 md:space-y-4">
                  <!-- Título de categoría responsivo -->
                  <h3 class="text-base sm:text-lg md:text-xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent border-b-2 border-red-200 pb-1 sm:pb-2 md:pb-3">
                    {{ categoria.categoria }}
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
                    <span class="text-white text-lg sm:text-2xl">🔍</span>
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
                <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold flex flex-col sm:flex-row items-center justify-center gap-2">
                  <span class="text-lg sm:text-xl md:text-2xl">🚀</span>
                  <span class="text-center">Contacto Directo</span>
                </h3>
              </div>

              <!-- Lista de métodos de contacto - responsiva -->
              <div class="space-y-3 sm:space-y-4 md:space-y-5">
                <!-- WhatsApp -->
                <button
                  @click="abrirWhatsApp('general')"
                  class="w-full flex items-center p-3 sm:p-4 md:p-5 bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-200 rounded-lg sm:rounded-xl hover:from-green-100 hover:to-green-200 hover:border-green-300 transition-all duration-300 shadow-md sm:shadow-lg hover:shadow-xl transform hover:-translate-y-1 group cursor-pointer"
                >
                  <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mr-3 sm:mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-base sm:text-xl">💬</span>
                  </div>
                  <div class="flex-1 text-left">
                    <p class="font-bold text-green-700 text-sm sm:text-base">WhatsApp</p>
                    <p class="text-xs sm:text-sm text-green-600">Respuesta inmediata</p>
                  </div>
                  <div class="text-green-600 opacity-75 group-hover:opacity-100 transition-opacity">
                    <span class="text-lg">→</span>
                  </div>
                </button>

                <!-- Email -->
                <a
                  :href="`mailto:${adminEmail}`"
                  class="flex items-center p-3 sm:p-4 md:p-5 bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-200 rounded-lg sm:rounded-xl hover:from-blue-100 hover:to-blue-200 hover:border-blue-300 transition-all duration-300 shadow-md sm:shadow-lg hover:shadow-xl transform hover:-translate-y-1 group"
                >
                  <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-3 sm:mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <span class="text-white text-base sm:text-xl">📧</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-blue-700 text-sm sm:text-base">Email</p>
                    <p class="text-xs sm:text-sm text-blue-600 truncate">{{ adminEmail }}</p>
                  </div>
                </a>
              </div>
            </div>


            <!-- Sección de Aerolíneas  -->
            <div class="bg-gradient-to-br from-white via-purple-50 to-indigo-50 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <!-- Header responsivo -->
              <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-center py-3 sm:py-4 md:py-6 rounded-lg sm:rounded-xl mb-4 sm:mb-6 md:mb-8">
                <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold flex flex-col sm:flex-row items-center justify-center gap-2">
                  <span class="text-lg sm:text-xl md:text-2xl">✈️</span>
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
                <p class="text-xs sm:text-sm text-gray-600 mt-2">
                  Consulta tarifas especiales y disponibilidad de vuelos con nuestras aerolíneas asociadas,
                  a través de nuestro canal de WhatsApp.
                </p>
                <div class="my-8 sm:my-10 md:my-10"></div>
                <a
                  href="https://wa.me/50379858777?text=Hola%2C%20necesito%20información%20sobre%20vuelos%20y%20tarifas%20de%20aerolíneas"
                  target="_blank"
                  class="inline-flex items-center justify-center gap-2 sm:gap-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 sm:px-6 md:px-8 py-3 sm:py-4 rounded-lg sm:rounded-xl font-semibold text-sm sm:text-base transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2 group"
                >
                  <span class="text-lg sm:text-xl group-hover:animate-pulse">💬</span>
                  <span>¿Necesitas información?</span>
                  <span class="bg-white/20 px-2 py-1 rounded-md text-xs sm:text-sm">WhatsApp</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        <!-- Espacio entre secciones -->
        <div class="my-8 sm:my-10 md:my-12"></div>
        <!-- Mapa - completamente responsivo -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-lg sm:rounded-xl shadow-lg sm:shadow-xl border border-gray-200 overflow-hidden hover:shadow-2xl transition-all duration-300">
              <!-- Header del mapa -->
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white p-3 sm:p-4 md:p-5">
                <h3 class="text-base sm:text-lg md:text-xl font-bold flex flex-col sm:flex-row items-center justify-center gap-2">
                  <span class="text-lg sm:text-xl md:text-2xl">📍</span>
                  <span class="text-center">Nuestra Ubicación</span>
                </h3>
              </div>
              <!-- Iframe del mapa responsivo -->
              <div class="relative">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d241.91319397188843!2d-88.9363812!3d14.0410515!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f636570efc5e09d%3A0xe884d67df04d7ff5!2sVASIR!5e0!3m2!1ses-419!2ssv!4v1749418509387!5m2!1ses-419!2ssv"
                  width="100%"
                  height="200"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Ubicación VASIR"
                  class="hover:opacity-90 transition-opacity duration-300 sm:h-64 md:h-72"
                >
                </iframe>
                <!-- Etiqueta flotante responsiva -->
                <div class="absolute bottom-2 sm:bottom-4 right-2 sm:right-4 bg-white/90 backdrop-blur-sm px-2 sm:px-3 py-1 sm:py-2 rounded-md sm:rounded-lg shadow-lg border border-gray-200">
                  <p class="text-xs sm:text-sm font-medium text-gray-800">📍 Chalatenango, El Salvador</p>
                </div>
              </div>
            </div>
      </div>
    </div>
  </Catalogo>
</template>
