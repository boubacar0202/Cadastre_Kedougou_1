<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { onMounted, ref, watch, computed  } from "vue";
import axios from "axios"; 
import { toast } from "@/Components/AppToast"; 
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
   
defineOptions({ layout: DefaultLayout });
 
// const toast = showToast();
 
const formVisible = ref(false); 
const referencesLot = ref([]);   
 
const fichierPDF = ref(null);  
const show = ref(false);  
const showSectionPP = ref(false); // Perssone Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // Etat
const showSectionPP_Acq = ref(false); // Perssone Physique
const showSectionPM_Acq = ref(false); // Personne Morale
const showSectionPA_Acq = ref(false); // Etat
 
// Récuperer le fichier PDF 
 
 

// charge titulaire selon le type de Proprietaire 
let hasShownError = false;
const handleSelectChangeTitulaire = () => {
    // Reset toutes les sections
    showSectionPP.value = false;
    showSectionPM.value = false;
    showSectionPA.value = false;

    switch (form.slt_titulaireMT) {
        case "Personne Physique":
            showSectionPP.value = true;
            hasShownError = false;
            break;
        case "Personne Morale":
            showSectionPM.value = true;
            hasShownError = false;
            break;
        case "Etat":
            showSectionPA.value = true;
            hasShownError = false;
            break;
        default:
            if (!hasShownError){
                hasShownError = true
                toast.error("Veuillez choisir un titulaire");
            }  
    }
}; 
// Réagir automatiquement sans bouton
watch(() => form.slt_titulaireMT, (newValue) => {

    handleSelectChangeTitulaire(); 

    if (!["Personne Physique", "Personne Morale", "Etat"].includes(newValue)) {
            toast.error("Veuillez choisir un titulaire");
        }
    }
);
  
// charge titulaire selon le type d'acquereur
let hasShownErrorMutAcquereur = false;
const handleSelectChangeTitulaireAcquereur = () => {
    // Reset toutes les sections
    showSectionPP_Acq.value = false;
    showSectionPM_Acq.value = false;
    showSectionPA_Acq.value = false;

    switch (form.slt_titulaireMutation) {
        case "Personne Physique":
            showSectionPP_Acq.value = true;
            hasShownErrorMutAcquereur = false;
            break;
        case "Personne Morale":
            showSectionPM_Acq.value = true;
            hasShownErrorMutAcquereur = false;
            break;
        case "Etat":
            showSectionPA_Acq.value = true;
            hasShownErrorMutAcquereur = false;
            break;
        default:
            if (!hasShownErrorMutAcquereur){
                hasShownErrorMutAcquereur = true
                toast.error("Veuillez choisir un titulaire");
            }  
    }
}; 

// Réagir automatiquement sans bouton 
watch(() => form.slt_titulaireMutation, (newValue) => {

    handleSelectChangeTitulaireAcquereur(); 

    if (!["Personne Physique", "Personne Morale", "Etat"].includes(newValue)) {
            toast.error("Veuillez choisir un titulaire");
        }
    }
);
 
// Récupérer le type d'acte
const mutationNotaire = () => {
    show.value = form.txt_typeActeMutation === "Acte Notarie";
};
watch(() => form.txt_typeActeMutation, (newValue) => {
    show.value = newValue === "Acte Notarie";
});
 
// Charger les références  
onMounted(async () => {
    try {
        const res = await axios.post('/fetchReferencesLotissement');
        referencesLot.value = res.data.referencesLot || []; 
    } catch (e) {
        console.error('❌ Erreur lors de la récupération des références :', e);
        referencesLot.value = [];  
    }
});

