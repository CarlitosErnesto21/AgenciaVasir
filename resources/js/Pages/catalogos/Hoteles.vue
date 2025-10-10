<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import FileUpload from "primevue/fileupload";
import Toast from "primevue/toast";
import Select from "primevue/select";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEdit, faEye, faGlobe, faMapLocation, faPlus, faTags, faTrashCan } from '@fortawesome/free-solid-svg-icons';
import axios from "axios";
import Carousel from "primevue/carousel";

const toast = useToast();

const hoteles = ref([]);
const hotel = ref({
    id: null,
    nombre: "",
    direccion: "",
    descripcion: "",
    estado: "",
    provincia: null,
    categoria: null,
    imagenes: [],
});

const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const removedImages = ref([]);
const selectedHoteles = ref();
const dialog = ref(false);
const deleteDialog = ref(false);
const submitted = ref(false);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const btnTitle = ref("Guardar");
const url = "/api/hoteles";
const IMAGE_PATH = "/storage/";
const paises = ref([]);
const provincias = ref([]);
const categoriasHoteles = ref([]);
const estados = ref([
    { label: "Activo", value: "activo" },
    { label: "Inactivo", value: "inactivo" },
]);
const showImageDialog = ref(false);
const selectedImages = ref([]);

onMounted(() => {
    fetchHoteles();
    fetchPaises();
    fetchProvincias();
    fetchCategoriasHoteles();
});

const fetchHoteles = async () => {
    try {
        const response = await axios.get(url);
        // Mapear imágenes para cada hotel, SOLO nombres de archivo
        hoteles.value = response.data.map((hotel) => ({
            ...hotel,
            imagenes: (hotel.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
        }));
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los hoteles",
            life: 4000,
        });
    }
};

const fetchPaises = async () => {
    try {
        const response = await axios.get("/api/paises");
        paises.value = response.data;
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar los países",
            life: 4000,
        });
    }
};

const fetchProvincias = async () => {
    try {
        const response = await axios.get("/api/provincias");
        provincias.value = response.data;
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las provincias",
            life: 4000,
        });
    }
};

const fetchCategoriasHoteles = async () => {
    try {
        const response = await axios.get("/api/categorias-hoteles");
        categoriasHoteles.value = response.data;
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las categorías",
            life: 4000,
        });
    }
};

const openNew = () => {
    hotel.value = {
        id: null,
        nombre: "",
        direccion: "",
        descripcion: "",
        estado: "",
        provincia: null,
        categoria: null,
        imagenes: [],
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
    btnTitle.value = "Guardar";
    dialog.value = true;
};

const editHotel = (h) => {
    hotel.value = { ...h };
    hotel.value.categoria = h.categoria_hotel ? h.categoria_hotel.id : null;
    hotel.value.provincia = h.provincia ? h.provincia.id : null;
    imagenPreviewList.value = Array.isArray(h.imagenes)
        ? h.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];

    imagenFiles.value = [];
    removedImages.value = [];
    btnTitle.value = "Actualizar";
    dialog.value = true;
};

const saveOrUpdate = async () => {
    submitted.value = true;

    // Validación de campos obligatorios
    if (
        !hotel.value.nombre ||
        !hotel.value.direccion ||
        !hotel.value.descripcion ||
        !hotel.value.estado ||
        !hotel.value.provincia ||
        !hotel.value.categoria
    ) {
        return;
    }

    if (imagenPreviewList.value.length === 0) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Debes subir al menos una imagen.",
            life: 4000,
        });
        return;
    }

    try {
        const formData = new FormData();
        formData.append("nombre", hotel.value.nombre);
        formData.append("direccion", hotel.value.direccion);
        formData.append("descripcion", hotel.value.descripcion);
        formData.append("estado", hotel.value.estado);
        formData.append("provincia_id", hotel.value.provincia);
        formData.append("categoria_id", hotel.value.categoria);

        // Solo archivos válidos para nuevas imágenes
        imagenFiles.value.forEach((file) => {
            if (file instanceof File) {
                formData.append("imagenes[]", file);
            }
        });

        // Solo nombres de imágenes guardadas para eliminar
        removedImages.value.forEach((img) => {
            formData.append("removed_images[]", img.split("/").pop());
        });

        let response;
        if (!hotel.value.id) {
            response = await axios.post(url, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.add({
                severity: "success",
                summary: "Hotel agregado",
                life: 3000,
            });
        } else {
            formData.append("_method", "PUT");
            response = await axios.post(`${url}/${hotel.value.id}`, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.add({
                severity: "info",
                summary: "Hotel actualizado",
                life: 3000,
            });
        }

        await fetchHoteles();
        dialog.value = false;
        // limpia valores
        hotel.value = {
            id: null,
            nombre: "",
            direccion: "",
            descripcion: "",
            estado: "",
            provincia: null,
            categoria: null,
            imagenes: [],
        };
        imagenPreviewList.value = [];
        imagenFiles.value = [];
        removedImages.value = [];
        submitted.value = false;
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo guardar el hotel.",
            life: 4000,
        });
    }
};

