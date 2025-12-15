<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm, usePage } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import AppCodeAccee from "@/Components/AppCodeAccee.vue";
import { onMounted, ref, watch, computed  } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia'; 
import "vue-toastification/dist/index.css";
   
defineOptions({ layout: DefaultLayout });
   
const { mutation } = defineProps({
    mutation: {
        type: Object,
        required: true,
    }
});

 
// Formulaire
const form = useForm({  
    txt_num_lotissement: mutation?.txt_num_lotissement || "",
    txt_lotissement: mutation?.txt_lotissement || "",
    txt_numeroMutation: mutation?.txt_numeroMutation || "",
    // txt_nicadMutation: mutation?.txt_nicadMutation || "",
    slt_titulaireMT: mutation?.slt_titulaireMT || "",
    txt_nationaliteMT: mutation?.txt_nationaliteMT || "",
    slt_civiliteMT: mutation?.slt_civiliteMT || "",
    txt_prenomMT: mutation?.txt_prenomMT || "",
    txt_nomMT: mutation?.txt_nomMT || "",
    slt_pieceMT: mutation?.slt_pieceMT || "",
    txt_numPieceMT: mutation?.txt_numPieceMT || "",
    dt_date_delivranceMT: mutation?.dt_date_delivranceMT || "",
    dt_date_naissanceMT: mutation?.dt_date_naissanceMT || "",
    txt_lieu_naissanceMT: mutation?.txt_lieu_naissanceMT || "",
    txt_adresseMT: mutation?.txt_adresseMT || "",
    tel_telephoneMT: mutation?.tel_telephoneMT || "",
    txt_representantMT: mutation?.txt_representantMT || "",
    tel_telRepresentantMT: mutation?.tel_telRepresentantMT || "",
    slt_categoriePM_MT: mutation?.slt_categoriePM_MT || "",
    txt_formJuridiquePM_MT: mutation?.txt_formJuridiquePM_MT || "",
    txt_denominationPM_MT: mutation?.txt_denominationPM_MT || "",
    txt_nineaPM_MT: mutation?.txt_nineaPM_MT || "",
    txt_perssonneRepresentantPM_MT: mutation?.txt_perssonneRepresentantPM_MT || "",
    txt_fonctionPM_MT: mutation?.txt_fonctionPM_MT || "",
    txt_telephonePM_MT: mutation?.txt_telephonePM_MT || "",
    txt_emailPM_MT: mutation?.txt_emailPM_MT || "",
    txt_adressePM_MT : mutation?.txt_adressePM_MT || "",
    slt_etablissementPA_MT: mutation?.slt_etablissementPA_MT || "",
    txt_personneResponsablePA_MT: mutation?.txt_personneResponsablePA_MT || "",
    txt_fonctionResponsablePA_MT: mutation?.txt_fonctionResponsablePA_MT || "",
    txt_telephonePA_MT: mutation?.txt_telephonePA_MT || "",
    txt_emailPA_MT: mutation?.txt_emailPA_MT || "",
    txt_ministèreTutelePA_MT: mutation?.txt_ministèreTutelePA_MT || "",
    slt_titulaireMutation: mutation?.slt_titulaireMutation || "",
    txt_prenomMutation: mutation?.txt_prenomMutation || "",
    txt_nomMutation: mutation?.txt_nomMutation || "",
    txt_nationaliteMutation: mutation?.txt_nationaliteMutation || "",
    slt_civiliteMutation: mutation?.slt_civiliteMutation || "",
    slt_pieceMutation: mutation?.slt_pieceMutation || "",
    txt_numPieceMutation: mutation?.txt_numPieceMutation || "",
    dt_date_delivranceMutation: mutation?.dt_date_delivranceMutation || "",
    dt_date_naissanceMutation: mutation?.dt_date_naissanceMutation || "",
    txt_lieu_naissanceMutation: mutation?.txt_lieu_naissanceMutation || "",
    txt_adresseMutation: mutation?.txt_adresseMutation || "",
    tel_telephoneMutation: mutation?.tel_telephoneMutation || "",
    txt_representantMutation: mutation?.txt_representantMutation || "",
    tel_telRepresentantMutation: mutation?.tel_telRepresentantMutation || "",
    txt_typeActeMutation: mutation?.txt_typeActeMutation || "",
    txt_nomNotaireMutation: mutation?.txt_nomNotaireMutation || "",
    dt_dateMutation: mutation?.dt_dateMutation || "",
    txt_jugementMutation: mutation?.txt_jugementMutation || "",
    txt_observationMutation: mutation?.txt_observationMutation || "",
    slt_categoriePM_Mutation: mutation?.slt_categoriePM_Mutation || "",
    txt_formJuridiquePM_Mutation: mutation?.txt_formJuridiquePM_Mutation || "",
    txt_denominationPM_Mutation: mutation?.txt_denominationPM_Mutation || "",
    txt_nineaPM_Mutation: mutation?.txt_nineaPM_Mutation || "",
    txt_perssonneRepresentantPM_Mutation: mutation?.txt_perssonneRepresentantPM_Mutation || "",
    txt_fonctionPM_Mutation: mutation?.txt_fonctionPM_Mutation || "",
    txt_telephonePM_Mutation: mutation?.txt_telephonePM_Mutation || "",
    txt_emailPM_Mutation: mutation?.txt_emailPM_Mutation || "",
    txt_adressePM_Mutation: mutation?.txt_adressePM_Mutation || "",
    slt_etablissementPA_Mutation: mutation?.slt_etablissementPA_Mutation || "",
    txt_personneResponsablePA_Mutation: mutation?.txt_personneResponsablePA_Mutation || "",
    txt_fonctionResponsablePA_Mutation: mutation?.txt_fonctionResponsablePA_Mutation || "",
    txt_telephonePA_Mutation: mutation?.txt_telephonePA_Mutation || "",
    txt_emailPA_Mutation: mutation?.txt_emailPA_Mutation || "",
    txt_ministèreTutelePA_Mutation: mutation?.txt_ministèreTutelePA_Mutation || "",

});
 
