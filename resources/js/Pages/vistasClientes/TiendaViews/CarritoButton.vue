<script setup>
import { useCarritoStore } from '@/stores/carrito'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faShoppingCart } from '@fortawesome/free-solid-svg-icons'
import { ref, watch } from 'vue'

const carritoStore = useCarritoStore()

// Estados para las animaciones
const isAnimating = ref(false)
const showPlusQuantity = ref(false)
const displayQuantity = ref(1)

// Función para animar cuando se agrega un producto
const animateProductAdded = (quantity = 1) => {
  displayQuantity.value = quantity
  isAnimating.value = true
  showPlusQuantity.value = true

  // Detener la animación después de un tiempo
  setTimeout(() => {
    isAnimating.value = false
  }, 800)

  // Ocultar el +cantidad después de un tiempo
  setTimeout(() => {
    showPlusQuantity.value = false
  }, 1200)
}

// Exponer la función para que pueda ser llamada desde el store
defineExpose({
  animateProductAdded
})

// Escuchar cambios en triggerAnimation para activar la animación con la cantidad correcta
watch(() => carritoStore.triggerAnimation, () => {
  animateProductAdded(carritoStore.lastAddedQuantity)
})
</script>

<template>
  <div class="carrito-container">
    <button
      v-show="!carritoStore.isVisible"
      @click="carritoStore.toggleVisibility()"
      :class="[
        'carrito-button',
        { 'animating': isAnimating }
      ]"
      title="Ver carrito de compras"
    >
      <!-- Icono del carrito -->
      <div class="carrito-icon">
        <FontAwesomeIcon :icon="faShoppingCart" />
      </div>

      <!-- Badge con contador -->
      <Transition name="bounce">
        <span
          v-if="carritoStore.itemCount > 0"
          class="carrito-badge"
        >
          {{ carritoStore.itemCount > 99 ? '99+' : carritoStore.itemCount }}
        </span>
      </Transition>

      <!-- Efecto de ripple -->
      <div class="ripple-effect"></div>

      <!-- Efecto de glow cuando se agrega -->
      <div v-if="isAnimating" class="glow-effect"></div>
    </button>

    <!-- Animación +cantidad -->
    <Transition name="plus-quantity">
      <div v-if="showPlusQuantity" class="plus-quantity">
        +{{ displayQuantity }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.carrito-container {
  position: fixed;
  bottom: 25px;
  right: 25px;
  z-index: 9999;
  /* Asegurar espacio para el badge */
  padding: 10px;
}

.carrito-button {
  position: relative;
  /* Removido overflow: hidden para que el badge no se corte */
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border: none;
  border-radius: 16px;
  padding: 12px;
  box-shadow:
    0 4px 15px rgba(59, 130, 246, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  min-width: 56px;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Animación cuando se agrega un producto */
.carrito-button.animating {
  animation: shake-glow 0.8s ease-in-out;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.carrito-button:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow:
    0 8px 25px rgba(59, 130, 246, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.2) inset;
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
}

.carrito-button:active {
  transform: translateY(0) scale(0.98);
  transition-duration: 0.1s;
}

.carrito-icon {
  color: white;
  transition: all 0.3s ease;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.carrito-icon svg {
  width: 20px !important;
  height: 20px !important;
}

.carrito-button:hover .carrito-icon {
  transform: rotate(-5deg) scale(1.1);
}

.carrito-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
  min-width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  box-shadow:
    0 2px 8px rgba(239, 68, 68, 0.4),
    0 0 0 2px white;
  animation: pulse-glow 2s infinite;
  padding: 0 6px;
}

.ripple-effect {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  opacity: 0;
  pointer-events: none;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.6) 0%, transparent 70%);
  transform: scale(0);
  transition: all 0.6s ease-out;
}

.carrito-button:active .ripple-effect {
  animation: ripple 0.6s ease-out;
}

/* Animaciones */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow:
      0 2px 8px rgba(239, 68, 68, 0.4),
      0 0 0 2px white;
  }
  50% {
    box-shadow:
      0 2px 12px rgba(239, 68, 68, 0.6),
      0 0 0 2px white,
      0 0 20px rgba(239, 68, 68, 0.3);
  }
}

@keyframes ripple {
  0% {
    transform: scale(0);
    opacity: 1;
  }
  100% {
    transform: scale(2);
    opacity: 0;
  }
}

