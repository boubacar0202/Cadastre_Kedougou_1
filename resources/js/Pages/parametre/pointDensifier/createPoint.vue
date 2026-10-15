<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import DeleteModal from "@/Components/DeleteModal.vue";
import { ref, computed, watch, watchEffect, onMounted } from "vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import { toast } from "@/Components/AppToast";
import { Inertia } from '@inertiajs/inertia';
import axios from 'axios';
import proj4 from 'proj4'; // ⬅️ IMPORT POUR LA CONVERSION UTM

const props = defineProps({
    point_densifiers: {
        type: Array,
        default: () => []
    }
});

// 🔹 Définition des projections UTM (zone 28N) et WGS84
const utm28n = '+proj=utm +zone=28 +ellps=WGS84 +datum=WGS84 +units=m +no_defs';
const wgs84 = '+proj=longlat +datum=WGS84 +no_defs';

/**
 * Convertit des coordonnées UTM (mètres) en degrés décimaux (WGS84).
 * Si les valeurs sont déjà en degrés, elles sont conservées telles quelles.
 */
function convertToWgs84(est, nord) {
    if (!est || !nord) return null;

    const e = parseFloat(est);
    const n = parseFloat(nord);

    // Si les valeurs sont dans une plage de degrés décimaux valides, on les garde
    if (Math.abs(e) <= 180 && Math.abs(n) <= 90) {
        return { lat: n, lon: e };
    }

    // Sinon, conversion UTM → WGS84
    try {
        const [lon, lat] = proj4(utm28n, wgs84, [e, n]);
        return { lat, lon };
    } catch (error) {
        console.error('Erreur de conversion UTM → WGS84 :', error);
        return null;
    }
}

// 🔹 refs pour la recherche et le tableau
const fichierPDFPoint = ref(null);
const rowRefs = ref({});
const refTitre = ref('');      // recherche par description
const refPoint = ref('');      // recherche par nom du point (filtre)
const selectedPoint = ref(null);
const showDeleteModal = ref(false);
const deleteCode = ref('');
const loadingDelete = ref(false);

// 🔹 Sélections localisation
const slt_departement = ref(null);
const slt_arrondissement = ref(null);
const slt_commune = ref(null);
const departements = ref([]);
const arrondissements = ref([]);
const communes = ref([]);

// 🔹 normalisation (recherche)
const normalize = (str) =>
    str?.toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

// 🔹 formulaire
const form = useForm({
    txt_nomPoint: "",
    txt_dateCreationPoint: "",
    slt_departement: null,
    slt_arrondissement: null,
    slt_commune: null,
    txt_descriptionPoint: "",
    txt_situationPoint: "",
    txt_estPoint: "",
    txt_nordPoint: "",
    txt_hauteurPoint: "",
    fichierPDFPoint: null,
});

// 🔍 LOG pour déboguer
console.log('📊 point_densifiers reçus :', props.point_densifiers);
console.log('📊 Nombre :', props.point_densifiers?.length);
if (props.point_densifiers?.length) {
    console.log('🔑 Clés du premier point :', Object.keys(props.point_densifiers[0]));
}

// 🔹 upload PDF
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.type !== "application/pdf") {
        toast.error("Veuillez sélectionner un fichier PDF valide.");
        return;
    }
    if (file.size > 100 * 1024 * 1024) {
        toast.error("Le fichier dépasse 100 Mo.");
        return;
    }
    fichierPDFPoint.value = file;
    form.fichierPDFPoint = file;
}

// 🔹 liste unique des noms de points pour le filtre
const uniquePoints = computed(() => {
    if (!props.point_densifiers || !props.point_densifiers.length) return [];
    return [...new Set(
        props.point_densifiers
            .map(p => p.txt_nomPoint)
            .filter(Boolean)
    )];
});

// 🔹 filtrage + tri + ajout des coordonnées converties
const filterePoints = computed(() => {
    if (!props.point_densifiers || !props.point_densifiers.length) return [];

    const searchNom = normalize(refPoint.value || '');
    const searchDesc = normalize(refTitre.value || '');

    return props.point_densifiers
        .filter(point => {
            const matchNom = searchNom
                ? normalize(point.txt_nomPoint || '').includes(searchNom)
                : true;
            const matchDesc = searchDesc
                ? normalize(point.txt_descriptionPoint || '').includes(searchDesc)
                : true;
            return matchNom && matchDesc;
        })
        .sort((a, b) => (a.id || 0) - (b.id || 0))
        .map(point => {
            // Conversion UTM → WGS84
            const coords = convertToWgs84(point.txt_estPoint, point.txt_nordPoint);
            return { ...point, coords };
        });
});

// 🔹 compteur total
const totalPoint = computed(() => filterePoints.value.length);