const referencesLot = ref([]);   
const formVisible = ref(false)
const showModal = ref(true);     
const isLoading = ref(false)  
const fichierPDF = ref(null);  
const show = ref(false);  
const showSectionPP = ref(false); // Perssone Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // Etat
const showSectionPP_Acq = ref(false); // Perssone Physique
const showSectionPM_Acq = ref(false); // Personne Morale
const showSectionPA_Acq = ref(false); // Etat
 
// Récuperer le fichier PDF 
function handleFileUpload(event) {
    const file = event.target.files[0];

    if (!file) return;

    if (file.type !== "application/pdf") {
        toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
        event.target.value = ""; // reset input
        return;
    }

    if (file.size > 100 * 1024 * 1024) { // 100 Mo
        toast.error("❌ Le fichier dépasse 100 Mo !");
        event.target.value = ""; // reset input
        return;
    }

    fichierPDF.value = file;
    form.fichierPDF = file;

    console.log("Fichier PDF sélectionné :", fichierPDF.value);
} 
 
// charge titulaire selon le type de Proprietaire 
let hasShownError = false;
const handleSelectChangeTitulaire = () => {
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
            if (!hasShownError) {
                hasShownError = true;
                toast.error("Veuillez choisir un titulaire");
            }
    }
};

// s’affiche dès le chargement du formulaire
onMounted(() => {
    handleSelectChangeTitulaire();
}); 
// changements dynamiques
watch(() => form.slt_titulaireMT, () => {
    handleSelectChangeTitulaire();
});
  
// charge titulaire selon le type d'acquereur
let hasShownErrorMutAcquereur = false;
const handleSelectChangeTitulaireAcquereur = () => {
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
                hasShownErrorMutAcquereur = true;
                toast.error("Veuillez choisir un titulaire");
            }  
    }
};

// chargement si édition
onMounted(() => {
    handleSelectChangeTitulaireAcquereur();
});