/* Transiciones para el badge */
.bounce-enter-active {
  animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.bounce-leave-active {
  animation: bounceOut 0.3s ease-in;
}

@keyframes bounceIn {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes bounceOut {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(0);
    opacity: 0;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .carrito-container {
    bottom: 20px;
    right: 20px;
    padding: 12px;
  }

  .carrito-button {
    min-width: 56px;
    min-height: 56px;
    padding: 16px;
    border-radius: 16px;
  }

  .carrito-icon {
    width: 22px;
    height: 22px;
  }

  .carrito-icon svg {
    width: 20px !important;
    height: 20px !important;
  }

  .carrito-badge {
    top: -6px;
    right: -6px;
    font-size: 0.75rem;
    min-width: 20px;
    height: 20px;
  }
}

@media (max-width: 480px) {
  .carrito-container {
    bottom: 15px;
    right: 15px;
    padding: 10px;
  }

  .carrito-button {
    min-width: 54px;
    min-height: 54px;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
  }

  .carrito-icon {
    width: 20px;
    height: 20px;
  }

  .carrito-icon svg {
    width: 18px !important;
    height: 18px !important;
  }

  .carrito-badge {
    top: -4px;
    right: -4px;
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
  }
}

/* Estados especiales */
.carrito-button:focus {
  outline: none;
  box-shadow:
    0 8px 25px rgba(59, 130, 246, 0.4),
    0 0 0 3px rgba(59, 130, 246, 0.2);
}

.carrito-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Efecto de carga/loading si lo necesitas */
.carrito-button.loading {
  pointer-events: none;
}

.carrito-button.loading .carrito-icon {
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

/* Nuevas animaciones para agregar producto */
@keyframes shake-glow {
  0% {
    transform: scale(1) rotate(0deg);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
  }
  10% {
    transform: scale(1.1) rotate(-2deg);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.6);
  }
  20% {
    transform: scale(1.15) rotate(2deg);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.8);
  }
  30% {
    transform: scale(1.1) rotate(-2deg);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.6);
  }
  40% {
    transform: scale(1.15) rotate(2deg);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.8);
  }
  50% {
    transform: scale(1.1) rotate(-1deg);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.6);
  }
  60% {
    transform: scale(1.08) rotate(1deg);
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.5);
  }
  70% {
    transform: scale(1.05) rotate(-0.5deg);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
  }
  80% {
    transform: scale(1.02) rotate(0.5deg);
    box-shadow: 0 5px 18px rgba(59, 130, 246, 0.35);
  }
  90% {
    transform: scale(1.01) rotate(0deg);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.32);
  }
  100% {
    transform: scale(1) rotate(0deg);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
  }
}

/* Efecto de glow */
.glow-effect {
  position: absolute;
  inset: -4px;
  border-radius: 20px;
  background: linear-gradient(45deg,
    rgba(16, 185, 129, 0.4),
    rgba(34, 197, 94, 0.4),
    rgba(16, 185, 129, 0.4));
  animation: rotate-glow 0.8s ease-in-out;
  z-index: -1;
}

@keyframes rotate-glow {
  0% {
    opacity: 0;
    transform: rotate(0deg) scale(0.8);
  }
  50% {
    opacity: 1;
    transform: rotate(180deg) scale(1.2);
  }
  100% {
    opacity: 0;
    transform: rotate(360deg) scale(0.8);
  }
}

/* Animación del +cantidad */
.plus-quantity {
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%);
  color: #10b981;
  font-weight: bold;
  font-size: 18px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  pointer-events: none;
  z-index: 10000;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 8px;
  border: 2px solid #10b981;
}

/* Transiciones para el +cantidad */
.plus-quantity-enter-active {
  transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.plus-quantity-leave-active {
  transition: all 0.4s ease-in;
}

.plus-quantity-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(10px) scale(0.5);
}

.plus-quantity-enter-to {
  opacity: 1;
  transform: translateX(-50%) translateY(-20px) scale(1.2);
}

.plus-quantity-leave-from {
  opacity: 1;
  transform: translateX(-50%) translateY(-20px) scale(1.2);
}

.plus-quantity-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-40px) scale(0.8);
}

/* Ajustes responsivos para el contenedor */
@media (max-width: 768px) {
  .plus-quantity {
    font-size: 16px;
    top: -12px;
  }
}

@media (max-width: 480px) {
  .plus-quantity {
    font-size: 14px;
    top: -10px;
  }
}
</style>
