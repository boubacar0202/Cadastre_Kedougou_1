<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { onMounted, ref, watch, computed  } from "vue";
import axios from "axios";
import { useToast } from "maz-ui";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
   
defineOptions({ layout: DefaultLayout });

const toast = useToast();
const txt_num_dossier = ref(''); 
const formVisible = ref(false);
const txt_nicad = ref('')
const nbr_surface = ref(0) 
const referencesLot = ref([]);   

const today = new Date().toISOString().split('T')[0];
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

  
const form = useForm({
    //  Dossier
    txt_num_dossier: "",
    txt_nicad: "",
  
    // information titulaire
    slt_titulaireMT: "",
    txt_nationaliteMT: "",
    slt_civiliteMT: "",
    txt_prenomMT: "",
    txt_nomMT: "",
    slt_pieceMT: "",
    txt_numPieceMT: "",
    dt_date_delivranceMT: "",
    dt_date_naissanceMT: "",
    txt_lieu_naissanceMT: "",
    txt_adresseMT: "",
    tel_telephoneMT: "",  
    txt_representantMT: "",
    tel_telRepresentantMT: "", 
        // Personne physique
    slt_categoriePM_MT: "",
    txt_formJuridiquePM_MT: "",
    txt_denominationPM_MT: "",
    txt_nineaPM_MT: "",
    txt_perssonneRepresentantPM_MT: "",
    txt_fonctionPM_MT: "",
    txt_telephonePM_MT: "",
    txt_emailPM_MT: "",
    txt_adressePM_MT: "",
       // Personne morale
    slt_etablissementPA_MT: "",
    txt_personneResponsablePA_MT: "",
    txt_fonctionResponsablePA_MT: "",
    txt_telephonePA_MT: "",
    txt_emailPA_MT: "",
    txt_ministèreTutelePA_MT: "",
 
    // information ecquereur 
    slt_titulaireMutation: "",
    txt_prenomMutation:"",
    txt_nomMT:"",
    txt_nationaliteMutation:"",
    slt_civiliteMutation:"",
    slt_pieceMutation:"",
    txt_numPieceMutation:"",
    dt_date_delivranceMutation:"",
    dt_date_naissanceMutation:"",
    txt_lieu_naissanceMutation:"",
    txt_adresseMutation:"",
    tel_telephoneMutation:"",
    txt_representantMutation:"",
    tel_telRepresentantMutation:"", //1
    txt_typeActeMutation:"",
    txt_nomNotaireMutation:"",
    dt_dateMutation:"",
    txt_jugementMutation:"",
    txt_observationMutation:"",
        // Personne physique
    slt_categoriePM_Mutation: "",
    txt_formJuridiquePM_Mutation: "",
    txt_denominationPM_Mutation: "",
    txt_nineaPM_Mutation: "",
    txt_perssonneRepresentantPM_Mutation: "",
    txt_fonctionPM_Mutation: "",
    txt_telephonePM_Mutation: "",
    txt_emailPM_Mutation: "",
    txt_adressePM_Mutation: "",
       // Personne morale
    slt_etablissementPA_MT: "",
    txt_personneResponsablePA_Mutation: "",
    txt_fonctionResponsablePA_Mutation: "",
    txt_telephonePA_Mutation: "",
    txt_emailPA_Mutation: "",
    txt_ministèreTutelePA_Mutation: "",

    fichierPDFMutation: "",
});

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
 
// Charger les références au montage du composant
onMounted(async () => {
    try {
        const res = await axios.post('/fetch-references-arrivee');
        referencesLot.value = res.data.referencesLot || []; 
    } catch (e) {
        console.error('❌ Erreur lors de la récupération des références :', e);
        referencesLot.value = [];  
    }
});

