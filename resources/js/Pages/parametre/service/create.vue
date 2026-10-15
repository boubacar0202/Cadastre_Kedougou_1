<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link as InertiaLink } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import DeleteModal from "@/Components/DeleteModal.vue";
import { ref, computed } from "vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import { toast } from "@/Components/AppToast";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    services: {
        type: Array,
        default: () => [],
    },
});

/* ------------------------------------------------------------------ */
/* 🔹 Refs                                                             */
/* ------------------------------------------------------------------ */
const fichierPDF = ref(null);
const rowRefs = ref({});
const refTitre = ref("");
const refService = ref("");
const selectedService = ref(null);
const showDeleteModal = ref(false);
const deleteCode = ref("");
const loadingDelete = ref(false);
const fileInputKey = ref(0); // pour reset l'input file après submit

/* ------------------------------------------------------------------ */
/* 🔹 Normalisation (recherche)                                        */
/* ------------------------------------------------------------------ */
const normalize = (str) =>
    str?.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "") ?? "";

/* ------------------------------------------------------------------ */
/* 🔹 Formulaire                                                       */
/* ------------------------------------------------------------------ */
const form = useForm({
    txt_numeroNote: "",
    txt_typeNote: "",
    txt_titreNote: "",
    fichierPDF: null,
});

/* ------------------------------------------------------------------ */
/* 🔹 Upload PDF                                                       */
/* ------------------------------------------------------------------ */
function handleFileUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.type !== "application/pdf") {
        toast.error("Veuillez sélectionner un fichier PDF valide.");
        event.target.value = "";
        return;
    }

    if (file.size > 100 * 1024 * 1024) {
        toast.error("Le fichier dépasse 100 Mo.");
        event.target.value = "";
        return;
    }

    fichierPDF.value = file;
    form.fichierPDF = file;
}

/* ------------------------------------------------------------------ */
/* 🔹 Liste des types uniques (pour le filtre)                         */
/* ------------------------------------------------------------------ */
const uniqueServices = computed(() => {
    return [...new Set(props.services.map((s) => s.txt_typeNote).filter(Boolean))];
});

/* ------------------------------------------------------------------ */
/* 🔹 Filtrage + tri                                                   */
/* ------------------------------------------------------------------ */
const filtereServices = computed(() => {
    const searchService = normalize(refService.value);
    const searchTitre = normalize(refTitre.value);

    return props.services
        .filter((service) => {
            const matchService = searchService
                ? normalize(service.txt_typeNote).includes(searchService)
                : true;

            const matchTitre = searchTitre
                ? normalize(service.txt_titreNote).includes(searchTitre)
                : true;

            return matchService && matchTitre;
        })
        .sort((a, b) => {
            const numA = parseInt(a.txt_numeroNote?.split("/")[0] || 0, 10);
            const numB = parseInt(b.txt_numeroNote?.split("/")[0] || 0, 10);
            return numA - numB;
        });
});

/* ------------------------------------------------------------------ */
/* 🔹 Compteur                                                         */
/* ------------------------------------------------------------------ */
const totalService = computed(() => filtereServices.value.length);

/* ------------------------------------------------------------------ */
/* 🔹 Submit (création)                                                */
/* ------------------------------------------------------------------ */
const submitForm = () => {
    form.post(route("parametre.service.store"), {
        forceFormData: true, // ✅ ESSENTIEL pour l'upload PDF
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Enregistré avec succès");
            form.reset();
            fichierPDF.value = null;
            fileInputKey.value++; // reset l'input file
        },
        onError: (errors) => {
            Object.values(errors).forEach((e) => toast.error(e));
        },
    });
};

/* ------------------------------------------------------------------ */
/* 🔹 Suppression                                                      */
/* ------------------------------------------------------------------ */
const supprimerService = (service) => {
    if (!service?.id) return;

    selectedService.value = service;
    deleteCode.value = "";
    showDeleteModal.value = true;
};

const confirmerSuppression = async ({ adminCode } = {}) => {
    if (!selectedService.value || loadingDelete.value) return;

    if (!adminCode || String(adminCode).trim() === "") {
        toast.error("Veuillez entrer le code administrateur.");
        return;
    }

    try {
        loadingDelete.value = true;

        const url = route("parametre.service.destroy", selectedService.value.id);
        const { data } = await axios.delete(url, { data: { code: String(adminCode).trim() } });

        if (data.success) {
            toast.success(data.message);
            showDeleteModal.value = false;
            router.reload({ preserveState: false, preserveScroll: true });
        } else {
            toast.error(data.message || "Code invalide.");
        }
    } catch (error) {
        if (error.response?.status === 403) {
            toast.error(
                error.response.data.message ||
                    "Code invalide, veuillez contacter l'administrateur."
            );
        } else {
            toast.error("Impossible de supprimer l'enregistrement.");
        }
    } finally {
        loadingDelete.value = false;
    }
};
</script>

