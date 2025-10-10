<script setup>
import { computed } from 'vue'
import { useCarritoStore } from '@/stores/carrito'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import ImageWithFallback from '@/Components/ImageWithFallback.vue'
import {
  faShoppingCart,
  faTrash,
  faPlus,
  faMinus,
  faXmark,
  faCreditCard,
  faShoppingBag
} from '@fortawesome/free-solid-svg-icons'

const carritoStore = useCarritoStore()

// Formatear precio
const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

// Función para proceder al checkout
const procederAlCheckout = () => {
  // Aquí puedes implementar la lógica de checkout
  // Por ejemplo, redirigir a una página de checkout
  console.log('Procediendo al checkout con:', carritoStore.items)
  // router.visit('/checkout') o la ruta que uses
}
</script>

<template>
  <!-- Overlay del carrito -->
  <div
    v-if="carritoStore.isVisible"
    class="fixed inset-0 z-[10000] overflow-hidden carrito-overlay"
    @click.self="carritoStore.ocultarCarrito()"
  >
    <!-- Fondo oscuro clickeable -->
    <div
      class="absolute inset-0 bg-black bg-opacity-50 transition-opacity cursor-pointer fondo-clickeable"
      @click="carritoStore.ocultarCarrito()"
    ></div>

    <!-- Panel del carrito -->
    <div class="absolute right-0 top-0 h-full w-full max-w-md transform transition-transform carrito-panel">
      <div class="flex h-full flex-col bg-white shadow-xl">
        <!-- Header del carrito -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <FontAwesomeIcon :icon="faShoppingCart" class="text-blue-600" />
            Carrito de Compras
          </h2>
          <button
            @click="carritoStore.ocultarCarrito()"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <FontAwesomeIcon :icon="faXmark" class="w-6 h-6" />
          </button>
        </div>

        <!-- Contador de items -->
        <div class="px-6 py-2 bg-blue-50 border-b border-blue-100">
          <p class="text-sm text-blue-800">
            {{ carritoStore.itemCount }} {{ carritoStore.itemCount === 1 ? 'producto' : 'productos' }} en tu carrito
          </p>
        </div>

        <!-- Lista de productos -->
        <div class="flex-1 overflow-y-auto px-6 py-4 carrito-scroll">
          <!-- Estado vacío -->
          <div v-if="carritoStore.isEmpty" class="flex flex-col items-center justify-center h-full text-center empty-state">
            <FontAwesomeIcon :icon="faShoppingBag" class="w-16 h-16 text-gray-300 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tu carrito está vacío</h3>
            <p class="text-gray-500 mb-6">Agrega algunos productos para comenzar</p>
            <button
              @click="carritoStore.ocultarCarrito()"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors"
            >
              Continuar Comprando
            </button>
          </div>

          <!-- Lista de productos -->
          <div v-else class="space-y-4">
            <div
              v-for="item in carritoStore.items"
              :key="item.id"
              class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors producto-item"
            >
              <!-- Imagen del producto -->
              <div class="flex-shrink-0 w-16 h-16 producto-imagen">
                <ImageWithFallback
                  :src="item.imagen"
                  :alt="item.nombre"
                  :fallback-text="item.nombre"
                  image-classes="w-full h-full object-cover rounded-md border border-gray-200"
                  placeholder-classes="w-full h-full rounded-md border border-gray-200"
                />
              </div>

              <!-- Información del producto -->
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 truncate">
                  {{ item.nombre }}
                </h4>
                <p class="text-sm text-blue-600 font-semibold">
                  {{ formatPrice(item.precio) }}
                </p>
                <p class="text-xs text-gray-500 whitespace-nowrap">
                  Stock: {{ item.stock_actual }}
                </p>
              </div>

              <!-- Controles de cantidad -->
              <div class="flex items-center gap-2">
                <button
                  @click="carritoStore.decrementarCantidad(item.id)"
                  class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-md hover:bg-gray-50 transition-colors cantidad-btn"
                  :disabled="item.cantidad <= 1"
                >
                  <FontAwesomeIcon :icon="faMinus" class="w-3 h-3" />
                </button>

                <span class="w-8 text-center text-sm font-medium">
                  {{ item.cantidad }}
                </span>

                <button
                  @click="carritoStore.incrementarCantidad(item.id)"
                  class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-md hover:bg-gray-50 transition-colors cantidad-btn"
                  :disabled="item.cantidad >= item.stock_actual"
                >
                  <FontAwesomeIcon :icon="faPlus" class="w-3 h-3" />
                </button>
              </div>

              <!-- Botón eliminar -->
              <button
                @click="carritoStore.eliminarProducto(item.id)"
                class="p-2 text-red-400 hover:text-red-600 transition-colors btn-eliminar"
                title="Eliminar producto"
              >
                <FontAwesomeIcon :icon="faTrash" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Botón vaciar carrito (solo si hay items) -->
        <div v-if="!carritoStore.isEmpty" class="px-6 py-3 border-t border-gray-100">
          <button
            @click="carritoStore.limpiarCarrito()"
            class="w-full text-red-500 hover:text-red-700 hover:bg-red-50 py-2 px-4 text-sm transition-colors flex items-center justify-center gap-2 rounded-md border border-red-200 hover:border-red-300"
          >
            <FontAwesomeIcon :icon="faTrash" class="w-4 h-4" />
            Vaciar Carrito
          </button>
        </div>

        <!-- Footer del carrito -->
        <div v-if="!carritoStore.isEmpty" class="border-t border-gray-200 px-6 py-4 space-y-4">
          <!-- Total -->
          <div class="flex items-center justify-between">
            <span class="text-lg font-semibold text-gray-900">Total:</span>
            <span class="text-xl font-bold text-blue-600 total-badge">
              {{ formatPrice(carritoStore.totalPrice) }}
            </span>
          </div>

          <!-- Botones de acción -->
          <div class="space-y-2">
            <button
              @click="procederAlCheckout"
              class="w-full text-white py-3 px-4 rounded-md transition-colors flex items-center justify-center gap-2 font-medium btn-checkout"
            >
              <FontAwesomeIcon :icon="faCreditCard" />
              Proceder al Checkout
            </button>

            <button
              @click="carritoStore.ocultarCarrito()"
              class="w-full text-white py-2 px-4 rounded-md transition-colors btn-continuar"
            >
              Continuar Comprando
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animaciones de entrada y salida */
.carrito-overlay {
  backdrop-filter: blur(4px);
  animation: fadeIn 0.3s ease-out;
  cursor: pointer;
}

