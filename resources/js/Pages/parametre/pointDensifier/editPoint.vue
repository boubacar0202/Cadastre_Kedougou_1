<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import AppCodeAccee from "@/Components/AppCodeAccee.vue";
import { onMounted, ref, watch, computed } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";
import { Inertia } from '@inertiajs/inertia';
// Importer route si nécessaire (selon votre configuration)
// import { route } from 'ziggy-js';

const props = defineProps({
    point_densifiers: Object,
    departements: Array,
    arrondissements: Array,
    communes: Array
});

// --- État local ---
const formVisible = ref(false);
const showModal = ref(true);
const isLoading = ref(false);
const isSubmitting = ref(false);

const hasPoint = computed(() => !!props.point_densifiers?.id);

// --- Refs locales pour les listes déroulantes (initialisées avec les props) ---
const departementsList = ref(props.departements || []);
const arrondissementsList = ref(props.arrondissements || []);
const communesList = ref(props.communes || []);

// --- Formulaire Inertia ---
const form = useForm({
    id: props.point_densifiers.id,
    txt_nomPoint: props.point_densifiers.txt_nomPoint || '',
    txt_dateCreationPoint: props.point_densifiers.txt_dateCreationPoint || '',
    slt_departement: props.point_densifiers.departement?.id || '',
    slt_arrondissement: props.point_densifiers.arrondissement?.id || '',
    slt_commune: props.point_densifiers.commune?.id || '',
    txt_descriptionPoint: props.point_densifiers.txt_descriptionPoint || '',
    txt_situationPoint: props.point_densifiers.txt_situationPoint || '',
    txt_estPoint: props.point_densifiers.txt_estPoint || '',
    txt_nordPoint: props.point_densifiers.txt_nordPoint || '',
    txt_hauteurPoint: props.point_densifiers.txt_hauteurPoint || '',
    fichierPDFPoint: null, // pour le nouveau fichier
});

// --- Références locales pour les selects (synchronisées avec form) ---
const slt_departement = ref(form.slt_departement);
const slt_arrondissement = ref(form.slt_arrondissement);
const slt_commune = ref(form.slt_commune);

// --- Fonctions de chargement des listes ---
const fetchDepartements = async () => {
    try {
        const response = await axios.get("/departements");
        departementsList.value = response.data.departements;
    } catch (error) {
        toast.error("Impossible de charger les départements");
    }
};

const fetchArrondissements = async (depId) => {
    if (!depId) {
        arrondissementsList.value = [];
        return;
    }
    try {
        const response = await axios.get(`/arrondissements/${depId}`);
        arrondissementsList.value = response.data.arrondissements;
    } catch (error) {
        toast.error("Impossible de charger les arrondissements");
    }
};

const fetchCommunes = async (arrId) => {
    if (!arrId) {
        communesList.value = [];
        return;
    }
    try {
        const response = await axios.get(`/communes/${arrId}`);
        communesList.value = response.data.communes;
    } catch (error) {
        toast.error("Impossible de charger les communes");
    }
};

// --- Initialisation au montage ---
onMounted(async () => {
    await fetchDepartements();
    // Charger les sous‑niveaux si déjà sélectionnés
    if (slt_departement.value) {
        await fetchArrondissements(slt_departement.value);
    }
    if (slt_arrondissement.value) {
        await fetchCommunes(slt_arrondissement.value);
    }
});

// --- Synchronisation des selects avec le formulaire ---
watch(slt_departement, async (val) => {
    // Mise à jour du formulaire
    form.slt_departement = val;
    // Réinitialiser les sous‑niveaux
    slt_arrondissement.value = "";
    slt_commune.value = "";
    await fetchArrondissements(val);
});

watch(slt_arrondissement, async (val) => {
    form.slt_arrondissement = val;
    slt_commune.value = '';
    await fetchCommunes(val);
});

watch(slt_commune, (val) => {
    form.slt_commune = val;
});

// --- Gestion du fichier PDF ---
function handleFileChangePDF(event) {
    const file = event.target.files[0];
    if (file && file.type === "application/pdf") {
        form.fichierPDFPoint = file;
    } else {
        form.fichierPDFPoint = null;
        toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
    }
}

// --- Vérification du code d'accès ---
const codeDaccees = async (code) => {
    if (!code) {
        toast.error("Veuillez entrer un code d'accès.");
        return;
    }
    isLoading.value = true;
    try {
        const { data } = await axios.post(route('code.verifyPoint'), { code });
        if (data.exists) {
            showModal.value = false;
            formVisible.value = true;
            toast.success("Modification autorisée !");
        } else {
            toast.error("Vérifie ton code d'accès !");
            formVisible.value = false;
        }
    } catch (err) {
        formVisible.value = false;
        if (err.response?.status === 422 && err.response.data.errors) {
            Object.values(err.response.data.errors).forEach(msg => toast.error(msg));
        } else {
            toast.error("Une erreur est survenue lors de la vérification.");
        }
    } finally {
        isLoading.value = false;
    }
};

