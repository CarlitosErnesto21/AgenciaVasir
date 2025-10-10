<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import { useToast } from "primevue/usetoast";
import { FilterMatchMode } from "@primevue/core/api";
import Calendar from "primevue/calendar";
import Carousel from "primevue/carousel";

const toast = useToast();
const aerolineas = ref([]);
const aerolinea = ref({
    id: null,
    nombre: "",
    fecha: null,
    imagenes: [],
});
const imagenPreviewList = ref([]);
const imagenFiles = ref([]);
const removedImages = ref([]);
const selectedAerolineas = ref();
const dialog = ref(false);
const deleteDialog = ref(false);
const submitted = ref(false);
const url = "/api/aerolineas";
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const btnTitle = ref("Guardar");
const showImageDialog = ref(false);
const selectedImages = ref([]);
const IMAGE_PATH = "/storage/";

onMounted(() => {
    fetchAerolineas();
});

const fetchAerolineas = async () => {
    try {
        const response = await axios.get(url);
        // Mapear imágenes a solo nombres
        aerolineas.value = response.data.map((aerolinea) => ({
            ...aerolinea,
            imagenes: (aerolinea.imagenes || []).map((img) =>
                typeof img === "string" ? img : img.nombre
            ),
        }));
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudieron cargar las aerolíneas",
            life: 4000,
        });
    }
};

const openNew = () => {
    aerolinea.value = {
        id: null,
        nombre: "",
        fecha: null,
        imagenes: [],
    };
    imagenPreviewList.value = [];
    imagenFiles.value = [];
    removedImages.value = [];
    submitted.value = false;
    btnTitle.value = "Guardar";
    dialog.value = true;
};

const editAerolinea = (a) => {
    aerolinea.value = { ...a };
    imagenFiles.value = [];
    btnTitle.value = "Actualizar";
    dialog.value = true;
    imagenPreviewList.value = Array.isArray(a.imagenes)
        ? a.imagenes.map((img) => (typeof img === "string" ? img : img.nombre))
        : [];
    removedImages.value = [];
};

const saveOrUpdate = async () => {
    submitted.value = true;

    if (!aerolinea.value.nombre || !aerolinea.value.fecha) {
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
        formData.append("nombre", String(aerolinea.value.nombre));

        let fechaFormateada = aerolinea.value.fecha;
        if (
            fechaFormateada &&
            typeof fechaFormateada === "object" &&
            typeof fechaFormateada.toISOString === "function"
        ) {
            fechaFormateada = fechaFormateada.toISOString().split("T")[0];
        } else if (typeof fechaFormateada === "string") {
            fechaFormateada = fechaFormateada;
        } else {
            fechaFormateada = "";
        }
        formData.append("fecha", fechaFormateada);

        imagenFiles.value.forEach((img) => {
            formData.append("imagenes[]", img);
        });

        removedImages.value.forEach((img) => {
            formData.append("removed_images[]", img.split("/").pop());
        });

        let response;
        if (!aerolinea.value.id) {
            response = await axios.post(url, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.add({
                severity: "success",
                summary: "Aerolínea agregada",
                life: 3000,
            });
        } else {
            formData.append("_method", "PUT");
            response = await axios.post(
                `${url}/${aerolinea.value.id}`,
                formData,
                { headers: { "Content-Type": "multipart/form-data" } }
            );
            toast.add({
                severity: "info",
                summary: "Aerolínea actualizada",
                life: 3000,
            });
        }

        await fetchAerolineas();
        dialog.value = false;
        aerolinea.value = {
            id: null,
            nombre: "",
            fecha: null,
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
            detail: "No se pudo guardar la aerolínea.",
            life: 4000,
        });
    }
};

const confirmDeleteAerolinea = (a) => {
    aerolinea.value = { ...a };
    deleteDialog.value = true;
};

const deleteAerolinea = async () => {
    try {
        await axios.delete(`${url}/${aerolinea.value.id}`);
        await fetchAerolineas();
        deleteDialog.value = false;
        toast.add({
            severity: "success",
            summary: "Aerolínea eliminada",
            life: 3000,
        });
    } catch (err) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No se pudo eliminar la aerolínea.",
            life: 3000,
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
        imagenFiles.value.splice(index, 1);
    } else {
        removedImages.value.push(removed);
        imagenPreviewList.value.splice(index, 1);
    }
};

const viewImages = (imagenesAerolinea) => {
    selectedImages.value = (imagenesAerolinea || []).map(
        (img) => IMAGE_PATH + (typeof img === "string" ? img : img.nombre)
    );
    showImageDialog.value = true;
};
</script>

