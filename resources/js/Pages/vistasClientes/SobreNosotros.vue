<script setup>
import Catalogo from '../Catalogo.vue';
import HeaderSection from './SobreNosotrosViews/HeaderSection.vue';
import ValoresSection from './SobreNosotrosViews/ValoresSection.vue';
import InstalacionesSection from './SobreNosotrosViews/InstalacionesSection.vue';
import PorQueElegirnosSection from './SobreNosotrosViews/PorQueElegirnosSection.vue';

const props = defineProps({
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
        <!-- Sección Header con Estadísticas, Misión y Visión -->
        <HeaderSection :siteSettings="props.siteSettings" />

        <!-- Valores de la empresa -->
        <ValoresSection :companyValues="props.companyValues" />

        <!-- Nuestras Instalaciones -->
        <InstalacionesSection />

        <!-- ¿Por qué elegir Vasir? -->
        <PorQueElegirnosSection />
    </Catalogo>
</template>
