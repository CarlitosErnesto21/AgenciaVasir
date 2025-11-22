
<script setup>
import Catalogo from '../Catalogo.vue';
import MisionVisionSection from './SobreNosotrosViews/MisionVisionSection.vue';
import ValoresSection from './SobreNosotrosViews/ValoresSection.vue';
import InstalacionesSection from './SobreNosotrosViews/InstalacionesSection.vue';
import PorQueElegirnosSection from './SobreNosotrosViews/PorQueElegirnosSection.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUserCircle } from '@fortawesome/free-solid-svg-icons';

const { siteSettings, companyValues } = defineProps({
    siteSettings: {
        type: Object,
        default: () => ({})
    },
    companyValues: {
        type: Array,
        default: () => []
    }
});
</script>
<template>
    <Catalogo>
        <!-- Header Professional - Ancho completo de la pantalla -->
        <div class="w-full bg-gradient-to-r from-red-500 via-blue-600 to-blue-600 text-white text-center py-4 sm:py-6 mt-20 sm:mt-20 md:mt-28 lg:mt-32 xl:mt-32 mb-6 sm:mb-8 shadow-xl">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">
                <FontAwesomeIcon :icon="faUserCircle" class="text-yellow-300 mr-2" />
                Sobre Nosotros</h1>
            <p class="text-base sm:text-lg text-red-100 px-4">Descubre la historia y pasión detrás de VASIR</p>
        </div>

        <!-- Contenido principal con fondo degradado -->
        <div class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-red-50/30 min-h-screen pb-8">

            <!-- Misión y Visión -->
            <MisionVisionSection :siteSettings="siteSettings" />

            <!-- Valores de la empresa -->
            <ValoresSection :companyValues="companyValues" />

            <!-- Nuestras Instalaciones -->
            <InstalacionesSection />

            <!-- ¿Por qué elegir Vasir? -->
            <PorQueElegirnosSection />

        </div>
    </Catalogo>
</template>