// Rechercher le dossier
const rechercherDossier = async () => {
    try {
        const { data } = await axios.post('/dossier/verifyLotissement', {
            txt_num_lotissement: form.txt_num_lotissement,
            txt_lotissement: form.txt_lotissement,
        });

        // Si un terrain identique existe déjà
        if (data.duplicate) {
            formVisible.value = false;
            toast.success(data.message);
            return;
        }

        // Si aucun dossier trouvé
        if (!data.exists) {
            formVisible.value = false;
            toast.error(data.error || "Terrain introuvable.");
            return;
        } 
        // Si le dossier existe et n'est pas un doublon
        formVisible.value = true; 
        toast.success(data.message);

        // ✅ Remplir les champs terrain
        form.txt_num_lotissement = data.terrain.txt_num_lotissement;
        form.txt_lotissement = data.terrain.txt_lotissement; 
 
        // ✅ Remplir les champs dossier si existant  
        if (data.titulaire) {  
            form.slt_titulaireMT = data.titulaire.slt_titulaire ?? '';
            form.txt_nationaliteMT = data.titulaire.txt_nationalite ?? '';
            form.slt_civiliteMT = data.titulaire.slt_civilite ?? '';
            form.txt_prenomMT = data.titulaire.txt_prenom ?? '';
            form.txt_nomMT = data.titulaire.txt_nom ?? '';
            form.slt_pieceMT = data.titulaire.slt_piece ?? '';
            form.txt_numPieceMT = data.titulaire.txt_numPiece ?? '';
            form.dt_date_delivranceMT = data.titulaire.dt_date_delivrance ?? '';
            form.dt_date_naissanceMT = data.titulaire.dt_date_naissance ?? '';
            form.txt_lieu_naissanceMT = data.titulaire.txt_lieu_naissance ?? '';
            form.txt_adresseMT = data.titulaire.txt_adresse ?? '';
            form.tel_telephoneMT = data.titulaire.tel_telephone ?? '';
            form.txt_representantMT = data.titulaire.txt_representant ?? '';
            form.tel_telRepresentantMT = data.titulaire.tel_telRepresentant ?? ''; 
            // Personne physique
            form.slt_categoriePM_MT = data.titulaire.slt_categoriePM ?? '';
            form.txt_formJuridiquePM_MT = data.titulaire.txt_formJuridiquePM ?? '';
            form.txt_denominationPM_MT = data.titulaire.txt_denominationPM ?? '';
            form.txt_nineaPM_MT = data.titulaire.txt_nineaPM ?? '';
            form.txt_perssonneRepresentantPM_MT = data.titulaire.txt_perssonneRepresentantPM ?? '';
            form.txt_fonctionPM_MT = data.titulaire.txt_fonctionPM ?? '';
            form.txt_telephonePM_MT = data.titulaire.txt_telephonePM ?? '';
            form.txt_emailPM_MT = data.titulaire.txt_emailPM ?? '';
            form.txt_adressePM_MT = data.titulaire.txt_adressePM ?? '';
            // Personne morale
            form.slt_etablissementPA_MT = data.titulaire.slt_etablissementPA ?? '';
            form.txt_personneResponsablePA_MT = data.titulaire.txt_personneResponsablePA ?? '';
            form.txt_fonctionResponsablePA_MT = data.titulaire.txt_fonctionResponsablePA ?? '';
            form.txt_telephonePA_MT = data.titulaire.txt_telephonePA ?? '';
            form.txt_emailPA_MT = data.titulaire.txt_emailPA ?? '';
            form.txt_ministèreTutelePA_MT = data.titulaire.txt_ministèreTutelePA ?? ''; 
        }

        if (data.dossier) {
            form.txt_num_dossier = data.dossier.txt_num_dossier ?? '';
        }
 
    }catch (err) {
        formVisible.value = false; 

        if (err.response) {  
            toast.error(err.response.data?.error || "Entrer le nomero lotissement et le lotissement.");
        } else if (err.request) { 
            toast.error("Aucune réponse du serveur");
        } else { 
            toast.error(err.message);
        }
    } 
};
 
// Submit du formulaire
const submitForm = function () {   
    form.post(route('mutation.store'), { 
        onSuccess: (page) => {
            console.log("✅ Enregistré !", page);
            toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
            Inertia.visit(route("mutation.create"), { replace: true });
        },  
        onError: (errors) => {
            console.error("❌ Erreurs de validation :", errors);

            Object.entries(errors).forEach(([key, message]) => {
 
                if (Array.isArray(message)) {
                    message.forEach(msg => toast.error(msg));
                }  
                else {
                    toast.error(message);
                }
            });
        }, 
    }); 
};
 
</script>

<template>
    <Head title="Mutations" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Mutations 
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <!-- En-tête du formulaire -->
                        <div   class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Formulaire</h1>
                        </div>
                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm">
                            <div class="p-6"> 
                                <div class="flex justify-center"> 
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-xl "
                                    > 
                                        <div class="flex relative sm:col-span-4">
                                            <div class="sm:col-span-1 mr-2"> 
                                                <input
                                                    type="text"
                                                    name="txt_num_lotissement"
                                                    v-model="form.txt_num_lotissement" 
                                                    required
                                                    aria-label="Rechercher"
                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        placeholder="Numéro Lot"
                                                    @keyup.enter="rechercherDossier" 
                                                > 
                                            </div>
                                            <div class="sm:col-span-1 mr-2"> 
                                                <select
                                                    type="text"
                                                    name="txt_lotissement"
                                                    v-model="form.txt_lotissement" 
                                                    autocomplete="off" 
                                                    class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        placeholder="Entrer le numéro dossier conserné"
                                                > 
                                                    <option value="">Choisir le lotissement</option>
                                                    <option v-for="(ref, index) in referencesLot" :key="index" :value="ref">
                                                        {{ ref }}
                                                    </option> 
                                                </select>
                                            </div>
                                        </div>  
                                        <MazBtn  
                                            type="button"
                                            title="Rechercher"
                                            @click="rechercherDossier"
                                            class="w-36 h-auto text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                                dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                                dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
                                            size="medium"
                                        > 
                                            Charger
                                        </MazBtn>
                                    </div>
                                </div>
                            </div>
                        </form> 
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