const confirmDeleteHotel = (h) => {
    hotel.value = { ...h };
    deleteDialog.value = true;
};

const deleteHotel = async () => {
    try {
        await axios.delete(`${url}/${hotel.value.id}`);
        fetchHoteles();
        deleteDialog.value = false;
        toast.add({ severity: "warn", summary: "Hotel eliminado", life: 3000 });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo eliminar el hotel.",
            life: 4000,
        });
    }
};

const hideDialog = () => {
    dialog.value = false;
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
};

const onImageSelect = (event) => {
    for (const file of event.files) {
        if (file instanceof File) {
            imagenFiles.value.push(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenPreviewList.value.push(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }
};

const removeImage = (index) => {
    const removed = imagenPreviewList.value[index];
    if (typeof removed === "string" && removed.startsWith("data:image")) {
        imagenPreviewList.value.splice(index, 1);
        imagenFiles.value.splice(index, 1); // Elimina el archivo correspondiente
    } else {
        removedImages.value.push(removed);
        imagenPreviewList.value.splice(index, 1);
    }
};

function getEstadoLabel(estado) {
    const found = estados.value.find((e) => e.value === estado);
    return found ? found.label : "";
}

function sanitizeDropdownInput(value) {
    return value.replace(/^\s+/, "").replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, "");
}

function onInputDropdown(field, event) {
    if (typeof event === "string") {
        hotel.value[field] = sanitizeDropdownInput(event);
    }
}
function onFilterDropdown(field, event) {
    if (event && event.value) {
        event.value = sanitizeDropdownInput(event.value);
    }
}

const viewImages = (imagenesHotel) => {
    selectedImages.value = (imagenesHotel || []).map(
        (img) => IMAGE_PATH + (typeof img === "string" ? img : img.nombre)
    );
    showImageDialog.value = true;
};
</script>

<template>
    <Head title="Hoteles" />
    <AuthenticatedLayout>
        <Toast />
        <div class="py-6 px-7 mt-10 mx-auto bg-red-100 shadow-md rounded-lg">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4 gap-4">
                <h3 class="text-xl font-bold">Catálogo hoteles</h3>
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto lg:justify-end">
                    <Link :href="route('controlPaisesProvincias')"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300">
                        <FontAwesomeIcon :icon="faGlobe" class="h-4"/>
                        <span class="ml-2 whitespace-nowrap">Control paises</span>
                    </Link>
                    <Link :href="route('catHoteles')"
                        class="bg-blue-500 border border-blue-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300">
                        <FontAwesomeIcon :icon="faTags" class="h-4"/>
                        <span class="ml-2 whitespace-nowrap">Gestionar categorías</span>
                    </Link>
                    <button
                        class="bg-red-500 border border-red-500 p-2 text-sm text-white shadow-md hover:shadow-lg rounded-md hover:-translate-y-1 transition-transform duration-300" @click="openNew">
                        <FontAwesomeIcon :icon="faPlus" class="h-4 w-4 text-white" /><span>&nbsp;Agregar hotel</span>
                    </button>
                </div>
            </div>

            <DataTable
                :value="hoteles"
                v-model:selection="selectedHoteles"
                dataKey="id"
                :filters="filters"
                :paginator="true"
                :rows="10"
                class="overflow-x-auto max-w-full"
                style="display: block; max-width: 84vw"
            >
                <template #header>
                    <InputText
                        v-model="filters['global'].value"
                        placeholder="Buscar..."
                        class="w-full"
                    />
                </template>
                <Column field="nombre" header="Nombre" sortable />
                <Column field="direccion" header="Dirección">
                    <template #body="slotProps">
                        {{ slotProps.data.direccion }}
                    </template>
                </Column>
                <Column field="descripcion" header="Descripción">
                    <template #body="slotProps">
                        {{ slotProps.data.descripcion || "" }}
                    </template>
                </Column>
                <Column field="estado" header="Estado">
                    <template #body="slotProps">
                        {{ getEstadoLabel(slotProps.data.estado) }}
                    </template>
                </Column>
                <Column header="Categoría">
                    <template #body="slotProps">
                        {{ slotProps.data.categoria_hotel?.nombre || "" }}
                    </template>
                </Column>
                <Column field="pais" header="País">
                    <template #body="slotProps">
                        {{ slotProps.data.provincia?.pais?.nombre || "" }}
                    </template>
                </Column>
                <Column header="Provincia">
                    <template #body="slotProps">
                        {{ slotProps.data.provincia?.nombre || "" }}
                    </template>
                </Column>
                <Column header="Acciones" :exportable="false">
                    <template #body="slotProps">
                        <div class="flex gap-2">


                             <button
                                class="text-orange-600 hover:text-orange-900 transition-colors"
                                @click="editHotel(slotProps.data)"
                                title="Editar"
                            >
                                <FontAwesomeIcon :icon="faEdit" class="h-5 w-5" />
                            </button>
                            <button
                                class="text-blue-600 hover:text-blue-900 transition-colors"
                                @click="viewImages(slotProps.data.imagenes)"
                            >
                                <FontAwesomeIcon :icon="faEye" class="h-5 w-5" />
                            </button>

                            <button
                                class="text-red-600 hover:text-red-900 transition-colors"
                                @click="confirmDeleteHotel(slotProps.data)"
                                title="Eliminar"
                            >
                                <FontAwesomeIcon :icon="faTrashCan" class="h-5 w-5" />
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <Dialog
                v-model:visible="dialog"
                :header="btnTitle + ' Hotel'"
                :modal="true"
                :style="{ width: '500px' }"
            >
                <div class="space-y-4">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24">Nombre:</label>
                            <InputText
                                v-model.trim="hotel.nombre"
                                id="nombre"
                                :class="{
                                    'p-invalid': submitted && !hotel.nombre,
                                }"
                                class="flex-1"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.nombre"
                            >El nombre es obligatorio.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="direccion" class="w-24"
                                >Dirección:</label
                            >
                            <textarea
                                v-model.trim="hotel.direccion"
                                id="direccion"
                                :class="{
                                    'p-invalid': submitted && !hotel.direccion,
                                }"
                                class="flex-1"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.direccion"
                            >La dirección es obligatoria.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="descripcion" class="w-24"
                                >Descripción:</label
                            >
                            <textarea
                                v-model.trim="hotel.descripcion"
                                id="descripcion"
                                :class="{
                                    'p-invalid':
                                        submitted && !hotel.descripcion,
                                }"
                                class="flex-1"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.descripcion"
                            >La descripción es obligatoria.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="estado" class="w-24">Estado:</label>
                            <Select
                                v-model="hotel.estado"
                                :options="estados"
                                option-label="label"
                                option-value="value"
                                id="estado"
                                class="flex-1"
                                placeholder="Selecciona un estado"
                                :class="{
                                    'p-invalid': submitted && !hotel.estado,
                                }"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.estado"
                            >El estado es obligatorio.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="categoria" class="w-24"
                                >Categoría:</label
                            >
                            <Select
                                v-model="hotel.categoria"
                                :options="categoriasHoteles"
                                option-label="nombre"
                                option-value="id"
                                id="categoria"
                                class="flex-1"
                                placeholder="Selecciona una categoría"
                                :class="{
                                    'p-invalid': submitted && !hotel.categoria,
                                }"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.categoria"
                            >La categoría es obligatoria.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="provincia" class="w-24"
                                >Provincia:</label
                            >
                            <Select
                                v-model="hotel.provincia"
                                :options="provincias"
                                option-label="nombre"
                                option-value="id"
                                id="provincia"
                                :filter="true"
                                filterPlaceholder="Buscar provincia..."
                                :showClear="true"
                                :class="{
                                    'p-invalid': submitted && !hotel.provincia,
                                }"
                                class="flex-1"
                                placeholder="Selecciona una provincia"
                                @input="onInputDropdown('provincia', $event)"
                                @filter="onFilterDropdown('provincia', $event)"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !hotel.provincia"
                            >La provincia es obligatoria.</small
                        >
                    </div>

                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="imagen" class="w-24">Imágenes:</label>
                            <FileUpload
                                mode="basic"
                                name="imagenes[]"
                                accept="image/*"
                                :auto="true"
                                chooseLabel="Seleccionar imágenes"
                                @select="onImageSelect"
                                :customUpload="true"
                                :multiple="true"
                                class="flex-1 p-button-rounded p-button-warn p-button-md mr-2"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && imagenPreviewList.length === 0"
                            >Al menos una imagen es obligatoria.</small
                        >
                    </div>

                    <div class="flex gap-4 flex-wrap mt-4 ml-28">
                        <div
                            v-for="(img, index) in imagenPreviewList"
                            :key="index"
                            class="relative w-32 h-32"
                        >
                            <img
                                :src="
                                    img.startsWith('data:image')
                                        ? img
                                        : IMAGE_PATH + img
                                "
                                alt="Vista previa"
                                class="w-full h-full object-cover rounded border"
                            />
                            <button
                                @click="removeImage(index)"
                                class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 shadow"
                                style="transform: translate(50%, -50%)"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>



                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <button
                            @click="hideDialog"
                            class="flex items-center border border-green-600 text-green-600 hover:bg-green-50 disabled:border-gray-400 disabled:text-gray-400 font-semibold py-2 px-6 rounded-lg transition-colors duration-200"
                            >
                            <i class="pi pi-times mr-2"></i>
                            Cancelar
                        </button>
                        <button
                            :label="btnTitle"
                            @click="saveOrUpdate"
                            class="bg-red-600 text-white hover:bg-red-700 disabled:bg-gray-400 disabled:text-gray-200 font-semibold py-2 px-6 rounded-lg transition-colors duration-200"
                            >
                            <i class="pi pi-check mr-2"></i>
                            Guardar
                        </button>
                    </div>
                </template>
            </Dialog>

           <Dialog
    v-model:visible="deleteDialog"
    header="Confirmar"
    :modal="true"
    :style="{ width: '350px' }"