// 🔹 chargement des localisations
const fetchDepartements = async () => {
    try {
        const response = await axios.get("/departements");
        departements.value = response.data.departements;
    } catch (error) {
        console.error("Erreur départements :", error);
        departements.value = [];
    }
};

const fetchArrondissements = async () => {
    if (!slt_departement.value) {
        arrondissements.value = [];
        slt_arrondissement.value = null;
        return;
    }
    try {
        const response = await axios.get(`/arrondissements/${slt_departement.value}`);
        arrondissements.value = response.data.arrondissements;
    } catch (error) {
        console.error("Erreur arrondissements :", error);
        arrondissements.value = [];
    }
};

const fetchCommunes = async () => {
    if (!slt_arrondissement.value) {
        communes.value = [];
        slt_commune.value = null;
        return;
    }
    try {
        const response = await axios.get(`/communes/${slt_arrondissement.value}`);
        communes.value = response.data.communes;
    } catch (error) {
        console.error("Erreur communes :", error);
        communes.value = [];
    }
};

// 🔹 watchers
watch(slt_departement, fetchArrondissements);
watch(slt_arrondissement, fetchCommunes);

// 🔹 synchronisation formulaire
watchEffect(() => {
    form.slt_departement = slt_departement.value;
    form.slt_arrondissement = slt_arrondissement.value;
    form.slt_commune = slt_commune.value;
});

// 🔹 submit
const submitForm = () => {
    form.slt_departement = slt_departement.value || "";
    form.slt_arrondissement = slt_arrondissement.value || "";
    form.slt_commune = slt_commune.value || "";

    form.post(route('parametre.pointDensifier.storePoint'), {
        forceFormData: true,
        onSuccess: () => {
            toast.success("✅ Point enregistré avec succès");
            Inertia.visit(route("parametre.pointDensifier.createPoint"), { replace: true });
        },
        onError: (errors) => {
            Object.values(errors).forEach(e => toast.error(e));
        }
    });
};

// 🔹 suppression
const supprimerPoint = (point) => {
    if (!point || !point.id) return;
    selectedPoint.value = point;
    deleteCode.value = "";
    showDeleteModal.value = true;
};

const confirmerSuppression = async (code) => {
    if (!selectedPoint.value || loadingDelete.value) return;

    if (!code?.trim()) {
        toast.error("Veuillez entrer le code administrateur.");
        return;
    }

    try {
        loadingDelete.value = true;
        const url = route('parametre.pointDensifier.destroy', selectedPoint.value.id);
        const { data } = await axios.delete(url, { data: { code } });

        if (data.success) {
            toast.success(data.message);
            showDeleteModal.value = false;
            Inertia.reload({ preserveState: false });
        } else {
            toast.error(data.message || "Code invalide.");
        }
    } catch (error) {
        if (error.response?.status === 403) {
            toast.error(error.response.data.message || "Code invalide, veuillez contacter l'administrateur.");
        } else {
            toast.error("Impossible de supprimer l'enregistrement.");
        }
    } finally {
        loadingDelete.value = false;
    }
};

onMounted(() => {
    fetchDepartements();
});

</script>