<template>
    <Head title="Recueil de textes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Recueil de textes
            </h2>
        </template>

        <!-- ============================================================ -->
        <!-- 🔹 Formulaire d'enregistrement                                -->
        <!-- ============================================================ -->
        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Formulaire d'enregistrement des textes administratifs
                            </h1>
                        </div>

                        <DeleteModal
                            :show="showDeleteModal"
                            :itemName="selectedService?.txt_numeroNote || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteModal = $event"
                            @confirm="confirmerSuppression"
                        />

                        <form @submit.prevent="submitForm" enctype="multipart/form-data">
                            <div class="p-6">
                                <h5 class="text-lg text-primary-txt font-bold mb-4">
                                    Références du texte
                                </h5>
                                <div class="mb-2">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-2">
                                        <BaseSelect
                                            class="sm:col-span-1"
                                            id="txt_typeNote"
                                            v-model="form.txt_typeNote"
                                            label="Types"
                                            :options="[
                                                { value: 'Loi', label: 'Loi' },
                                                { value: 'Décret', label: 'Décret' },
                                                { value: 'Arrêté', label: 'Arrêté' },
                                                { value: 'Note de service', label: 'Note de service' },
                                                { value: 'Circulaire', label: 'Circulaire' },
                                                { value: 'Ordonnance', label: 'Ordonnance' },
                                                { value: 'Note cadre', label: 'Note cadre' },
                                                { value: 'Code', label: 'Code' },
                                                { value: 'Documents', label: 'Documents' },
                                                { value: 'Autres', label: 'Autres' },
                                            ]"
                                        />

                                        <BaseInput
                                            class="sm:col-span-1"
                                            id="txt_numero"
                                            v-model="form.txt_numeroNote"
                                            label="Numéro"
                                            type="text"
                                        />

                                        <BaseInput
                                            class="sm:col-span-3"
                                            id="txt_titre"
                                            v-model="form.txt_titreNote"
                                            label="Titre"
                                            type="text"
                                        />

                                        <BaseInput
                                            :key="fileInputKey"
                                            type="file"
                                            accept="application/pdf"
                                            class="sm:col-span-1"
                                            label="Importer le Fichier PDF"
                                            name="fichierPDF"
                                            @change="handleFileUpload"
                                        />
                                    </div>
                                </div>

                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn
                                        type="submit"
                                        :loading="form.processing"
                                        no-shadow
                                        no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark
                                               to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                               focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50
                                               dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5
                                               py-2.5 text-center me-2 mb-1 mt-2"
                                        size="medium"
                                    >
                                        Enregistrer
                                    </MazBtn>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 🔹 Liste des enregistrements                                  -->
        <!-- ============================================================ -->
        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Liste des textes</h1>
                        </div>
                        <div class="p-6">
                            <div class="p-4 bg-primary-form border border-primary-only rounded-xl">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h1 class="text-lg text-primary-txt font-bold">
                                        Nombre d'enregistrements
                                        <span v-if="totalService > 0" class="text-primary-txt">
                                            ({{ totalService }})
                                        </span>
                                        <span v-else class="text-red-600">
                                            Aucun enregistrement
                                        </span>
                                    </h1>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <BaseInput
                                            v-model="refTitre"
                                            type="search"
                                            id="default-search"
                                            placeholder="Rechercher Titre"
                                            wrapper-class="w-64"
                                        />
                                        <BaseSelect
                                            v-model="refService"
                                            :options="uniqueServices"
                                            placeholder="Tous"
                                            wrapper-class="w-44"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="max-h-[600px] overflow-auto rounded-xl border border-primary-only mt-4">
                                <div class="container min-w-[1500px]">
                                    <div class="card">
                                        <div class="card-body">
                                            <table class="table-auto w-full text-sm text-left text-primary-txt">
                                                <thead class="sticky top-0 z-10 text-center whitespace-nowrap">
                                                    <tr class="h-10">
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">N°</th>
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Type</th>
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Numero</th>
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Titre</th>
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Fichier PDF</th>
                                                        <th scope="col" class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Actions</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr
                                                        v-for="(service, index) in filtereServices"
                                                        :key="service.id"
                                                        :ref="(el) => (rowRefs[service.txt_numeroNote] = el)"
                                                        class="h-10 bg-white text-primary-txt text-center text-sm whitespace-nowrap hover:bg-primary-only"
                                                    >
                                                        <td class="sticky left-0 z-0 bg-white px-4 py-2 text-center text-primary-txt border border-primary-only font-bold whitespace-nowrap">
                                                            {{ index + 1 }}
                                                        </td>
                                                        <td class="px-2 py-2 text-center whitespace-nowrap border border-primary-only">
                                                            {{ service.txt_typeNote || "-" }}
                                                        </td>
                                                        <td class="px-2 py-2 text-center whitespace-nowrap border border-primary-only">
                                                            {{ service.txt_numeroNote || "-" }}
                                                        </td>
                                                        <td class="px-2 py-2 text-center whitespace-nowrap border border-primary-only">
                                                            {{ service.txt_titreNote || "-" }}
                                                        </td>
                                                        <td class="px-2 py-2 text-center whitespace-nowrap border border-primary-only">
                                                            <a
                                                                v-if="service.fichierPDF"
                                                                :href="`/storage/${service.fichierPDF}`"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="text-primary font-semibold underline"
                                                            >
                                                                PDF
                                                            </a>
                                                            <span v-else class="text-primary-txt/60 italic">Aucun PDF</span>
                                                        </td>
                                                        <td class="px-4 py-2 text-center whitespace-nowrap border border-primary-only">
                                                            <!-- ✅ InertiaLink correctement importé + route() -->
                                                            <InertiaLink :href="route('parametre.service.edit', service.id)">
                                                                <MazBtn
                                                                    size="medium"
                                                                    aria-label="Modifier"
                                                                    title="Modifier"
                                                                    class="h-6 text-white bg-gradient-to-r from-primary via-primary-dark to-primary font-medium rounded-lg text-xs px-3 py-1"
                                                                >
                                                                    ✏️
                                                                </MazBtn>
                                                            </InertiaLink>

                                                            <MazBtn
                                                                size="medium"
                                                                aria-label="Supprimer"
                                                                title="Supprimer"
                                                                @click="supprimerService(service)"
                                                                class="h-6 text-white bg-primary-txt font-medium rounded-lg text-xs px-3 py-1"
                                                            >
                                                                🗑️
                                                            </MazBtn>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>