<template>
    <Head title="Aerolíneas" />
    <AuthenticatedLayout>
        <Toast />
        <div class="py-6 px-6 mt-10 mx-auto bg-red-50 shadow-md rounded-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Gestión de Aerolíneas</h3>
                <Button
                    label="Agregar aerolínea"
                    icon="pi pi-plus"
                    class="p-button-sm p-button-danger"
                    @click="openNew"
                />
            </div>

            <DataTable
                :value="aerolineas"
                v-model:selection="selectedAerolineas"
                dataKey="id"
                :filters="filters"
                :paginator="true"
                :rows="5"
                :rowsPerPageOptions="[5, 10, 15]"
                class="w-full table-fixed"
            >
                <template #header>
                    <div class="flex justify-between items-center w-full">
                        <InputText
                            v-model="filters['global'].value"
                            placeholder="Buscar..."
                            class="w-full mr-4"
                        />
                    </div>
                </template>
                <Column
                    field="nombre"
                    header="Nombre"
                    sortable
                    :style="{ width: '30%' }"
                />
                <Column field="fecha" header="Fecha" :style="{ width: '30%' }">
                    <template #body="slotProps">
                        {{
                            slotProps.data.fecha
                                ? new Date(
                                      slotProps.data.fecha
                                  ).toLocaleDateString("es-ES", {
                                      day: "2-digit",
                                      month: "2-digit",
                                      year: "numeric",
                                  })
                                : ""
                        }}
                    </template>
                </Column>
                <Column
                    header="Acciones"
                    :exportable="false"
                    :style="{ width: '20%', textAlign: 'left' }"
                >
                    <template #body="slotProps">
                        <div class="flex justify-letf gap-2 w-full">
                            <Button
                                icon="pi pi-eye"
                                class="p-button-rounded p-button-info p-button-md mr-2"
                                @click="viewImages(slotProps.data.imagenes)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                class="p-button-rounded p-button-warn p-button-md mr-2"
                                @click="editAerolinea(slotProps.data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-danger p-button-md"
                                @click="confirmDeleteAerolinea(slotProps.data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <Dialog
                v-model:visible="dialog"
                :header="btnTitle + ' Aerolínea'"
                :modal="true"
                :style="{ width: '400px' }"
            >
                <div class="p-fluid space-y-6">
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="nombre" class="w-24">Nombre:</label>
                            <InputText
                                v-model.trim="aerolinea.nombre"
                                id="nombre"
                                :class="{
                                    'p-invalid': submitted && !aerolinea.nombre,
                                }"
                                class="flex-1"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !aerolinea.nombre"
                            >El nombre es obligatorio.</small
                        >
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="fecha" class="w-24"
                                >Fecha fundación:</label
                            >
                            <Calendar
                                v-model="aerolinea.fecha"
                                id="fecha"
                                :showIcon="true"
                                dateFormat="yy-mm-dd"
                                :class="{
                                    'p-invalid': submitted && !aerolinea.fecha,
                                }"
                                class="flex-1"
                                :manualInput="false"
                            />
                        </div>
                        <small
                            class="text-red-500 ml-28"
                            v-if="submitted && !aerolinea.fecha"
                            >La fecha es obligatoria.</small
                        >
                    </div>
                    <div class="w-full flex flex-col">
                        <div class="flex items-center gap-4">
                            <label for="imagenes" class="w-24">Imágenes:</label>
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
                            class="relative w-24 h-24 group"
                        >
                            <img
                                :src="img.startsWith('data:image') ? img : IMAGE_PATH + img"
                                alt="Vista previa"
                                class="w-full h-full object-cover rounded border"
                            />
                            <button
                                @click="removeImage(index)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 shadow"
                                style="z-index: 2"
                            >
                                <i class="pi pi-times text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-4 w-full">
                        <Button
                            label="Cancelar"
                            icon="pi pi-times"
                            class="p-button-rounded p-button-danger p-button-md mr-2"
                            text
                            @click="hideDialog"
                        />
                        <Button
                            :label="btnTitle"
                            icon="pi pi-check"
                            class="p-button-rounded p-button-warn p-button-md mr-2"
                            @click="saveOrUpdate"
                        />
                    </div>
                </template>
            </Dialog>

            <Dialog
                v-model:visible="deleteDialog"
                header="Confirmar"
                :modal="true"
                :style="{ width: '350px' }"
            >
                <span
                    >¿Eliminar la aerolínea <b>{{ aerolinea.nombre }}</b
                    >?</span
                >
                <template #footer>
                    <Button
                        label="No"
                        icon="pi pi-times"
                        text
                        @click="deleteDialog = false"
                        class="p-button-rounded p-button-warn p-button-md mr-2"
                    />
                    <Button
                        label="Sí"
                        icon="pi pi-check"
                        severity="danger"
                        @click="deleteAerolinea"
                    />
                </template>
            </Dialog>

            <!-- Modal para ver imagen grande y navegar -->
            <Dialog
                v-model:visible="showImageDialog"
                header="Imágenes de la aerolínea"
                :modal="true"
                :style="{ width: '700px' }"
            >
                <div
                    v-if="selectedImages.length"
                    class="flex flex-col items-center justify-center"
                >
                    <Carousel
                        :value="selectedImages"
                        :numVisible="1"
                        :numScroll="1"
                        :circular="true"
                        class="w-full"
                        :showIndicators="selectedImages.length > 1"
                        :showNavigators="selectedImages.length > 1"
                        style="max-width: 610px"
                    >
                        <template #item="slotProps">
                            <div
                                class="flex justify-center items-center w-full h-96"
                            >
                                <img
                                    :src="slotProps.data"
                                    alt="Imagen aerolínea"
                                    class="w-auto h-full max-h-96 object-contain rounded shadow"
                                />
                            </div>
                        </template>
                    </Carousel>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    No hay imágenes para esta aerolínea.
                </div>
                <template #footer>
                    <Button
                        label="Cerrar"
                        icon="pi pi-times"
                        class="p-button-rounded-md p-button-danger"
                        @click="showImageDialog = false"
                    />
                </template>
            </Dialog>
        </div>
    </AuthenticatedLayout>
</template>