// --- Soumission du formulaire ---
async function submitForm() {
    try {
        // Construire le FormData
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('txt_nomPoint', form.txt_nomPoint);
        formData.append('txt_dateCreationPoint', form.txt_dateCreationPoint);
        formData.append('slt_departement', form.slt_departement);
        formData.append('slt_arrondissement', form.slt_arrondissement);
        formData.append('slt_commune', form.slt_commune);
        formData.append('txt_descriptionPoint', form.txt_descriptionPoint);
        formData.append('txt_situationPoint', form.txt_situationPoint);
        formData.append('txt_estPoint', form.txt_estPoint);
        formData.append('txt_nordPoint', form.txt_nordPoint);
        formData.append('txt_hauteurPoint', form.txt_hauteurPoint);

        // Envoyer toujours le champ fichier (null si absent)
        formData.append('fichierPDFPoint', form.fichierPDFPoint || null);

        // Générer l'URL
        const url = route('parametre.pointDensifier.updatePoint', form.id);
        console.log('URL :', url);
        console.log('Données envoyées :', Object.fromEntries(formData.entries()));

        // Envoi
        await axios.post(url, formData);

        toast.success("Modification réussie !");
        Inertia.visit(route('parametre.pointDensifier.createPoint'), { replace: true });

    } catch (error) {
        console.error('Erreur complète :', error);
        if (error.response) {
            console.error('Données de réponse :', error.response.data);
            // Si le serveur renvoie un message d'erreur, on l'affiche
            const msg = error.response.data.message || `Erreur ${error.response.status}`;
            toast.error(msg);
        } else {
            toast.error("Erreur lors de la modification");
        }
    }
}
</script>

<template>
    <Head title="Modifier un point densifié" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modifier un point densifié
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">

                    <!-- Modal de code d'accès -->
                    <AppCodeAccee
                        v-model="showModal"
                        :loading="isLoading"
                        @confirm="codeDaccees"
                    />

                    <!-- Formulaire -->
                    <div v-if="hasPoint && formVisible" class="bg-white shadow-md rounded-lg">
                        <div class="p-4 border-b bg-primary">
                            <h1 class="text-lg text-white font-semibold">
                                Modification du point : {{ point_densifiers.txt_nomPoint }}
                            </h1>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="p-6 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                                    <!-- Nom -->
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Nom du Point"
                                        name="txt_nomPoint"
                                        autocomplete="off"
                                        v-model="form.txt_nomPoint"
                                    />

                                    <!-- Date -->
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Date de création"
                                        name="txt_dateCreationPoint"
                                        type="date"
                                        v-model="form.txt_dateCreationPoint"
                                    />

                                    <!-- Departement (utilise la ref locale) -->
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Département"
                                        name="slt_departement"
                                        :options="departementsList"
                                        optionLabel="slt_departement"
                                        optionValue="id"
                                        v-model="slt_departement"
                                    />

                                    <!-- Arrondissement -->
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Arrondissement"
                                        :options="arrondissementsList"
                                        optionLabel="slt_arrondissement"
                                        optionValue="id"
                                        v-model="slt_arrondissement"
                                    />

                                    <!-- Commune -->
                                    <BaseSelect
                                        class="sm:col-span-1"
                                        label="Commune"
                                        :options="communesList"
                                        optionLabel="slt_commune"
                                        optionValue="id"
                                        v-model="slt_commune"
                                    />

                                    <!-- Description -->
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Description"
                                        name="txt_descriptionPoint"
                                        v-model="form.txt_descriptionPoint"
                                    />
                                    <!-- Situation -->
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Situation"
                                        name="txt_situationPoint"
                                        v-model="form.txt_situationPoint"
                                    />
                                    <!-- Coordonnées -->
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Est (X)"
                                        name="txt_estPoint"
                                        v-model="form.txt_estPoint"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Nord (Y)"
                                        name="txt_nordPoint"
                                        v-model="form.txt_nordPoint"
                                    />
                                    <BaseInput
                                        class="sm:col-span-1"
                                        label="Hauteur (Z)"
                                        name="txt_hauteurPoint"
                                        v-model="form.txt_hauteurPoint"
                                    />
                                    <!-- Fichier PDF -->
                                    <BaseInput
                                        class="sm:col-span-2"
                                        label="Documents afférants (PDF)"
                                        name="fichierPDFPoint"
                                        type="file"
                                        accept="application/pdf"
                                        @change="handleFileChangePDF"
                                    /> 

                                    <div v-if="form.fichierPDFPoint && typeof form.fichierPDFPoint === 'string'" class="mt-2"> 
                                        <a
                                            :href="`/storage/${form.fichierPDFPoint}`"
                                            target="_blank"
                                            class="text-primary underline"
                                        >
                                            Voir le fichier
                                        </a>
                                    </div>  
                                </div>

                                <!-- Bouton -->
                                <div class="sm:col-span-6 flex justify-center mt-6">
                                    <MazBtn
                                        type="submit"
                                        :disabled="isSubmitting"
                                        no-shadow
                                        no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                                        size="medium"
                                    >
                                        {{ isSubmitting ? 'En cours...' : 'Mettre à jour' }}
                                    </MazBtn>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- États alternatifs -->
                    <div v-else-if="!hasPoint" class="bg-white shadow-md rounded-lg p-6 text-center">
                        <span class="text-4xl block mb-4">🔍</span>
                        <p class="text-primary-txt text-lg">Aucun point trouvé pour cette édition.</p>
                        <p class="text-primary-txt/60 text-sm mt-2">Vérifiez que l'ID est correct.</p>
                    </div>
                    <div v-else-if="hasPoint && !formVisible" class="bg-white shadow-md rounded-lg p-6 text-center">
                        <span class="text-4xl block mb-4">🔐</span>
                        <p class="text-primary-txt text-lg">Veuillez valider le code d'accès.</p>
                    </div>
                    <div v-else-if="isLoading" class="bg-white shadow-md rounded-lg p-6 text-center">
                        <span class="text-4xl block mb-4">⏳</span>
                        <p class="text-primary-txt">Vérification du code…</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>