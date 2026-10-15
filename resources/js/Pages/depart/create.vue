<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import MazSelect from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { onMounted, ref, watch, watchEffect, computed  } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';  
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";

defineOptions({ layout: DefaultLayout }); 
const props = defineProps({
    arrivee: Object,    
    arrivees: Array,
    departs: Array,
    depart: Object,
});
 
// show bay
const showBail = ref(false); 

// useForm
const form = useForm({
    //  courierdepart 
    txt_numdordrecd:"",
    txt_caracterecd:"",
    dt_datecouriercd:"",
    txt_numdossiercd:"",
    txt_avisReponsecd:"",
    txt_typecourriercd:"",
    txt_categoriecd:"",
    txt_referencecourierarriveecd:"",
    txt_referencecourierdepartcd:"",
    txt_nombrepiececd:"",
    txt_referencecd:"",

    txt_prenomNomcd:"",
    txt_numLotcd:"",
    txt_superficiecd:"",
    txt_numTFcd:"",
    txt_lotissementcd:"",

    txt_objetcd:"",
    txt_destinatairecd:"",
    dt_dateenvoicd:"",
    txt_referencereceptioncd:"",
    txt_observationcd:"",
    txt_dureetraitementcd:"",
    fichierPDFcd: null,
});
 
const fichierPDFcd = ref(null);  

// Show des demande de régularisation par voie de bail 
watch(
    [() => form.txt_objetcd, () => form.txt_typecourriercd],
    ([objetcd, typecourrier]) => {

            showBail.value =
                objetcd === "Demande de Régularisation par voie de Bail" || objetcd === "DEMANDE DE BAIL"  || objetcd === "Demande de CIC" && 
            typecourrier !== "BE";
    }
);

// Récuperer le fichier PDF 
function handleFileUploadcd(event) {
    const file = event.target.files[0];

    if (!file) return;

    if (file.type !== "application/pdf") {
        toast.error("Veuillez sélectionner un fichier PDF valide.");
        event.target.value = ""; // reset input
        return;
    }

    if (file.size > 100 * 1024 * 1024) { // 100 Mo
        toast.error("Le fichier dépasse 100 Mo !");
        event.target.value = ""; // reset input
        return;
    }

    fichierPDFcd.value = file;
    form.fichierPDFcd = file;

    console.log("Fichier PDF sélectionné :", fichierPDFcd.value);
}

// Comotement de la catégories
const showcd = ref(false);
const handleCategorieChangecd = () => {
    showcd.value = form.txt_categoriecd === "Reponse à un Courrier arrivé" || form.txt_categoriecd === "Dossier Retourne";
};
watch(() => form.txt_categoriecd, (newValue) => {
    showcd.value = newValue === "Reponse à un Courrier arrivé" || newValue === "Dossier Retourne";
});
  
// Automaiser le Numéro d'ordre
const fetchNextDossier = async (annee) => {
    try {
        const response = await axios.get(`/depart/next/${annee}`);
        const numero = response.data.num_dordre;

        // ✅ Formater la date au format DD-MM-YYYY
        const date = new Date(form.dt_datecouriercd);
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        const dateFormatee = `${day}-${month}-${year}`;

        form.txt_numdordrecd = `${numero}/${dateFormatee}`;
        console.log("✅ Numéro généré :", form.txt_numdordrecd);

    } catch (error) {
        console.error("❌ Erreur :", error);

        const date = new Date();
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        const dateFormatee = `${day}-${month}-${year}`;

        form.txt_numdordrecd = `0001/${dateFormatee}`;
    }
};

// Regénérer automatiquement quand la date change
watch(() => form.dt_datecouriercd, (nouvelleDate) => {
    if (nouvelleDate) {
        const annee = new Date(nouvelleDate).getFullYear();
        fetchNextDossier(annee);
    }
}); 
  
// Références disponibles 
const references = ref([]);  
const referenceToExpediteur = ref({});
const referenceToObject = ref({}); 
const referenceToReception = ref({});
const referenceToNombreDePiece = ref({});
const referenceToPrenomNom = ref({});
const referenceToNumLot = ref({});
const referenceToSuperficie = ref({});
const referenceToNumTF = ref({});
const referenceToLotissement = ref({});
const referenceToTypeCourrier = ref({});
const referenceToNumDossier = ref({});
  