.carrito-panel {
  animation: slideIn 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  cursor: default;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
  }
  to {
    transform: translateX(0);
  }
}

/* Mejorar la experiencia de clic fuera del panel */
.carrito-overlay:hover {
  background-color: rgba(0, 0, 0, 0.6);
  transition: background-color 0.2s ease;
}

.fondo-clickeable:hover {
  background-color: rgba(0, 0, 0, 0.6) !important;
  transition: background-color 0.2s ease;
}

/* Efectos hover para productos */
.producto-item {
  transition: all 0.2s ease;
}

.producto-item:hover {
  transform: translateX(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #3b82f6;
}

/* Botones con gradientes */
.btn-checkout {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
  transition: all 0.3s ease;
}

.btn-checkout:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
}

.btn-continuar {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white !important;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.btn-continuar:hover {
  transform: translateY(-2px);
  background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
  box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
}

/* Controles de cantidad estilizados */
.cantidad-btn {
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.cantidad-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  transform: scale(1.1);
}

.cantidad-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Efectos de hover para botones de eliminar */
.btn-eliminar {
  transition: all 0.2s ease;
  border-radius: 8px;
}

.btn-eliminar:hover {
  background-color: #fef2f2;
  color: #dc2626;
  transform: scale(1.1);
}

/* Animación del badge del total */
.total-badge {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

/* Efectos de imagen */
.producto-imagen {
  transition: all 0.3s ease;
  border-radius: 8px;
  overflow: hidden;
}

.producto-imagen:hover {
  transform: scale(1.05);
}

/* Estilos para el estado vacío */
.empty-state {
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Scrollbar personalizado */
.carrito-scroll::-webkit-scrollbar {
  width: 6px;
}

.carrito-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.carrito-scroll::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 3px;
}

.carrito-scroll::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
}

/* Efectos de enfoque */
.carrito-button:focus,
.cantidad-btn:focus,
.btn-checkout:focus,
.btn-continuar:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

/* Animación de carga para el checkout */
.btn-checkout.loading {
  position: relative;
  color: transparent;
}

.btn-checkout.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 20px;
  height: 20px;
  margin: -10px 0 0 -10px;
  border: 2px solid transparent;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsivo mejorado */
@media (max-width: 768px) {
  .carrito-panel {
    width: 85%;
    max-width: 400px;
    right: 0;
    top: 0;
    bottom: 0;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }

  .producto-item {
    padding: 10px;
  }

  .btn-checkout,
  .btn-continuar {
    font-size: 0.875rem;
    padding: 10px 16px;
  }
}

@media (max-width: 480px) {
  .carrito-panel {
    width: 80%;
    max-width: 350px;
    right: 0;
    top: 0;
    bottom: 0;
    height: 100vh;
    max-height: 100vh;
    border-radius: 0;
  }

  .carrito-header h3 {
    font-size: 1.1rem;
  }

  .producto-item {
    padding: 8px;
    gap: 8px;
  }

  .producto-imagen {
    width: 50px;
    height: 50px;
  }

  .producto-info h4 {
    font-size: 0.9rem;
  }

  .producto-info p {
    font-size: 0.65rem;
  }

  .cantidad-controls {
    gap: 4px;
  }

  .cantidad-btn {
    width: 28px;
    height: 28px;
    font-size: 0.9rem;
  }
}

/* Efectos de microinteracción */
.bounce-in {
  animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes bounceIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