// Rechercher le dossier
const rechercherDossier = async () => {
    try {
        const { data } = await axios.post('/dossier/verify', {
            txt_num_dossier: txt_num_dossier.value
        });

        if (data.exists) {
            const terrain = data.terrain;
            const nicad = terrain?.txt_nicad ?? "";

            // ✅ Vérification du NICAD
            if (!nicad) {
                formVisible.value = false;
                toast.error("Ce dossier n'a pas de NICAD.");
                return;
            }

            if (nicad.length <= 8) {
                formVisible.value = false;
                toast.error("Le NICAD est incomplet. Vérifiez !");
                return;
            }

            // ✅ Afficher le formulaire si tout est correct
            formVisible.value = true;
            form.txt_num_dossier = txt_num_dossier.value;
            // form.txt_nicad = nicad; 

            toast.success(data.success);
            console.log("✅ Terrain chargé :", terrain);

        } else {
            formVisible.value = false;
            toast.error(data.error || "Dossier introuvable.");
        }
    } catch (err) {
        formVisible.value = false;
        console.error("❌ Erreur complète :", err);

        if (err.response?.status === 422 && err.response.data.errors) {
            Object.values(err.response.data.errors).forEach(msg => toast.error(msg));
        } else {
            toast.error("Une erreur est survenue lors de la vérification.");
        }
    }
};
 

