<!-- Ejemplo de uso en un componente Vue para procesos largos -->
<template>
    <div>
        <Button 
            @click="procesarReserva"
            :loading="procesando"
            label="Procesar Reserva"
        />
        
        <Button 
            @click="procesoLargo"
            :loading="procesandoLargo"
            label="Proceso Largo"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const procesando = ref(false)
const procesandoLargo = ref(false)

// Ejemplo 1: Proceso normal con manejo automático de CSRF
const procesarReserva = async () => {
    procesando.value = true
    
    try {
        // Los interceptores automáticos manejan el token CSRF
        const response = await axios.post('/api/reservas', {
            // datos de la reserva
        })
        
        // Éxito
        console.log('Reserva procesada:', response.data)
        
    } catch (error) {
        console.error('Error:', error)
        
        // Si es error 419 (CSRF), el interceptor automáticamente
        // renovará el token y reintentará la request
        
    } finally {
        procesando.value = false
    }
}

// Ejemplo 2: Proceso largo con renovación periódica de token
const procesoLargo = async () => {
    procesandoLargo.value = true
    
    try {
        // Usar el método global para procesos largos
        await this.$startLongProcess(async () => {
            // Simular proceso largo
            for (let i = 0; i < 10; i++) {
                await new Promise(resolve => setTimeout(resolve, 1000))
                console.log(`Paso ${i + 1}/10 completado`)
                
                // El token se renueva automáticamente cada 5 minutos
                // durante la ejecución de este proceso
            }
            
            return { success: true }
        }, {
            showProgress: true,
            refreshInterval: 3 * 60 * 1000, // Renovar cada 3 minutos
            onTokenRefresh: () => {
                console.log('Token CSRF renovado durante proceso largo')
            }
        })
        
        console.log('Proceso largo completado!')
        
    } catch (error) {
        console.error('Error en proceso largo:', error)
    } finally {
        procesandoLargo.value = false
    }
}

// Ejemplo 3: Usar Inertia con manejo automático
const navegarConInertia = () => {
    // Inertia también tiene interceptores automáticos configurados
    router.post('/reservas', {
        // datos
    }, {
        onSuccess: () => {
            console.log('Éxito con Inertia')
        },
        onError: (errors) => {
            console.log('Errores:', errors)
        }
    })
}
</script>