// Filtrage dynamique des arrivées par catégorie 
// Surveille le changement de catégorie pour charger les références liées
watch(() => form.txt_categoriecd, async (newCategorie) => {
    if (!newCategorie) return;

    try {
        const res = await axios.post('/fetch-references-arrivee', {
            categorie: newCategorie
        });

        // ✅ Mise à jour des références 
        references.value = res.data.references || []; 

        // ✅ Mapping de référence vers expéditeur 
        referenceToExpediteur.value = res.data.map_ref_to_expediteur || {};
        referenceToObject.value = res.data.map_ref_to_objet || {};
        referenceToReception.value = res.data.map_ref_to_reception || {};
        referenceToNombreDePiece.value = res.data.map_ref_to_nombrePiece || {};
        referenceToPrenomNom.value = res.data.map_ref_to_prenomNom || {};
        referenceToNumLot.value = res.data.map_ref_to_numLot || {};
        referenceToSuperficie.value = res.data.map_ref_to_superficie || {};
        referenceToNumTF.value = res.data.map_ref_to_numTF || {};
        referenceToLotissement.value = res.data.map_ref_to_lotissement || {};
        referenceToTypeCourrier.value = res.data.map_ref_to_typeCourrier || {};
        referenceToNumDossier.value = res.data.map_ref_to_numDossier || {};
  
        form.txt_referencecourierarriveecd = '';
        form.txt_destinatairecd = '';
        form.txt_objetcd = ''; 
        form.txt_referencereceptioncd = ''; 
        form.txt_nombrepiececd = '';
        form.txt_prenomNomcd = '';
        form.txt_numLotcd = '';
        form.txt_superficiecd = '';
        form.txt_numTFcd = '';
        form.txt_lotissementcd = '';
        form.txt_typecourriercd = '';
        form.txt_numdossiercd = '';

    } catch (e) {
        console.error('❌ Erreur lors de la récupération des références :', e);
        references.value = []; 
        referenceToExpediteur.value = {};
        referenceToObject.value = {};
        referenceToReception.value = {};
        referenceToNombreDePiece.value = {};
        referenceToPrenomNom.value = {};
        referenceToNumLot.value = {};
        referenceToSuperficie.value = {};
        referenceToNumTF.value = {};
        referenceToLotissement.value = {};
        referenceToTypeCourrier.value = {};
        referenceToNumDossier.value = {};
     }
});
 
// Surveille le changement de la référence choisie
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToExpediteur.value[selectedRef]) {
        form.txt_destinatairecd = referenceToExpediteur.value[selectedRef];
    } else {
        form.txt_destinatairecd = '';
    }
});

// Surveiller le categorie et récupérer l'objet 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToObject.value[selectedRef]) {
        form.txt_objetcd = referenceToObject.value[selectedRef];
    } else {
        form.txt_objetcd = '';
    }
});

// Surveiller le categorie et récupérer le nombre de piece 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToNombreDePiece.value[selectedRef]) {
        form.txt_nombrepiececd = referenceToNombreDePiece.value[selectedRef];
    } else {
        form.txt_nombrepiececd = '';
    }
});

// Surveiller le categorie et récupérer le penomNom 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToPrenomNom.value[selectedRef]) {
        form.txt_prenomNomcd = referenceToPrenomNom.value[selectedRef];
    } else {
        form.txt_prenomNomcd = '';
    }
});

// Surveiller le categorie et récupérer le numLot 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToNumLot.value[selectedRef]) {
        form.txt_numLotcd = referenceToNumLot.value[selectedRef];
    } else {
        form.txt_numLotcd = '';
    }
});

// Surveiller le categorie et récupérer la Superficie 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToSuperficie.value[selectedRef]) {
        form.txt_superficiecd = referenceToSuperficie.value[selectedRef];
    } else {
        form.txt_superficiecd = '';
    }
});

// Surveiller le categorie et récupérer le numTF 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToNumTF.value[selectedRef]) {
        form.txt_numTFcd = referenceToNumTF.value[selectedRef];
    } else {
        form.txt_numTFcd = '';
    }
});