<template>
    <Head title="Points densifiés" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Création de Points Densifiés
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md sm:rounded-xl">
                        <!-- En-tête du formulaire -->
                        <div class="p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Formulaire d'enregistrement des points densifiés</h1>
                        </div>

                        <!-- Modal de suppression -->
                        <DeleteModal
                            :show="showDeleteModal"
                            :itemName="selectedPoint?.txt_nomPoint || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteModal = $event"
                            @confirm="confirmerSuppression"
                        />

                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm">
                            <div class="p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Nom du Point"
                                        name="txt_nomPoint"
                                        autocomplete="off"
                                        v-model="form.txt_nomPoint"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Date de création"
                                        name="txt_dateCreationPoint"
                                        type="date"
                                        v-model="form.txt_dateCreationPoint"
                                    />
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Département"
                                        name="slt_departement"
                                        :options="departements"
                                        optionLabel="slt_departement"
                                        optionValue="id"
                                        v-model="slt_departement"
                                    />
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Arrondissement"
                                        :options="arrondissements"
                                        optionLabel="slt_arrondissement"
                                        optionValue="id"
                                        v-model="slt_arrondissement"
                                    />
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Commune"
                                        :options="communes"
                                        optionLabel="slt_commune"
                                        optionValue="id"
                                        v-model="slt_commune"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Description"
                                        name="txt_descriptionPoint"
                                        v-model="form.txt_descriptionPoint"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Situation"
                                        name="txt_situationPoint"
                                        v-model="form.txt_situationPoint"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Est (X)"
                                        name="txt_estPoint"
                                        v-model="form.txt_estPoint"
                                        autocomplete="off"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Nord (Y)"
                                        name="txt_nordPoint"
                                        v-model="form.txt_nordPoint"
                                        autocomplete="off"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Hauteur (Z)"
                                        name="txt_hauteurPoint"
                                        v-model="form.txt_hauteurPoint"
                                        autocomplete="off"
                                    />
                                    <BaseInput
                                        class="sm:col-span-2"
                                        label="Documents afférants (PDF)"
                                        name="fichierPDFPoint"
                                        type="file"
                                        accept="application/pdf"
                                        @change="handleFileUpload"
                                    />
                                </div>

                                <!-- Bouton de soumission -->
                                <div class="sm:col-span-6 flex justify-center mt-6">
                                    <MazBtn
                                        type="submit" no-shadow no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark
                                            to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                            focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
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

        <!-- Tableau des points -->
        <div class="py-12">
            <div class="w-full flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="mx-auto max-w-8xl sm:px-8 lg:px-12 mt-1 mb-1"><br>
                            <div class="relative overflow-x-auto p-3 border-b bg-primary-form sm:rounded-xl mt-1">
                                <div class="flex justify-between items-center">
                                    <h1 class="text-1xl text-primary-txt font-semibold">
                                        Nombre d'enregistrements
                                        <span v-if="totalPoint > 0" class="text-primary-txt">
                                            ({{ totalPoint }})
                                        </span>
                                        <span v-else class="text-red-600">
                                            Aucun enregistrement
                                        </span>
                                    </h1>
                                    <div class="flex items-start space-x-2">
                                        <div>
                                            <input
                                                v-model="refTitre"
                                                type="search"
                                                aria-label="Rechercher"
                                                class="h-9 block w-64 rounded-xl bg-white px-3 py-1.5 text-base text-primary-txt
                                                    border border-primary-menu placeholder:text-primary-txt/50
                                                    focus:outline focus:outline-2 focus:outline-primary sm:text-sm/6"
                                                placeholder="Rechercher Description"
                                            />
                                        </div>
                                        <div>
                                            <select
                                                v-model="refPoint"
                                                class="h-8 block w-30 rounded-xl bg-white px-3 py-1.5 text-base text-primary-txt
                                                    border border-primary-menu placeholder:text-primary-txt/50
                                                    focus:outline focus:outline-2 focus:outline-primary sm:text-sm/6"
                                            >
                                                <option value="">Tous</option>
                                                <option
                                                    v-for="point in uniquePoints"
                                                    :key="point"
                                                    :value="point"
                                                >
                                                    {{ point }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="max-h-[700px] overflow-y-auto shadow-md sm:rounded-xl mt-2">
                                <table class="table table-auto w-full text-sm text-left rtl:text-right text-primary-txt">
                                    <thead class="sticky top-0 z-10 text-xs text-primary-txt font_bold uppercase text-center
                                            bg-primary-footer bg-gray-50 whitespace-nowrap">
                                        <tr class="h-10">
                                            <th class="px-4 py-2 border-l border-primary-only">N°</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Nom</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Département</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Arrondissement</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Commune</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Description</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Situation</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Est (X)</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Nord (Y)</th>
                                            <th class="px-4 py-2 border-l border-primary-only">Hauteur</th>
                                            <th class="px-4 py-2 border-l border-primary-only">PDF</th> 
                                            <th class="px-4 py-2 border-l border-primary-only">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(point, index) in filterePoints" :key="point.id"
                                            class="h-10 bg-white text-primary-txt border border-primary-footer text-center text-sm hover:bg-primary-form">
                                            <td class="px-2 py-1 border border-primary-only font-bold">{{ index + 1 }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_nomPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.departement?.slt_departement || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.arrondissement?.slt_arrondissement || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.commune?.slt_commune || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_descriptionPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_situationPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_estPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_nordPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">{{ point.txt_hauteurPoint || '-' }}</td>
                                            <td class="px-2 py-1 border border-primary-only">
                                                <a v-if="point.fichierPDF" :href="`/storage/${point.fichierPDF}`" target="_blank"
                                                    class="text-primary font-bold underline">
                                                    Voir PDF
                                                </a>
                                                <span v-else class="text-primary-txt/60 italic">Aucun</span>
                                            </td> 
                                            <td class="px-2 py-1 border border-primary-only whitespace-nowrap">
                                                <Link :href="`/parametre/pointDensifier/edit/${point.id}`">
                                                    <MazBtn
                                                        class="h-6 w-6 text-white bg-gradient-to-r from-green-400 via-green-500
                                                            to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                            focus:ring-green-300 font-medium rounded-lg text-xs px-3 py-1 me-1"
                                                    >
                                                        ✏️
                                                    </MazBtn>
                                                </Link>
                                                <MazBtn
                                                    @click="() => supprimerPoint(point)"
                                                    class="h-6 w-6 text-white bg-gradient-to-r from-red-500 via-red-600
                                                        to-red-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                        focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-1"
                                                >
                                                    🗑️
                                                </MazBtn>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div><br>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>