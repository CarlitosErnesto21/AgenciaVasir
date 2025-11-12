import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';
import PrimeVue from 'primevue/config';
import { createPinia } from 'pinia';

import Aura from '@primeuix/themes/aura';
import { ToastService } from 'primevue';
import 'primeicons/primeicons.css';

// Importaciones de componentes de PrimeVue
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Toolbar from 'primevue/toolbar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Carousel from "primevue/carousel";
import ConfirmationService from 'primevue/confirmationservice';
import DatePicker from "primevue/datepicker";
import ConfirmDialog from 'primevue/confirmdialog';
import Chart from 'primevue/chart';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName, // Se escribe así para que el título sea dinámico
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'), // Importa los componentes de las páginas para que Inertia los pueda resolver con el fin de que se puedan utilizar en la aplicación
        ),
    setup({ el, App, props, plugin }) {
        const app =  createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia()) // Configurar Pinia para el estado global
            //configuración global de SweetAlert2
            app.config.globalProperties.$swal = Swal;
            app.use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: 'false',
                        cssLayer: {
                            name: 'primevue',
                            order: 'tailwind-base, primevue, tailwind-utilities'
                        }
                    }
                }
            });
            app.use(ToastService);
            app.use(ConfirmationService)
            // Definimos los componentes de PrimeVue
            app.component('Toast', Toast); // Componente de notificaciones
            app.component('Button', Button); // Componente de botones
            app.component('Card', Card); // Componente de tarjetas
            app.component('Toolbar', Toolbar); // Componente de barra de herramientas
            app.component('DataTable', DataTable); // Componente de tabla de datos
            app.component('Column', Column); // Componente de columna para tablas
            app.component('Dialog', Dialog); // Componente de diálogo modal
            app.component('InputText', InputText); // Componente de entrada de texto
            app.component('Password', Password); // Componente de entrada de contraseña
            app.component('IconField', IconField); // Componente de campo de icono
            app.component('InputIcon', InputIcon);  // Componente de entrada con icono
            app.component('FileUpload', FileUpload); // Componente de carga de archivos
            app.component('Textarea', Textarea); // Componente de área de texto
            app.component('InputNumber', InputNumber); // Componente de entrada numérica
            app.component('Select', Select); // Componente de selección
            app.component('Carousel', Carousel); // Componente de carrusel
            app.component('DatePicker', DatePicker); // Componente de calendario
            app.component('ConfirmDialog', ConfirmDialog); // Componente de diálogo de confirmación
            app.component('Chart', Chart); // Componente de gráficos
            app.component('Tabs', Tabs); // Componente de pestañas
            app.component('TabList', TabList); // Componente de lista de pestañas
            app.component('Tab', Tab); // Componente de pestaña individual
            app.component('TabPanels', TabPanels); // Componente de paneles de pestañas
            app.component('TabPanel', TabPanel); // Componente de panel individual
            app.component('Tag', Tag); // Componente de etiqueta
            app.mount(el);
            return app;
    },
    progress: {
        color: '#4B5563',
    },
});