// changements dynamiques
watch(() => form.slt_titulaireMutation, () => {
    handleSelectChangeTitulaireAcquereur();
});
 
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
            <div class="w-full max-w-8xl">   
                <AppCodeAccee
                    v-model="showModal"
                    :loading="isLoading"
                    @confirm="codeDaccees"
                />
            </div>

            <div class="flex justify-center">
                <div class="w-full max-w-7xl">
                    <div class="bg-white shadow-md rounded-lg">
                        <!-- En-tête du formulaire -->
                        <div   class="p-4 border-b bg-primary">
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
                                    </div>
                                </div>

                                <h5 class="text-lg text-primary-txt font-bold mt-2">
                                    Références Propriétaire
                                </h5>
                               
                                <div class="mb-6">
                                    <div 
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-2"
                                    > 

                                        <BaseSelect 
                                            label="Titulaire" 
                                            v-model="form.slt_titulaireMT"
                                            name="slt_titulaireMT"
                                            :options="[
                                                {value: 'Personne Physique', label: 'Perssonne Physique'},
                                                {value: 'Personne Morale', label: 'Personne Morale'},
                                                {value: 'Etat', label: 'Etat'},
                                            ]"
                                        />
                                         
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Nationalite" 
                                            v-model="form.txt_nationaliteMT"
                                            name="txt_nationaliteMT"
                                            type="text"
                                        /> 
                                        
                                        <BaseSelect
                                            v-if="showSectionPP"
                                            label="Civilite" 
                                            v-model="form.slt_civiliteMT"
                                            name="slt_civiliteMT"
                                            :options="[
                                                {value: 'Mr', label: 'Mr'},
                                                {value: 'Mme', label: 'Mme'},
                                            ]"
                                        />
 
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Prénom" 
                                            v-model="form.txt_prenomMT"
                                            name="txt_prenomMT"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Nom" 
                                            v-model="form.txt_nomMT"
                                            name="txt_nomMT"
                                            type="text"
                                        />
  
                                        <BaseSelect
                                            v-if="showSectionPP"
                                            label="Type Piece" 
                                            v-model="form.slt_pieceMT"
                                            name="slt_pieceMT"
                                            :options="[
                                                {value: 'CNI', label: 'CNI'},
                                                {value: 'Passeport', label: 'Passeport'},
                                            ]"
                                        />
  
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="N° Pièce" 
                                            v-model="form.txt_numPieceMT"
                                            name="txt_numPieceMT"
                                            type="text"
                                        />
  
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Date Délivrance" 
                                            v-model="form.dt_date_delivranceMT"
                                            name="dt_date_delivranceMT"
                                            type="date"
                                            :max="new Date().toISOString().split('T')[0]"
                                        />
   
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Date Naissance" 
                                            v-model="form.dt_date_naissanceMT"
                                            name="dt_date_naissanceMT"
                                            type="date"
                                            :max="new Date().toISOString().split('T')[0]"
                                        />
   
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Lieu Naissance" 
                                            v-model="form.txt_lieu_naissanceMT"
                                            name="txt_lieu_naissanceMT"
                                            type="text"
                                        />
    
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Adresse" 
                                            v-model="form.txt_adresseMT"
                                            name="txt_adresseMT"
                                            type="text"
                                        />
     
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Téléphone" 
                                            v-model="form.tel_telephoneMT"
                                            name="tel_telephoneMT"
                                            type="text"
                                        />
      
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Représentant" 
                                            v-model="form.txt_representantMT"
                                            name="txt_representantMT"
                                            type="text"
                                        />
       
                                        <BaseInput
                                            v-if="showSectionPP"
                                            label="Tel.Representant" 
                                            v-model="form.tel_telRepresentant"
                                            name="tel_telRepresentant"
                                            type="text"
                                        /> 

                                        <!-- Perssonne Morale -->
                                           
                                        <BaseSelect
                                            v-if="showSectionPM"
                                            label="Categorie" 
                                            v-model="form.slt_categoriePM_MT"
                                            name="slt_categoriePM_MT"
                                            :options="[
                                                {value: 'Station', label: 'Station'},
                                                {value: 'Industrie', label: 'Industrie'},
                                                {value: 'Restaurant', label: 'Restaurant'},
                                                {value: 'Hotel', label: 'Hotel'},
                                                {value: 'Hoberge', label: 'Hoberge'},
                                                {value: 'Elevage', label: 'Elevage'},
                                                {value: 'Agrobusness', label: 'Agrobusness'},
                                                {value: 'Epicerie', label: 'Epicerie'},
                                                {value: 'Alimentation Générale', label: 'Alimentation Générale'},
                                                {value: 'Qincaillerie', label: 'Qincaillerie'},
                                                {value: 'Transport', label: 'Transport'},
                                                {value: 'Coopérative Habitat', label: 'Coopérative Habitat'},
                                                {value: 'BTP', label: 'BTP'},
                                                {value: 'Organisation Religieux', label: 'Organisation Religieux'},
                                            ]"
                                        />
                                                
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Forme Juridique" 
                                            v-model="form.txt_formJuridiquePM_MT"
                                            name="txt_formJuridiquePM_MT"
                                            type="text"
                                        />  
                                                                                        
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Dénomination" 
                                            v-model="form.txt_denominationPM_MT"
                                            name="txt_denominationPM_MT"
                                            type="text"
                                        />  
                                                                                      
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Ninea" 
                                            v-model="form.txt_nineaPM_MT"
                                            name="txt_nineaPM_MT"
                                            type="text"
                                        />  
                                                                                       
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Nom.Personne Responsable" 
                                            v-model="form.txt_perssonneRepresentantPM_MT"
                                            name="txt_perssonneRepresentantPM_MT"
                                            type="text"
                                        /> 
                                                                                        
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Fonction Responsable" 
                                            v-model="form.txt_fonctionPM_MT"
                                            name="txt_fonctionPM_MT"
                                            type="text"
                                        /> 
                                                                                         
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Tél Responsable" 
                                            v-model="form.txt_telephonePM_MT"
                                            name="txt_telephonePM_MT"
                                            type="text"
                                        /> 
                                                                                          
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Email" 
                                            v-model="form.txt_emailPM_MT"
                                            name="txt_emailPM_MT"
                                            type="text"
                                        /> 
                                                                                                                                     
                                        <BaseInput
                                            v-if="showSectionPM"
                                            label="Adresse Sciège" 
                                            v-model="form.txt_adressePM_MT"
                                            name="txt_adressePM_MT"
                                            type="text"
                                        />  
  
                                        <!-- Etat -->
                                                                                                                                                                              
                                        <BaseSelect
                                            v-if="showSectionPA" 
                                            label="Etaablissement" 
                                            v-model="form.slt_etablissementPA_MT"
                                            name="slt_etablissementPA_MT"
                                            :option="[
                                                {value: 'Sanitaire', label: 'Sanitaire'},
                                                {value: 'Scolaire', label: 'Scolaire'}, 
                                                {value: 'Millitaire', label: 'Millitaire'},
                                                {value: 'Administratif', label: 'Administratif'},
                                                {value: 'Aéroportuaire', label: 'Aéroportuaire'},
                                                {value: 'Sportif', label: 'Sportif'},
                                                {value: 'Universitaire', label: 'Universitaire'},
                                                {value: 'Autres', label: 'Autres'},
                                            ]"
                                        />   
                                                                                                                                                                             
                                        <BaseInput
                                            v-if="showSectionPA"
                                            label="Personne Responsable" 
                                            v-model="form.txt_personneResponsablePA_MT"
                                            name="txt_personneResponsablePA_MT"
                                            type="text"
                                        />  
                                                                                                                                                                                                                     
                                        <BaseInput
                                            v-if="showSectionPA"
                                            label="Fonction Responsable" 
                                            v-model="form.txt_fonctionResponsablePA_MT"
                                            name="txt_fonctionResponsablePA_MT"
                                            type="text"
                                        />  
                                                                                                                                                                                                                                                         
                                        <BaseInput
                                            v-if="showSectionPA"
                                            label="Téléphone" 
                                            v-model="form.txt_telephonePA_MT"
                                            name="txt_telephonePA_MT"
                                            type="text"
                                        />   
                                                                                                                                                                                                                                                                                                 
                                        <BaseInput
                                            v-if="showSectionPA"
                                            label="Email" 
                                            v-model="form.txt_emailPA_MT"
                                            name="txt_emailPA_MT"
                                            type="text"
                                        />  
                                                                                                                                                                                                                                                                                                                                       
                                        <BaseInput
                                            v-if="showSectionPA"
                                            label="Ministère Tutelle" 
                                            v-model="form.txt_ministèreTutelePA_MT"
                                            name="txt_ministèreTutelePA_MT"
                                            type="text"
                                        />  
 
                                    </div>
                                </div>
                                    
                                <!-- Section acquereurre -->
                                <div class="mb-6">  
                                    <h5 class="text-lg text-primary-txt font-bold mt-2">
                                        Références Acquéreur
                                    </h5> 

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4"
                                    >
                                        <BaseSelect 
                                            label="Titulaire"
                                            v-model="form.slt_titulaireMutation"
                                            name="slt_titulaireMutation"
                                            :options="[
                                                { value: 'Personne Physique', label: 'Personne Physique' },
                                                { value: 'Personne Morale', label: 'Personne Morale' },
                                                { value: 'Etat', label: 'Etat' },
                                            ]"
                                            @change="handleSelectChangeTitulaireAcquereur"
                                        />
 
                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Prénom"
                                            v-model="form.txt_prenomMutation"
                                            name="txt_prenomMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Nom"
                                            v-model="form.txt_nomMutation"
                                            name="txt_nomMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Nationalité"
                                            v-model="form.txt_nationaliteMutation"
                                            name="txt_nationaliteMutation"
                                            type="text"
                                        />

                                        <BaseSelect
                                            v-if="showSectionPP_Acq"
                                            label="Civilité"
                                            v-model="form.slt_civiliteMutation"
                                            name="slt_civiliteMutation"
                                            :options="[
                                                {value: 'Mr', label: 'Mr'},
                                                {value: 'Mme', label: 'Mme'},
                                            ]"
                                        />

                                        <BaseSelect
                                            v-if="showSectionPP_Acq"
                                            label="Types Pièces"
                                            v-model="form.slt_pieceMutation"
                                            name="slt_pieceMutation"
                                            :options="[
                                                {value: 'CNI', label: 'CNI'},
                                                {value: 'Passeport', label: 'Passeport'},
                                            ]"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Numéro Pièce"
                                            v-model="form.txt_numPieceMutation"
                                            name="txt_numPieceMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Date délivrance"
                                            v-model="form.dt_date_delivranceMutation"
                                            name="dt_date_delivranceMutation"
                                            type="date"
                                            :max="new Date().toISOString().split('T')[0]"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Date Naissance"
                                            v-model="form.dt_date_naissanceMutation"
                                            name="dt_date_naissanceMutation"
                                            type="date"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Lieu Naissance"
                                            v-model="form.txt_lieu_naissanceMutation"
                                            name="txt_lieu_naissanceMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Adresse"
                                            v-model="form.txt_adresseMutation"
                                            name="txt_adresseMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPP_Acq"
                                            label="Téléphone"
                                            v-model="form.tel_telephoneMutation"
                                            name="tel_telephoneMutation"
                                            type="text"
                                        />   
 

                                        <BaseSelect
                                            v-if="showSectionPM_Acq"
                                            label="Catégorie"
                                            v-model="form.slt_categoriePM_Mutation"
                                            name="slt_categoriePM_Mutation"
                                            :options="[
                                                { value: '', label: '' },
                                                { value: 'Station', label: 'Station' },
                                                { value: 'Industrue', label: 'Industrue' },
                                                { value: 'Restaurant', label: 'Restaurant' },
                                                { value: 'Hotel', label: 'Hotel' },
                                                { value: 'Hoberge', label: 'Hoberge' },
                                                { value: 'Elevage', label: 'Elevage' },
                                                { value: 'Agrobusness', label: 'Agrobusness' },
                                                { value: 'Epicerie', label: 'Epicerie' },
                                                { value: 'Alimentation Générale', label: 'Alimentation Générale' },
                                                { value: 'Qincaillerie', label: 'Qincaillerie' },
                                                { value: 'Transport', label: 'Transport' },
                                                { value: 'Coopérative Habitat', label: 'Coopérative Habitat' },
                                                { value: 'BTP', label: 'BTP' },
                                                { value: 'Organisation Religieux', label: 'Organisation Religieux' },
                                            ]"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Forme Juridique"
                                            v-model="form.txt_formJuridiquePM_Mutation"
                                            name="txt_formJuridiquePM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Dénomination"
                                            v-model="form.txt_denominationPM_Mutation"
                                            name="txt_denominationPM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Ninea"
                                            v-model="form.txt_nineaPM_Mutation"
                                            name="txt_nineaPM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Nom.Personne Responsable"
                                            v-model="form.txt_perssonneRepresentantPM_Mutation"
                                            name="txt_perssonneRepresentantPM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Fonction Responsable"
                                            v-model="form.txt_fonctionPM_Mutation"
                                            name="txt_fonctionPM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Téléphone Responsable"
                                            v-model="form.txt_telephonePM_Mutation"
                                            name="txt_telephonePM_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Email"
                                            v-model="form.txt_emailPM_Mutation"
                                            name="txt_emailPM_Mutation"
                                            type="email"
                                        />

                                        <BaseInput
                                            v-if="showSectionPM_Acq"
                                            label="Adresse Siège"
                                            v-model="form.txt_adressePM_Mutation"
                                            name="txt_adressePM_Mutation"
                                            type="text"
                                        />
  

                                        <BaseSelect
                                            v-if="showSectionPA_Acq"
                                            label="Etablissement"
                                            name="slt_etablissementPA"
                                            v-model="form.slt_etablissementPA_Mutation"
                                            :options="[
                                                { value: '', label: '' },
                                                { value: 'Sanitaire', label: 'Sanitaire' },
                                                { value: 'Scolaire', label: 'Scolaire' },
                                                { value: 'Millitaire', label: 'Millitaire' },
                                                { value: 'Administratif', label: 'Administratif' },
                                                { value: 'Aéroportuaire', label: 'Aéroportuaire' },
                                                { value: 'Sportif', label: 'Sportif' },
                                                { value: 'Universitaire', label: 'Universitaire' },
                                                { value: 'Autres', label: 'Autres' },
                                            ]"
                                        />

                                        <BaseInput
                                            v-if="showSectionPA_Acq"
                                            label="Personne Responsable"
                                            name="txt_personneResponsablePA"
                                            v-model="form.txt_personneResponsablePA_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPA_Acq"
                                            label="Fonction Responsable"
                                            name="txt_fonctionResponsablePA"
                                            v-model="form.txt_fonctionResponsablePA_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPA_Acq"
                                            label="Téléphone"
                                            name="txt_telephonePA"
                                            v-model="form.txt_telephonePA_Mutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            v-if="showSectionPA_Acq"
                                            label="Email"
                                            name="txt_emailPA"
                                            v-model="form.txt_emailPA_Mutation"
                                            type="email"
                                        />

                                        <BaseInput
                                            v-if="showSectionPA_Acq"
                                            label="Ministère Tutelle"
                                            name="txt_ministèreTutelePA"
                                            v-model="form.txt_ministèreTutelePA_Mutation"
                                            type="text"
                                        />
 

                                        <BaseSelect
                                            label="Actes"
                                            name="txt_typeActeMutation"
                                            v-model="form.txt_typeActeMutation"
                                            @change="mutationNotaire"
                                            :options="[
                                                { value: '', label: '' },
                                                { value: 'Acte Notarie', label: 'Acte Notarié' },
                                                { value: 'Acte Sous Seing Privé', label: 'Acte sous Seing Privé' },
                                                { value: 'Acte de donation', label: 'Acte de donation' },
                                            ]"
                                        />

                                        <BaseInput
                                            v-if="show"
                                            label="Nom Notaire"
                                            name="txt_nomNotaireMutation"
                                            v-model="form.txt_nomNotaireMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            label="Date Mutation"
                                            name="dt_dateMutation"
                                            v-model="form.dt_dateMutation"
                                            type="date"
                                        />

                                        <BaseInput
                                            label="Jugement"
                                            name="txt_jugementMutation"
                                            v-model="form.txt_jugementMutation"
                                            type="text"
                                        />

                                        <BaseInput
                                            label="Observation"
                                            name="txt_observationMutation"
                                            v-model="form.txt_observationMutation"
                                            type="text"
                                        />
 
                                        <div class="sm:col-span-1">
                                            <label
                                                for="fichierPDFMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Importer fichier</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="file"
                                                    name="fichierPDFMutation"
                                                    accept="application/pdf"
                                                    @change="handleFileUpload"
                                                    id="fichierPDFMutation"
                                                    autocomplete="off"
                                                    class="h-7 block w-full rounded-md bg-white 
                                                        px-3 py-1.5 text-base text-primary-txt outline outline-1 
                                                        -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-offset-2 
                                                        focus:outline-indigo-600 sm:text-sm/6"
                                                />
                                            </div>
                                        </div>

                                    </div>  
                                </div> 

                                <!-- Bouton de soumission -->

                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn type="submit" no-shadow no-hover-effect
                                            class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-light to-primary-dark 
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-primary 
                                                dark:focus:ring-primary-dark shadow-lg shadow-primary/50 
                                                dark:shadow-lg dark:shadow-primary-dark font-medium rounded-lg text-sm 
                                                px-5 py-2.5 text-center"
                                                size="medium">
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

