<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, ref, watch, watchEffect } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';

defineOptions({ layout: DefaultLayout });
  
// Form
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
    txt_documentsAfferantsPoint: ""
});

// Sélections
const slt_departement = ref(null);
const slt_arrondissement = ref(null);
const slt_commune = ref(null); 
const departements = ref([]);
const arrondissements = ref([]);
const communes = ref([]);
const fichierPDF = ref(null);
 
// départements
const fetchDepartements = async () => {
    try {
        const response = await axios.get("/departements");
        departements.value = response.data.departements;
        console.log("Départements :", departements.value);
    } catch (error) {
        console.error("Erreur lors du chargement des départements :", error);
        departements.value = [];
    }
};

// arrondissements selon le département
const fetchArrondissements = async () => {
    if (!slt_departement.value) {
        arrondissements.value = [];
        slt_arrondissement.value = null;
        return;
    }

    try {
        const response = await axios.get(`/arrondissements/${slt_departement.value}`);
        arrondissements.value = response.data.arrondissements;
        console.log("Arrondissements :", arrondissements.value);
    } catch (error) {
        console.error("Erreur lors du chargement des arrondissements :", error);
        arrondissements.value = [];
    }
};

// communes selon l’arrondissement
const fetchCommunes = async () => {
    if (!slt_arrondissement.value) {
        communes.value = [];
        slt_commune.value = null;
        return;
    }

    try {
        const response = await axios.get(`/communes/${slt_arrondissement.value}`);
        communes.value = response.data.communes;
        console.log("Communes :", communes.value);
    } catch (error) {
        console.error("Erreur lors du chargement des communes :", error);
        communes.value = [];
    }
}; 
// synchroniser les fetchs
watch(slt_departement, fetchArrondissements);
watch(slt_arrondissement, fetchCommunes); 
// synchronisation
watchEffect(() => {
    form.slt_departement = slt_departement.value;
    form.slt_arrondissement = slt_arrondissement.value;
    form.slt_commune = slt_commune.value;
}); 
onMounted(fetchDepartements);

// Fichier PDF 
const handleFileUpload = (event) => {
    fichierPDF.value = event.target.files[0];
    form.txt_documentsAfferantsPoint = fichierPDF.value; // lier directement au formulaire
};
 
// Submit
const submitForm = () => {
    // Synchroniser les selections avec le formulaire
    form.slt_departement = slt_departement.value || "";
    form.slt_arrondissement = slt_arrondissement.value || "";
    form.slt_commune = slt_commune.value || "";

    form.post(route('parametre.store'), {
        onSuccess: (page) => {
            toast.success(page.props.flash?.success || "✅ Dossier Lotissement enregistré !");
            Inertia.visit(route("parametre.lotissement.create"), { replace: true });
        },
        onError: (errors) => {
            Object.values(errors).forEach(msg => toast.error(msg));
        }
    });
};

</script>
 
<template>
    <Head title="Lotissement" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Lotissements
            </h2>

        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl"> 
                        <div   class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Formulaire</h1>
                        </div> 
                        <form @submit.prevent="submitForm">
                            <div class="p-6"> 
                                <h5 class="text-lg text-primary-txt font-bold">
                                    Ajouter Lotissement
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4"> 
 
                                    <BaseSelect
                                        class="sm:col-span-2"
                                        label="Département"
                                        name="slt_departement"
                                        :options="departements"
                                        optionLabel="slt_departement"
                                        optionValue="id" 
                                        v-model="slt_departement" 
                                        @change="fetchDepartements"
                                    /> 
  
                                    <BaseSelect
                                        class="sm:col-span-2"
                                        label="Arrondissement"
                                        :options="arrondissements"
                                        optionLabel="slt_arrondissement"
                                        optionValue="id" 
                                        v-model="slt_arrondissement" 
                                        @change="fetchArrondissements"
                                    />
 
                                    <BaseSelect
                                        class="sm:col-span-2"
                                        label="Commune"
                                        :options="communes"
                                        optionLabel="slt_commune"
                                        optionValue="id" 
                                        v-model="slt_commune"
                                        @change="fetchCommunes"
                                    />
 
                                    <BaseInput
                                        class="sm:col-span-2"
                                        label="Dénommination"
                                        name="txt_denomination" 
                                        autocomplete="off"
                                        v-model="form.txt_denomination"
                                    />
 
                                    <BaseInput
                                        class="sm:col-span-2"
                                        label="Nombre de Parcelles"
                                        name="txt_nombreParcelle" 
                                        autocomplete="off"
                                        v-model="form.txt_nombreParcelle"
                                    />

                                     
                                    <BaseInput
                                        class="sm:col-span-2"
                                        label="Surface du Lotissement"
                                        name="txt_surfaceLotissement"
                                        autocomplete="off"
                                        v-model="form.txt_surfaceLotissement"
                                    />
 
                                    <BaseInput
                                        class="sm:col-span-4"
                                        label="Documents Afférants" 
                                        accept="application/pdf"
                                        name="txt_documentsAfferants"
                                        type="file"  
                                        @change="handleFileUpload"
                                    />
                                </div>
                                <br>

 
                                <!-- Bouton de soumission -->

                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn type="submit" no-shadow no-hover-effect
                                            class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                                dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                                dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
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
    </AuthenticatedLayout>
</template>

