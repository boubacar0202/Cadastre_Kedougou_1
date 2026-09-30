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
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';

defineOptions({ layout: DefaultLayout });
  
const {lotissements} = defineProps({
    lotissements: Object,
    departements: Array,
    arrondissements: Array,
    communes: Array
});
  
// Options des selects
const departements = ref([]);
const arrondissements = ref([]);
const communes = ref([]);
const code = ref('');  
const formVisible = ref(false)
const showModal = ref(true);     
const isLoading = ref(false)  
 
// Form
const form = useForm({
    id: lotissements.id,

    txt_denomination:   lotissements.txt_denomination || '',
    txt_nombreParcelle: lotissements.txt_nombreParcelle || '',
    slt_departement:    lotissements.departement?.id || '',
    slt_arrondissement: lotissements.arrondissement?.id || '',
    slt_commune: lotissements.commune?.id || '',
    txt_surfaceLotissement: lotissements.txt_surfaceLotissement || '',
    txt_documentsAfferants: lotissements.txt_documentsAfferants || '',
}); 

// Refs pour les selects
const slt_departement = ref(form.slt_departement);
const slt_arrondissement = ref(form.slt_arrondissement);
const slt_commune = ref(form.slt_commune);

// Charger départements
const fetchDepartements = async () => {
    const response = await axios.get("/departements");
    departements.value = response.data.departements;
};

// Charger arrondissements selon le département
const fetchArrondissements = async (depId) => {
    if (!depId) return arrondissements.value = [];
    const response = await axios.get(`/arrondissements/${depId}`);
    arrondissements.value = response.data.arrondissements;
};

// Charger communes selon l’arrondissement
const fetchCommunes = async (arrId) => {
    if (!arrId) return communes.value = [];
    const response = await axios.get(`/communes/${arrId}`);
    communes.value = response.data.communes;
};

// Pré-charger les selects si édition
onMounted(async () => {
    await fetchDepartements();

    if (slt_departement.value) {
        await fetchArrondissements(slt_departement.value);
    }
    if (slt_arrondissement.value) {
        await fetchCommunes(slt_arrondissement.value);
    }
});

// Synchroniser les refs avec le formulaire
watch([slt_departement, slt_arrondissement, slt_commune], () => {
    form.slt_departement = slt_departement.value;
    form.slt_arrondissement = slt_arrondissement.value;
    form.slt_commune = slt_commune.value;
});

// Mettre à jour arrondissements et communes quand le département change
watch(slt_departement, async (val) => {
    slt_arrondissement.value = "";
    slt_commune.value = "";
    await fetchArrondissements(val);
});

watch(slt_arrondissement, async (val) => {
    slt_commune.value = "";
    await fetchCommunes(val);
});

// Fichier PDF
function handleFileChangePDF(event) {
    const file = event.target.files[0];
    console.log('Fichier sélectionné:', file);
    
    if (file && file.type === "application/pdf") {
        form.txt_documentsAfferants = file; // ✅ Assigner directement le File object
    } else {
        form.txt_documentsAfferants = null;
        toast.error("❌ Veuillez sélectionner un fichier PDF valide.");

    }
}
 
// rechercher 

const codeDaccees = async (code) => { 
    if (!code) {
        toast.error("Veuillez entrer un code d'accès.")
        return
    }

    isLoading.value = true

    try {
        const { data } = await axios.post(route('code.verifyLotissement'), {
            code
        })

        if (data.exists) {
            showModal.value = false
            formVisible.value = true
            toast.success("Modification autorisée !")
        } else {
            toast.error("Vérifie ton code d'accès !")
            formVisible.value = false
        }

    } catch (err) {
        formVisible.value = false

        if (err.response?.status === 422 && err.response.data.errors) {
            Object.values(err.response.data.errors)
                .forEach(msg => toast.error(msg))
        } else {
            toast.error("Une erreur est survenue lors de la vérification.")
        }

    } finally {
        isLoading.value = false
    }
}
  
async function submitForm() {
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT'); 
        formData.append('txt_denomination', form.txt_denomination);
        formData.append('txt_nombreParcelle', form.txt_nombreParcelle);
        formData.append('slt_departement', form.slt_departement);
        formData.append('slt_arrondissement', form.slt_arrondissement);
        formData.append('slt_commune', form.slt_commune);
        formData.append('txt_surfaceLotissement', form.txt_surfaceLotissement);

        if (form.txt_documentsAfferants instanceof File) {
            formData.append('txt_documentsAfferants', form.txt_documentsAfferants);
        }

        const url = route('parametre.lotissement.update', form.id); 

        await axios.post(url, formData);

        toast.success("Modification réussie !");
        Inertia.visit(route('parametre.lotissement.index'), { replace: true });

    } catch (error) {
        console.error(error);
        toast.error("Erreur lors de la modification");
    }
}



</script>
 
<template>
    <Head title="Lotissement" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modifier Lotissement
            </h2>

        </template>

        <div class="py-12">


            <div class="flex justify-center">
                <div class="w-full max-w-8xl"> 
                    <div class="w-full max-w-8xl">   
                        <AppCodeAccee
                            v-model="showModal"
                            :loading="isLoading"
                            @confirm="codeDaccees"
                        />
                    </div> 

                    <div v-if="formVisible" class="bg-white shadow-md rounded-lg"> 
                        <div   class="p-4 border-b bg-primary">
                            <h1 class="text-lg text-white font-semibold">Formulaire de Modification d'un Lotissement</h1>
                        </div> 
                        <form @submit.prevent="submitForm">
                            <div class="p-6"> 
                                <h5 class="text-2xl text-gray-400 font-bold italic text-center mt-2">
                                    Modifier Lotissement : {{ lotissements.txt_denomination }}
                                </h5> <br>
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
                                        @change="handleFileChangePDF"
                                    />
                                    <div v-if="form.txt_documentsAfferants && typeof form.txt_documentsAfferants === 'string'" class="mt-2"> 
                                        <a
                                            :href="`/storage/${form.txt_documentsAfferants}`"
                                            target="_blank"
                                            class="text-blue-600 underline"
                                        >
                                            Voir le fichier
                                        </a>
                                    </div> 
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