// Submit du formulaire
const submitForm = function () {  // Ajoutez `async` ici
  
    // Formulaire Laravel
    form.post(route('secretariat.store'), {
 
        onSuccess: (page) => {
            console.log("✅ Enregistré !", page);
            toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
            Inertia.visit(route("secretariat.create"), { replace: true });
        },
        onError: (errors) => {
            console.error("❌ Erreurs de validation :", errors);
            Object.entries(errors).forEach(([key, messages]) => {
                messages.forEach(msg => {
                    toast.error(`${key} : ${msg}`);
                });
            });
        },
        onFinish: () => {
            console.log("🟡 Requête terminée");
        }
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

                                <h5 class="text-lg text-primary-txt font-bold mt-2">
                                    Références Propriétaire
                                </h5>
                               
                                <div class="mb-6">
                                    <div 
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-2"
                                    >
                                        <div class="sm:col-span-1">
                                            <label
                                                for="Titulaire"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Titulaire</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_titulaire"
                                                    v-model="
                                                        form.slt_titulaireMT
                                                    "
                                                    id="Titulaire"
                                                    required
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    >
                                                    <option value="">
                                                    </option>
                                                    <option
                                                        value="Personne Physique"
                                                    >
                                                        Personne Physique
                                                    </option>
                                                    <option
                                                        value="Personne Morale"
                                                    >
                                                        Personne Morale
                                                    </option>
                                                    <option value="Etat">
                                                        État
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="Nationalite"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Nationalite</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nationalite"
                                                    v-model="
                                                        form.txt_nationaliteMT
                                                    " 
                                                    id="Nationalite"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="Civilite"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Civilité</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_civilite"
                                                    v-model="form.slt_civiliteMT" 
                                                    id="Civilite"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option value=""></option>
                                                    <option value="Mr">Mr</option>
                                                    <option value="Mme">Mme</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="Prenom"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Prénom</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_prenom"
                                                    v-model="form.txt_prenom" 
                                                    id="Prenom"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="nom"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Nom</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nom"
                                                    v-model="form.txt_nomMT" 
                                                    id="nom"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="selectePiece"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Types Piece</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_piece"
                                                    v-model="form.slt_pieceMT" 
                                                    id="selectePiece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option value=""></option>
                                                    <option value="CNI">CNI</option>
                                                    <option value="Passeport">Passeport</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="numPiece"
                                                class="block text-sm font-medium text-primary-txt"
                                                >N° Pièce</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_piece"
                                                    v-model="form.txt_numPieceMT" 
                                                    id="numPiece"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div  v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="dateDelivrance"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Date Delivrance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_delivrance"
                                                    v-model="
                                                        form.dt_date_delivranceMT
                                                    " 
                                                    :max="new Date().toISOString().split('T')[0]"
                                                    id="dateDelivrance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="dateNaissance"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Date Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_naissance"
                                                    v-model="
                                                        form.dt_date_naissanceMT
                                                    "
                                                    :max="new Date().toISOString().split('T')[0]" 
                                                    id="dateNaissance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="lieuNaissance"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Lieu Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_lieu_naissance"
                                                    v-model="
                                                        form.txt_lieu_naissanceMT
                                                    " 
                                                    id="lieuNaissance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="adresse"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Adresse</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_adresse"
                                                    v-model="form.txt_adresseMT" 
                                                    id="adresse"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="telephone"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Telephone</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="tel"
                                                    name="tel_telephone"
                                                    v-model="form.tel_telephoneMT" 
                                                    id="telephone"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>  
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="txt_representant"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Representant</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_representant"
                                                    v-model="
                                                        form.txt_representantMT
                                                    "
                                                    id="txt_representant"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="tel_telRepresentant"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Tel.Representant</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="tel_telRepresentant"
                                                    v-model="
                                                        form.tel_telRepresentantMT
                                                    "
                                                    id="tel_telRepresentant"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>

                                        <!-- Perssonne Morale -->
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="slt_categoriePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Catégorie</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_categoriePM"
                                                    v-model="form.slt_categoriePM_MT" 
                                                    id="selectePiece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option  value=""> 
                                                         
                                                    </option>
                                                    <option value="Station">
                                                        Station
                                                    </option>
                                                    <option value="Industrue">
                                                        Industrue
                                                    </option>
                                                    <option value="Restaurant">
                                                        Restaurant
                                                    </option>
                                                    <option value="Hotel">
                                                        Hotel
                                                    </option>
                                                    <option value="Hoberge">
                                                        Hoberge
                                                    </option>
                                                    <option value="Elevage">
                                                        Elevage
                                                    </option>
                                                    <option value="Agrobusness">
                                                        Agrobusness
                                                    </option>
                                                    <option value="Epicerie">
                                                        Epicerie
                                                    </option>
                                                    <option value="Alimentation Générale">
                                                        Alimentation Générale
                                                    </option>
                                                    <option value="Qincaillerie">
                                                        Qincaillerie
                                                    </option>
                                                    <option value="Transport">
                                                        Transport
                                                    </option>
                                                    <option value="Coopérative Habitat">
                                                        Coopérative Habitat
                                                    </option>
                                                    <option value="BTP">
                                                        BTP
                                                    </option>
                                                    <option value="Organisation Religieux">
                                                        Organisation Religieux
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_formJuridiquePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Forme Juridique</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_formJuridiquePM"
                                                    v-model="
                                                        form.txt_formJuridiquePM_MT 
                                                    "
                                                    id="txt_formJuridiquePM"
                                                    autocomplete="address-level2" 
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_denominationPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Dénomination</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_denominationPM"
                                                    v-model="
                                                        form.txt_denominationPM_MT
                                                    "
                                                    id="txt_denominationPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_nineaPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Ninea</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nineaPM"
                                                    v-model="
                                                        form.txt_nineaPM_MT
                                                    "
                                                    id="txt_nineaPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_perssonneRepresentantPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Nom.Personne Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_perssonneRepresentantPM"
                                                    v-model="
                                                        form.txt_perssonneRepresentantPM_MT
                                                    "
                                                    id="txt_perssonneRepresentantPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_fonctionPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Fonction Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_fonctionPM"
                                                    v-model="
                                                        form.txt_fonctionPM_MT
                                                    "
                                                    id="txt_fonctionPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_telephonePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Téléphone Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_telephonePM"
                                                    v-model="
                                                        form.txt_telephonePM_MT
                                                    "
                                                    id="txt_telephonePM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_emailPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Email</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="txt_emailPM"
                                                    v-model="
                                                        form.txt_emailPM_MT
                                                    "
                                                    id="txt_emailPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM" class="sm:col-span-1">
                                            <label
                                                for="txt_adressePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Adresse Sciège </label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_adressePM"
                                                    v-model="
                                                        form.txt_adressePM_MT
                                                    "
                                                    id="txt_adressePM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 

                                        <!-- Etat -->
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="slt_etablissementPA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Etablissement</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_etablissementPA"
                                                    v-model="form.slt_etablissementPA_MT" 
                                                    id="selectePiece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option  value=""> 
                                                         
                                                    </option>
                                                    <option value="Sanitaire">
                                                        Sanitaire
                                                    </option>
                                                    <option value="Scolaire">
                                                        Scolaire
                                                    </option>
                                                    <option value="Millitaire">
                                                        Millitaire
                                                    </option>
                                                    <option value="Administratif">
                                                        Administratif
                                                    </option>
                                                    <option value="Aéroportuaire">
                                                        Aéroportuaire
                                                    </option>
                                                    <option value="Sportif">
                                                        Sportif
                                                    </option>
                                                    <option value="Universitaire">
                                                        Universitaire
                                                    </option>
                                                    <option value="Autres">
                                                        Autres
                                                    </option> 
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="txt_personneResponsablePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Personne Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_personneResponsablePA"
                                                    v-model="
                                                        form.txt_personneResponsablePA_MT
                                                    "
                                                    id="txt_personneResponsablePA" 
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="txt_fonctionResponsablePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Fonction Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_fonctionResponsablePA"
                                                    v-model="
                                                        form.txt_fonctionResponsablePA_MT
                                                    "
                                                    id="txt_fonctionResponsablePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="txt_telephonePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Téléphone</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_telephonePA"
                                                    v-model="
                                                        form.txt_telephonePA_MT
                                                    "
                                                    id="txt_telephonePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="txt_emailPA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Email</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="txt_emailPA"
                                                    v-model="
                                                        form.txt_emailPA_MT
                                                    "
                                                    id="txt_emailPA" 
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA" class="sm:col-span-1">
                                            <label
                                                for="txt_ministèreTutelePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Ministère Tutelle</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_ministèreTutelePA"
                                                    v-model="
                                                        form.txt_ministèreTutelePA_MT
                                                    "
                                                    id="txt_ministèreTutelePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 
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

                                        <div class="sm:col-span-1">
                                            <label
                                                for="TitulaireMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Titulaire</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_titulaireMutation"
                                                    v-model="form.slt_titulaireMutation"
                                                    id="TitulaireMutation"
                                                    required
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @change="handleSelectChangeTitulaireAcquereur"
                                                >
                                                    >
                                                    <option value="">
                                                    </option>
                                                    <option
                                                        value="Personne Physique"
                                                    >
                                                        Personne Physique
                                                    </option>
                                                    <option
                                                        value="Personne Morale"
                                                    >
                                                        Personne Morale
                                                    </option>
                                                    <option value="Etat">
                                                        État
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Mutation de parssone physique -->
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="Prenom"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Prénom</label
                                            > 
                                            <div class="mt-2"> 
                                                <input
                                                    type="text"
                                                    name="txt_prenomMutationtxt_prenomMutation"
                                                    v-model="form.txt_prenomMutation"
                                                    id="Prenom"
                                                    required
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    
                                                > 
                                            </div>
                                        </div>
                                    
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="Nom"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Nom</label
                                            > 
                                            <div class="mt-2"> 
                                                <input
                                                    type="text"
                                                    name="txt_nomMTtxt_nomMT"
                                                    v-model="
                                                        form.txt_nomMT
                                                    " 
                                                    id="Nom"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>
                                        </div>
                                        <div  v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="Nationalite"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Nationalité</label
                                            > 
                                            <div class="mt-2"> 
                                                <input
                                                    type="text"
                                                    name="txt_nationaliteMutation"
                                                    v-model="form.txt_nationaliteMutation" 
                                                    id="Nationalite"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >  
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="Civilite"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Civilité</label
                                            > 
                                            <div class="mt-2"> 
                                                <select
                                                    type="text"
                                                    name="slt_civiliteMutation"
                                                    v-model="form.slt_civiliteMutation" 
                                                    id="Civilite"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option value=""></option>  
                                                    <option value="Mr">Mr</option>
                                                    <option value="Mme">Mme</option> 
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="Types Pieces"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Types Pièces</label
                                            >  
                                            <div class="mt-2">  
                                                <select
                                                    name="slt_pieceMutation"
                                                    v-model="form.slt_pieceMutation" 
                                                    id="Types Piece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option selected disabled>
                                                        Choisir ici
                                                    </option>
                                                    <option value="CNI">
                                                        CNI
                                                    </option>
                                                    <option value="Passeport">
                                                        Passeport
                                                    </option>
                                                </select> 
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="NumPieceMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Numéro Pièce</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_numPieceMutation"
                                                    v-model="form.txt_numPieceMutation" 
                                                    id="NumPieceMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="dateDelivranceMT"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Date délivrance</label
                                            > 
                                            <div class="mt-2">  
                                                <input
                                                    type="date"
                                                    name="dt_date_delivranceMutation"
                                                    v-model="form.dt_date_delivranceMutation" 
                                                    :max="new Date().toISOString().split('T')[0]"
                                                    id="dateDelivranceMT"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                > 
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="DateNaissanceMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Date Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_naissanceMutation"
                                                    v-model="form.dt_date_naissanceMutation" 
                                                    id="DateNaissanceMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="LieuNaissanceMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Lieu Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_lieu_naissanceMutation"
                                                    v-model="
                                                        form.txt_lieu_naissanceMutation
                                                    " 
                                                    :max="new Date().toISOString().split('T')[0]"
                                                    id="LieuNaissanceMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="AdresseMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Adresse</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_adresseMutation"
                                                    v-model="
                                                        form.txt_adresseMutation
                                                    " 
                                                    id="AdresseMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP_Acq" class="sm:col-span-1">
                                            <label
                                                for="TelephoneMutations"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Telephone</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="tel_telephoneMutation"
                                                    v-model="form.tel_telephoneMutation" 
                                                    id="TelephoneMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 

                                        <!-- Mutationperssone morale  --> 

                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="slt_categoriePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Catégorie</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_categoriePM"
                                                    v-model="form.slt_categoriePM_Mutation" 
                                                    id="selectePiece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option  value=""> 
                                                         
                                                    </option>
                                                    <option value="Station">
                                                        Station
                                                    </option>
                                                    <option value="Industrue">
                                                        Industrue
                                                    </option>
                                                    <option value="Restaurant">
                                                        Restaurant
                                                    </option>
                                                    <option value="Hotel">
                                                        Hotel
                                                    </option>
                                                    <option value="Hoberge">
                                                        Hoberge
                                                    </option>
                                                    <option value="Elevage">
                                                        Elevage
                                                    </option>
                                                    <option value="Agrobusness">
                                                        Agrobusness
                                                    </option>
                                                    <option value="Epicerie">
                                                        Epicerie
                                                    </option>
                                                    <option value="Alimentation Générale">
                                                        Alimentation Générale
                                                    </option>
                                                    <option value="Qincaillerie">
                                                        Qincaillerie
                                                    </option>
                                                    <option value="Transport">
                                                        Transport
                                                    </option>
                                                    <option value="Coopérative Habitat">
                                                        Coopérative Habitat
                                                    </option>
                                                    <option value="BTP">
                                                        BTP
                                                    </option>
                                                    <option value="Organisation Religieux">
                                                        Organisation Religieux
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_formJuridiquePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Forme Juridique</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_formJuridiquePM"
                                                    v-model="
                                                        form.txt_formJuridiquePM_Mutation
                                                    "
                                                    id="txt_formJuridiquePM"
                                                    autocomplete="address-level2" 
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_denominationPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Dénomination</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_denominationPM"
                                                    v-model="
                                                        form.txt_denominationPM_Mutation
                                                    "
                                                    id="txt_denominationPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_nineaPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Ninea</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nineaPM"
                                                    v-model="
                                                        form.txt_nineaPM_Mutation
                                                    "
                                                    id="txt_nineaPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_perssonneRepresentantPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Nom.Personne Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_perssonneRepresentantPM"
                                                    v-model="
                                                        form.txt_perssonneRepresentantPM_Mutation
                                                    "
                                                    id="txt_perssonneRepresentantPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_fonctionPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Fonction Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_fonctionPM"
                                                    v-model="
                                                        form.txt_fonctionPM_Mutation
                                                    "
                                                    id="txt_fonctionPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_telephonePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Téléphone Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_telephonePM"
                                                    v-model="
                                                        form.txt_telephonePM_Mutation
                                                    "
                                                    id="txt_telephonePM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_emailPM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Email</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="txt_emailPM"
                                                    v-model="
                                                        form.txt_emailPM_Mutation
                                                    "
                                                    id="txt_emailPM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPM_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_adressePM"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Adresse Sciège </label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_adressePM"
                                                    v-model="
                                                        form.txt_adressePM_Mutation
                                                    "
                                                    id="txt_adressePM"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 

                                        <!-- Mutation vers Etat -->
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="slt_etablissementPA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Etablissement</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_etablissementPA"
                                                    v-model="form.slt_etablissementPA_Mutation" 
                                                    id="selectePiece"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option  value=""> 
                                                         
                                                    </option>
                                                    <option value="Sanitaire">
                                                        Sanitaire
                                                    </option>
                                                    <option value="Scolaire">
                                                        Scolaire
                                                    </option>
                                                    <option value="Millitaire">
                                                        Millitaire
                                                    </option>
                                                    <option value="Administratif">
                                                        Administratif
                                                    </option>
                                                    <option value="Aéroportuaire">
                                                        Aéroportuaire
                                                    </option>
                                                    <option value="Sportif">
                                                        Sportif
                                                    </option>
                                                    <option value="Universitaire">
                                                        Universitaire
                                                    </option>
                                                    <option value="Autres">
                                                        Autres
                                                    </option> 
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_personneResponsablePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Personne Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_personneResponsablePA"
                                                    v-model="
                                                        form.txt_personneResponsablePA_Mutation
                                                    "
                                                    id="txt_personneResponsablePA" 
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_fonctionResponsablePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Fonction Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_fonctionResponsablePA"
                                                    v-model="
                                                        form.txt_fonctionResponsablePA_Mutation
                                                    "
                                                    id="txt_fonctionResponsablePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_telephonePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Téléphone</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_telephonePA"
                                                    v-model="
                                                        form.txt_telephonePA_Mutation
                                                    "
                                                    id="txt_telephonePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_emailPA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Email</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="txt_emailPA"
                                                    v-model="
                                                        form.txt_emailPA_Mutation
                                                    "
                                                    id="txt_emailPA" 
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPA_Acq" class="sm:col-span-1">
                                            <label
                                                for="txt_ministèreTutelePA"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Ministère Tutelle</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_ministèreTutelePA"
                                                    v-model="
                                                        form.txt_ministèreTutelePA_Mutation
                                                    "
                                                    id="txt_ministèreTutelePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 

                                        <!--    Partie assigné à chaque Types de mutation -->
                                        <div class="sm:col-span-1">
                                            <label
                                                for="TypesMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Actes</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    type="select"
                                                    name="txt_typeActeMutation"
                                                    v-model="
                                                        form.txt_typeActeMutation
                                                    "
                                                    id="TypesMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @charge="mutationNotaire"
                                                > 
                                                    <option value=""></option>
                                                    <option value="Acte Notarie">Acte Notarié</option> 
                                                    <option value="Acte Sous Seing Privé">Acte sous Seing Privé</option> 
                                                    <option value="Acte de donation">Acte de donation</option>  
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="show" class="sm:col-span-1">
                                            <label
                                                for="NomNotaireMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Nom Notaire</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nomNotaireMutation"
                                                    v-model="
                                                        form.txt_nomNotaireMutation
                                                    "
                                                    id="NomNotaireMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="DateMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Date Mutation</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_dateMutation"
                                                    v-model="
                                                        form.dt_dateMutation
                                                    "
                                                    id="DateMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="typeJugementMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Jugement</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_jugementMutation"
                                                    v-model="
                                                        form.txt_jugementMutation
                                                    "
                                                    id="typeJugementMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="ObservationMutation"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Observation</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_observationMutation"
                                                    v-model="
                                                        form.txt_observationMutation
                                                    "
                                                    id="ObservationMutation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div  class="sm:col-span-1"> 
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
                                                    px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                    outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                    focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
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

