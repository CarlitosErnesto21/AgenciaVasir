
<script setup>
import Catalogo from '../Catalogo.vue';
import HeaderSection from './SobreNosotrosViews/HeaderSection.vue';
import MisionVisionSection from './SobreNosotrosViews/MisionVisionSection.vue';
import ValoresSection from './SobreNosotrosViews/ValoresSection.vue';
import InstalacionesSection from './SobreNosotrosViews/InstalacionesSection.vue';
import PorQueElegirnosSection from './SobreNosotrosViews/PorQueElegirnosSection.vue';

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
        <!-- Sección Header -->
        <HeaderSection :siteSettings="siteSettings" />

        <!-- Misión y Visión en card blanco debajo del header -->
        <div class="px-1 sm:px-1 lg:px-2 max-w-7xl mx-auto">
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg overflow-hidden p-6 sm:p-8">
                <MisionVisionSection :siteSettings="siteSettings" />
            </div>
        </div>

        <!-- Valores de la empresa -->
        <ValoresSection :companyValues="companyValues" />

        <!-- Nuestras Instalaciones -->
        <InstalacionesSection />

        <!-- ¿Por qué elegir Vasir? -->
        <PorQueElegirnosSection />
    </Catalogo>
</template>
