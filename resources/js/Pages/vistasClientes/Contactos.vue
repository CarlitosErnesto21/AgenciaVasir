<script setup>
import Catalogo from '../Catalogo.vue'
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'

// Inicializar toast
const toast = useToast()

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

// Información de contacto
const contactoInfo = ref([
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
    contenido: 'info@vasir.com',
    enlace: 'mailto:info@vasir.com'
  }
])

// Funciones
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
    <div class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-red-50/30 min-h-screen pt-36">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6 sm:pb-8">
        <!-- Header Professional -->
        <div class="mb-8 sm:mb-12">
          <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 via-red-500 to-blue-600 text-white text-center py-6 sm:py-8 md:py-10">
              <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4">📞 Contáctanos</h1>
              <p class="text-lg sm:text-xl md:text-2xl text-red-100 mb-4 px-4">¿Tienes dudas? ¡Estamos aquí para ayudarte!</p>
              <p class="text-base sm:text-lg md:text-xl text-white max-w-4xl mx-auto leading-relaxed px-4">
                Encuentra respuestas rápidas en nuestras FAQ o envíanos un mensaje personalizado
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
          <!-- Columna izquierda: FAQs -->
          <div class="space-y-6 sm:space-y-8">
            <!-- Preguntas Frecuentes -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl p-6 sm:p-8 shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-4 sm:py-6 rounded-xl mb-6 sm:mb-8">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-bold flex items-center justify-center">
                  <span class="text-2xl sm:text-3xl mr-3">❓</span>
                  Preguntas Frecuentes
                </h2>
              </div>

              <!-- Buscador de FAQs -->
              <div class="mb-6 sm:mb-8">
                <div class="relative">
                  <InputText
                    v-model="busquedaFAQ"
                    placeholder="Buscar en preguntas frecuentes..."
                    class="w-full border-2 border-gray-300 focus:border-red-500 focus:ring-4 focus:ring-red-200 rounded-xl px-4 py-3 sm:py-4 text-sm sm:text-base shadow-md transition-all duration-300"
                  />
                  <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <span class="text-lg">🔍</span>
                  </div>
                </div>
              </div>

              <!-- Lista de FAQs por categoría -->
              <div class="space-y-4 sm:space-y-6">
                <div v-for="categoria in faqsFiltradas" :key="categoria.categoria" class="space-y-3 sm:space-y-4">
                  <h3 class="text-lg sm:text-xl font-bold bg-gradient-to-r from-red-600 to-blue-600 bg-clip-text text-transparent border-b-2 border-red-200 pb-2 sm:pb-3">
                    {{ categoria.categoria }}
                  </h3>

                  <div v-for="faq in categoria.preguntas" :key="faq.id" class="bg-white rounded-xl overflow-hidden shadow-lg border border-gray-200 hover:border-red-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <button
                      @click="toggleFAQ(faq.id)"
                      class="w-full text-left p-4 sm:p-5 bg-gradient-to-r from-gray-50 to-white hover:from-red-50 hover:to-blue-50 transition-all duration-300 flex justify-between items-center"
                    >
                      <span class="font-semibold text-gray-800 pr-4 text-sm sm:text-base leading-relaxed">{{ faq.pregunta }}</span>
                      <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                        <span class="text-white font-bold text-lg">
                          {{ faqAbierta === faq.id ? '−' : '+' }}
                        </span>
                      </div>
                    </button>

                    <div v-if="faqAbierta === faq.id" class="p-4 sm:p-5 bg-gradient-to-br from-white to-gray-50 border-t-2 border-red-200">
                      <p class="text-gray-700 leading-relaxed whitespace-pre-line text-sm sm:text-base">{{ faq.respuesta }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mensaje cuando no hay resultados -->
              <div v-if="faqsFiltradas.length === 0" class="text-center py-8 sm:py-12">
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border-2 border-yellow-200 rounded-xl p-6 sm:p-8 shadow-lg">
                  <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full mx-auto mb-4 flex items-center justify-center shadow-lg">
                    <span class="text-white text-2xl">🔍</span>
                  </div>
                  <h3 class="text-lg sm:text-xl font-semibold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent mb-2">No se encontraron resultados</h3>
                  <p class="text-gray-600 mb-4 text-sm sm:text-base">No se encontraron preguntas que coincidan con tu búsqueda.</p>
                  <button
                    @click="busquedaFAQ = ''"
                    class="bg-gradient-to-r from-red-600 to-blue-600 hover:from-red-700 hover:to-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1"
                  >
                    Ver todas las preguntas
                  </button>
                </div>
              </div>
            </div>

            <!-- Información de contacto -->
            <div class="bg-gradient-to-br from-white via-red-50 to-blue-50 rounded-xl p-6 sm:p-8 shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <div class="bg-gradient-to-r from-blue-600 to-red-600 text-white text-center py-4 sm:py-6 rounded-xl mb-6 sm:mb-8">
                <h3 class="text-lg sm:text-xl md:text-2xl font-bold flex items-center justify-center">
                  <span class="text-xl sm:text-2xl mr-2">📋</span>
                  Información de Contacto
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div v-for="info in contactoInfo" :key="info.titulo" class="bg-white rounded-xl p-4 sm:p-5 shadow-lg border border-gray-200 hover:border-red-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                  <div class="flex items-start space-x-3 sm:space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-blue-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                      <span class="text-white text-lg sm:text-xl">{{ info.icono }}</span>
                    </div>
                    <div class="flex-1">
                      <h4 class="font-bold text-gray-800 text-sm sm:text-base mb-1 sm:mb-2">{{ info.titulo }}</h4>
                      <div v-if="info.enlace">
                        <a :href="info.enlace" target="_blank" class="text-red-600 hover:text-blue-600 hover:underline text-sm sm:text-base font-medium transition-colors duration-300">
                          {{ info.contenido }}
                        </a>
                      </div>
                      <div v-else class="text-gray-600 text-sm sm:text-base whitespace-pre-line leading-relaxed">
                        {{ info.contenido }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Columna derecha: Formulario de contacto -->
          <div class="space-y-6 sm:space-y-8">
            <!-- Formulario -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl p-6 sm:p-8 shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-4 sm:py-6 rounded-xl mb-6 sm:mb-8">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-bold flex items-center justify-center">
                  <span class="text-2xl sm:text-3xl mr-3">✉️</span>
                  Envíanos un Mensaje
                </h2>
              </div>

              <form class="space-y-5 sm:space-y-6" @submit="enviarFormulario">
                <div>
                  <label class="block mb-2 sm:mb-3 font-bold text-gray-700 text-sm sm:text-base">Nombre completo</label>
                  <InputText
                    v-model="nombre"
                    class="w-full border-2 border-gray-300 focus:border-red-500 focus:ring-4 focus:ring-red-200 rounded-xl px-4 py-3 sm:py-4 text-sm sm:text-base shadow-md transition-all duration-300"
                    placeholder="Tu nombre completo"
                    required
                  />
                </div>

                <div>
                  <label class="block mb-2 sm:mb-3 font-bold text-gray-700 text-sm sm:text-base">Correo electrónico</label>
                  <InputText
                    v-model="email"
                    type="email"
                    class="w-full border-2 border-gray-300 focus:border-red-500 focus:ring-4 focus:ring-red-200 rounded-xl px-4 py-3 sm:py-4 text-sm sm:text-base shadow-md transition-all duration-300"
                    placeholder="tu@email.com"
                    required
                  />
                </div>

                <div>
                  <label class="block mb-2 sm:mb-3 font-bold text-gray-700 text-sm sm:text-base">Mensaje</label>
                  <Textarea
                    v-model="mensaje"
                    class="w-full border-2 border-gray-300 focus:border-red-500 focus:ring-4 focus:ring-red-200 rounded-xl px-4 py-3 sm:py-4 text-sm sm:text-base shadow-md transition-all duration-300"
                    rows="5"
                    placeholder="Escribe tu mensaje aquí... Cuéntanos sobre el tour que te interesa, fechas, número de personas, etc."
                    autoResize
                    required
                  />
                </div>

                <Button
                  type="submit"
                  label="Enviar Mensaje"
                  class="!bg-gradient-to-r !from-red-600 !to-blue-600 hover:!from-red-700 hover:!to-blue-700 !border-none !px-6 !py-3 sm:!py-4 !text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 w-full text-sm sm:text-base"
                />
              </form>
            </div>

            <!-- Métodos de contacto directo -->
            <div class="bg-gradient-to-br from-white via-red-50 to-blue-50 rounded-xl p-6 sm:p-8 shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
              <div class="bg-gradient-to-r from-blue-600 to-red-600 text-white text-center py-4 sm:py-6 rounded-xl mb-6 sm:mb-8">
                <h3 class="text-lg sm:text-xl md:text-2xl font-bold flex items-center justify-center">
                  <span class="text-xl sm:text-2xl mr-2">🚀</span>
                  Contacto Directo
                </h3>
              </div>

              <div class="space-y-4 sm:space-y-5">
                <a
                  href="https://wa.me/50312345678"
                  target="_blank"
                  class="flex items-center p-4 sm:p-5 bg-gradient-to-r from-green-50 to-green-100 border-2 border-green-200 rounded-xl hover:from-green-100 hover:to-green-200 hover:border-green-300 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
                >
                  <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mr-4 shadow-lg">
                    <span class="text-white text-xl">💬</span>
                  </div>
                  <div>
                    <p class="font-bold text-green-700 text-sm sm:text-base">WhatsApp</p>
                    <p class="text-xs sm:text-sm text-green-600">Respuesta inmediata</p>
                  </div>
                </a>

                <a
                  href="mailto:info@vasir.com"
                  class="flex items-center p-4 sm:p-5 bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl hover:from-blue-100 hover:to-blue-200 hover:border-blue-300 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
                >
                  <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mr-4 shadow-lg">
                    <span class="text-white text-xl">📧</span>
                  </div>
                  <div>
                    <p class="font-bold text-blue-700 text-sm sm:text-base">Email</p>
                    <p class="text-xs sm:text-sm text-blue-600">info@vasir.com</p>
                  </div>
                </a>

                <div class="flex items-center p-4 sm:p-5 bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-200 rounded-xl shadow-lg">
                  <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center mr-4 shadow-lg">
                    <span class="text-white text-xl">📱</span>
                  </div>
                  <div>
                    <p class="font-bold text-red-700 text-sm sm:text-base">Redes Sociales</p>
                    <p class="text-xs sm:text-sm text-red-600">Síguenos en Facebook, Instagram y TikTok</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mapa -->
            <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl shadow-xl border border-gray-200 overflow-hidden hover:shadow-2xl transition-all duration-300">
              <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white p-4 sm:p-5">
                <h3 class="text-lg sm:text-xl font-bold flex items-center justify-center">
                  <span class="text-xl sm:text-2xl mr-2">📍</span>
                  Nuestra Ubicación
                </h3>
              </div>
              <div class="relative">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d241.91319397188843!2d-88.9363812!3d14.0410515!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8f636570efc5e09d%3A0xe884d67df04d7ff5!2sVASIR!5e0!3m2!1ses-419!2ssv!4v1749418509387!5m2!1ses-419!2ssv"
                  width="100%"
                  height="250"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  title="Ubicación VASIR"
                  class="hover:opacity-90 transition-opacity duration-300"
                >
                </iframe>
                <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg shadow-lg border border-gray-200">
                  <p class="text-xs font-medium text-gray-800">📍 Chalatenango, El Salvador</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA Final -->
        <div class="mt-8 sm:mt-12">
          <div class="bg-gradient-to-br from-white via-blue-50 to-red-50 rounded-xl p-6 sm:p-8 md:p-10 shadow-xl border border-gray-200 hover:shadow-2xl transition-all duration-300">
            <div class="bg-gradient-to-r from-red-600 to-blue-600 text-white text-center py-4 sm:py-6 rounded-xl mb-6 sm:mb-8">
              <h2 class="text-xl sm:text-2xl md:text-3xl font-bold">🤔 ¿No encontraste lo que buscabas?</h2>
            </div>
            <div class="text-center">
              <p class="text-gray-700 mb-6 sm:mb-8 text-sm sm:text-base md:text-lg leading-relaxed max-w-3xl mx-auto">
                Nuestro equipo está disponible para responder cualquier pregunta específica sobre nuestros tours y servicios.
                ¡Estamos aquí para hacer realidad tu próxima aventura!
              </p>
              <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center items-center">
                <a
                  href="https://wa.me/50312345678"
                  target="_blank"
                  class="w-full sm:w-auto bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 sm:py-4 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-flex items-center justify-center"
                >
                  <span class="mr-2 text-lg">💬</span>
                  Chatear por WhatsApp
                </a>
                <a
                  href="/sobre-nosotros"
                  class="w-full sm:w-auto bg-gradient-to-r from-red-600 to-blue-600 hover:from-red-700 hover:to-blue-700 text-white px-6 py-3 sm:py-4 rounded-xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-flex items-center justify-center"
                >
                  <span class="mr-2 text-lg">🏢</span>
                  Conocer más sobre VASIR
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Catalogo>
</template>