// Surveiller le categorie et récupérer le lotissement 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToLotissement.value[selectedRef]) {
        form.txt_lotissementcd = referenceToLotissement.value[selectedRef];
    } else {
        form.txt_lotissementcd = '';
    }
});

// Surveiller le categorie et récupérer le type de courrier 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToTypeCourrier.value[selectedRef]) {
        form.txt_typecourriercd = referenceToTypeCourrier.value[selectedRef];
    } else {
        form.txt_typecourriercd = '';
    }
});

// Surveiller le categorie et récupérer le numDossier 
watch(() => form.txt_referencecourierarriveecd, (selectedRef) => {
    if (selectedRef && referenceToNumDossier.value[selectedRef]) {
        form.txt_numdossiercd = referenceToNumDossier.value[selectedRef];
    } else {
        form.txt_numdossiercd = '';
    }
});
 
// reupèration references courrier depart
watch(
  () => [form.txt_numdordrecd, form.dt_datecouriercd],
  ([newNum, newDate]) => {
    if (!newNum || !newDate){
        form.txt_referencecd = '';
        return;
    }  

    // Sécuriser le parsing de la date
    const parseDate = (str) => {
        if (!str) return null;
        // accepter "YYYY-MM-DD" ou "DD-MM-YYYY"
        let parts = str.includes("-") ? str.split("-") : str.split("/");
        if (parts.length !== 3) return null;

        if (parts[0].length === 4) {
            // format "YYYY-MM-DD"
            return new Date(`${parts[0]}-${parts[1]}-${parts[2]}`);
        } else {
            // format "DD-MM-YYYY"
            return new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
        }
    };

    const date = parseDate(newDate);
    if (!date || isNaN(date.getTime())) {
        form.txt_referencecd = "Date invalide";
        return;
    }

    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    const dateFormatee = `${day}-${month}-${year}`; // "29-08-2025"

    // Extraire la partie après le dernier "/"
    const partsNumero = newNum.split("/");
    const complementRef = partsNumero[partsNumero.length - 2].trim();

    form.txt_referencecd = `${complementRef}/MFB/DGID/CSF/CSF-KDG/BCAD du ${dateFormatee}`;

  }
);
  
// 🔹 1. Extraire et normaliser la date d'arrivée depuis la référence
const dateArrivee = ref(''); 
watch(
    () => form.txt_referencecourierarriveecd,
    (newReference) => {
        if (!newReference) {
            dateArrivee.value = '';
            return;
        }

        const parts = newReference.split(' du ');
        if (parts.length === 2) {
            let dateString = parts[1].trim();
            dateString = normalizeDate(dateString);
            dateArrivee.value = dateString;
            console.log("✅ Date extraite normalisée :", dateString);
        } else {
            dateArrivee.value = '';
            console.warn("⚠️ Format inattendu :", newReference);
        }
    }
);

// // 🔹 2. Fonction intelligente de normalisation
function normalizeDate(dateStr) {
    if (!dateStr) return "";

    // Remplacer ., / par -
    dateStr = dateStr.replace(/[./]/g, "-");

    // Vérifier si format AAAA-MM-JJ
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        return dateStr; // déjà ISO
    }

    // Vérifier si format JJ-MM-AAAA
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
        const [jour, mois, annee] = dateStr.split("-");
        return `${annee}-${mois}-${jour}`;
    }

    // Vérifier si format JJ-MM-AA (rare mais possible)
    if (/^\d{2}-\d{2}-\d{2}$/.test(dateStr)) {
        const [jour, mois, annee] = dateStr.split("-");
        return `20${annee}-${mois}-${jour}`;
    }

    // Sinon, invalide
    return "";
}

// 🔹 3. Calcul automatique de la durée de traitement
watch(
    () => [dateArrivee.value, form.dt_dateenvoicd, form.txt_categoriecd],
    ([arrivee, envoie, categorie]) => {
        if (!["Reponse à un Courrier arrivé", "Dossier Retourne"].includes(categorie)) {
        form.txt_dureetraitementcd = "";
        return;
        }

        if (!arrivee || !envoie) return;

        // ✅ Convertir "JJ/MM/AAAA" → "AAAA-MM-JJ" si nécessaire
        const toISO = (date) =>
        /^\d{2}\/\d{2}\/\d{4}$/.test(date)
            ? date.split("/").reverse().join("-")
            : date;

        const dArr = new Date(toISO(arrivee));
        const dEnv = new Date(toISO(envoie));

        const diff = Math.ceil((dEnv - dArr) / (1000 * 3600 * 24));

        form.txt_dureetraitementcd = diff <= 0 ? "24 heures" : `${diff} jours`;
    }
);
 