>
    <div class="flex items-center gap-3">
        <!-- Ícono de advertencia -->
        <i class="pi pi-exclamation-triangle text-black-500" style="font-size: 1.50rem;"></i>
        <!-- Texto del diálogo -->
        <span>¿Eliminar el hotel <b>{{ hotel.nombre }}</b>?</span>
    </div>
    <template #footer>
        <button
            @click="deleteDialog = false"
            class="border border-green-600 text-green-600 hover:bg-green-50 disabled:border-gray-400 disabled:text-gray-400 font-semibold py-2 px-6 rounded-lg transition-colors duration-200"
        >
            No
        </button>

        <button
            @click="deleteHotel"
            class="bg-red-600 text-white hover:bg-red-700 disabled:bg-gray-400 disabled:text-gray-200 font-semibold py-2 px-6 rounded-lg transition-colors duration-200"
        >
            Sí
        </button>
    </template>
</Dialog>


            <Dialog
    v-model:visible="showImageDialog"
    header="Imágenes del hotel"
    :modal="true"
    :style="{ width: '700px' }"
>
    <div
        v-if="selectedImages.length"
        class="flex flex-col items-center justify-center"
    >
        <!-- Carousel for images -->
        <Carousel
            :value="selectedImages"
            :numVisible="1"
            :numScroll="1"
            :circular="true"
            v-model:page="carouselIndex"
            class="w-full"
            :showIndicators="selectedImages.length > 1"
            :showNavigators="selectedImages.length > 1"
            style="max-width: 610px"
        >
            <template #item="slotProps">
                <div class="flex justify-center items-center w-full h-96">
                    <img
                        :src="slotProps.data"
                        alt="Imagen hotel"
                        class="w-auto h-full max-h-96 object-contain rounded shadow"
                    />
                </div>
            </template>
        </Carousel>
    </div>

    <div v-else class="text-center text-gray-500 py-8">
        No hay imágenes para este hotel.
    </div>

    <template #footer>
        <!-- Contenedor centrado -->
        <div class="flex justify-center w-full">
            <button
                @click="showImageDialog = false"
                class="border border-green-600 text-green-600 hover:bg-green-50 disabled:border-gray-400 disabled:text-gray-400 font-semibold py-2 px-6 rounded-lg transition-colors duration-200"
            >
                Cerrar
            </button>
        </div>
    </template>
</Dialog>

        </div>
    </AuthenticatedLayout>
</template>