// Soumission du formulaire
const submitForm = function () {  // Ajoutez `async` ici 
    const today = new Date().toISOString().split('T')[0];

    if (form.dt_dateenvoicd > today) {
        toast.error("La date d'envoi dépasse d'aujourd'hui. CORRIGER");
        return;
    }
    // Formulaire Laravel
    form.post(route('depart.store'), {   
        onSuccess: () => {
            toast.success('Enregistré avec succès')
            Inertia.visit(route("depart.create"), { replace: true });
        },
        onError: (errors) => {
        
            Object.values(errors).forEach(msg => toast.error(msg))
        }
    })

};

</script>

<template>
    <Head title="Courrier Départ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Enregistrement des Courriers Départs 
            </h2>

        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md sm:rounded-xl">
                        <!-- En-tête du formulaire -->
                        <div class="p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Formulaire d'enregistrement des Courriers Départs</h1>
                        </div>
                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm">
                            <div class="p-6 rounded-lg">
                                <!-- Section Parcelle --> 
                                <div class="mb-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2"> 
                                        <BaseInput
                                            class="sm:col-span-2 bg-gray-100 cursor-not-allowed"
                                            id="txt_numdordrecd"
                                            v-model="form.txt_numdordrecd"
                                            label="N° Dordre"
                                            type="text"
                                            :required="true"
                                            readonly
                                            :disabled="true"
                                            autocomplete="off" 
                                        />

                                        <BaseInput
                                            id="dt_datecouriercd"
                                            v-model="form.dt_datecouriercd"
                                            label="Date Courrier"
                                            type="date"
                                            autocomplete="off"
                                        />
 
                                        <BaseInput
                                            v-if="showBail" 
                                            id="txt_numdossiercd"
                                            v-model="form.txt_numdossiercd"
                                            label="N° Dossier Foncier" 
                                            autocomplete="off"
                                        /> 
 
                                        <BaseSelect  
                                            v-if="showBail" 
                                            id="txt_avisReponsecd"
                                            v-model="form.txt_avisReponsecd"
                                            label="Avis Réponse" 
                                            :options="[
                                                { value: 'Avis Favorable', label: 'Avis Favorable' },
                                                { value: 'Avis Défavorable', label: 'Avis Défavorable' }, 
                                            ]"
                                        /> 

                                        <BaseSelect 
                                            class="sm:col-span-1"
                                            label="Type de Courriercd"
                                            name="txt_typecourriercd"  
                                            required
                                            v-model="form.txt_typecourriercd"
                                            :options="[
                                                { value: 'Individuel', label: 'Individuel' },
                                                { value: 'BE', label: 'BE' }, 
                                                { value: 'BE à réponses Multiples', label: 'BE à réponses Multiples' },
                                            ]"
                                        />  
                                    </div>
                                    <br />
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2"> 
                                        <BaseSelect
                                            class="sm:col-span-1"
                                            id="txt_categoriecd"
                                            v-model="form.txt_categoriecd"
                                            label="Catégorie"
                                            :options="[
                                                { value: 'Reponse à un Courrier arrivé', label: 'Reponse à un Courrier arrivé' },
                                                { value: 'Demande', label: 'Demande' },
                                                { value: 'Transmission de Documents', label: 'Transmission de Documents' },
                                                { value: 'Information', label: 'Information' },
                                                { value: 'Alerte', label: 'Alerte' },
                                                { value: 'Signalement', label: 'Signalement' },
                                                { value: 'Dossier Retourne', label: 'Dossier Retourné' }
                                            ]"
                                            placeholder="Sélectionner une catégorie"
                                            @change="handleCategorieChangecd"
                                        /> 

                                        <BaseSelect
                                            v-if="showcd"
                                            id="txt_referencecourierarriveecd"
                                            v-model="form.txt_referencecourierarriveecd"
                                            label="Ref.Courrier Arrivée à Repondre"
                                            :options="references.map(ref => ({ value: ref, label: ref }))"
                                            placeholder="Choisir une référence"
                                        /> 

                                        <BaseInput
                                            id="txt_nombrepiececd"
                                            v-model="form.txt_nombrepiececd"
                                            label="Nbre de pièces"
                                            type="text"
                                            :required="true"
                                            autocomplete="off"
                                            min="1"
                                        />

                                        <BaseInput
                                            class="sm:col-span-2"
                                            id="txt_referencecd"
                                            v-model="form.txt_referencecd"
                                            label="Référence Courrier"
                                            type="text"
                                            :required="true"
                                            autocomplete="off"
                                        /> 

                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="Prénom et Nom"
                                            name="txt_prenomNomcd"  
                                            autocomplete="off"  
                                            v-model="form.txt_prenomNomcd"
                                        />   

                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="N° Lot"
                                            name="txt_numLotcd"  
                                            autocomplete="off"  
                                            v-model="form.txt_numLotcd"
                                        />  

                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="Superficie (m²)"
                                            name="txt_superficiecd"  
                                            autocomplete="off"  
                                            v-model="form.txt_superficiecd"
                                        />   

                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="N° Titre Mère"
                                            name="txt_numTFcd"  
                                            autocomplete="off"  
                                            v-model="form.txt_numTFcd"
                                        />   

                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-2"
                                            label="Lotissement"
                                            name="txt_lotissementcd"  
                                            autocomplete="on"  
                                            v-model="form.txt_lotissementcd"
                                        />    

                                        <BaseInput
                                            class="sm:col-span-2"
                                            id="txt_objetcd"
                                            v-model="form.txt_objetcd"
                                            label="Objet"
                                            type="text" 
                                            autocomplete="address-level2"
                                        /> 

                                        <BaseInput
                                            class="sm:col-span-2"
                                            id="txt_destinatairecd"
                                            v-model="form.txt_destinatairecd"
                                            label="Destinataire"
                                            type="text" 
                                            autocomplete="off"
                                        /> 

                                        <BaseInput
                                            class="sm:col-span-2"
                                            id="txt_referencereceptioncd"
                                            v-model="form.txt_referencereceptioncd"
                                            label="Ref. Décharge"
                                            type="text"
                                            autocomplete="off"
                                        />

                                        <BaseInput
                                            id="dt_dateenvoicd"
                                            v-model="form.dt_dateenvoicd"
                                            label="Date d'envoi"
                                            type="date"
                                            :required="true"
                                            autocomplete="off"
                                        /> 

                                        <BaseInput 
                                            v-if="showcd"
                                            id="txt_dureetraitementcd"
                                            v-model="form.txt_dureetraitementcd"
                                            label="Durrée de traitement"
                                            type="text"
                                            autocomplete="off"
                                            :disabled="true"
                                        /> 

                                        <BaseSelect
                                            class="sm:col-span-2"
                                            id="txt_caracterecd"
                                            v-model="form.txt_caracterecd"
                                            label="Caractères"
                                            :options="[
                                                { value: 'Connfidentiel', label: 'Confidentiel' },
                                                { value: 'Urgent', label: 'Urgent' },
                                                { value: 'Secret', label: 'Secret' }
                                            ]"
                                            placeholder="Sélectionner un caractère"
                                        /> 

                                        <BaseInput
                                            class="sm:col-span-2"
                                            id="txt_observationcd"
                                            v-model="form.txt_observationcd"
                                            label="Observation"
                                            type="text"
                                            autocomplete="off"
                                        /> 
                             
                                        <BaseInput 
                                            type="file"
                                            accept="application/pdf"
                                            class="sm:col-span-2"
                                            label="Importer le Fichier PDF" 
                                            name="fichier_PDFcd" 
                                            @change="handleFileUploadcd" 
                                        /> 
 
                                    </div>
                                    <br /> 
                                </div> 
 
                                <!-- Bouton de soumission -->
                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn 
                                        type="submit" no-shadow no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark 
                                            to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                            focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                            dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                            py-2.5 text-center me-2 mb-2"
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