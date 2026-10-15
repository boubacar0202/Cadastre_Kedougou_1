<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";  // Utilisation de router au lieu de Inertia
import MazBtn from "maz-ui/components/MazBtn"; 
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import { onMounted, ref, watch } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";

// Configuration du layout
defineOptions({ layout: DefaultLayout }); 

// ════════════════════════════════════════════
// 1. État local (fichier, date, etc.)
// ════════════════════════════════════════════
const fichierPDF = ref(null);
const today = new Date().toISOString().split('T')[0];

// ════════════════════════════════════════════
// 2. Gestion du fichier PDF
// ════════════════════════════════════════════
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (file.type !== "application/pdf") {
        toast.error("Veuillez sélectionner un fichier PDF valide.");
        event.target.value = "";
        return;
    }

    if (file.size > 100 * 1024 * 1024) { // 100 Mo
        toast.error("Le fichier dépasse 100 Mo !");
        event.target.value = "";
        return;
    }

    fichierPDF.value = file;
    form.fichierPDF = file; 
}

// ════════════════════════════════════════════
// 3. Formulaire (useForm Inertia)
// ════════════════════════════════════════════
const form = useForm({
    txt_numdordre: "",
    txt_numbordereau: "",
    txt_numdossier: "",
    txt_caractere: "",
    dt_datearrivee: "",
    txt_typecourrier: "",
    txt_numcourier: "",
    dt_datecourier: "",
    txt_reference: "",
    txt_categorie: "",
    txt_designation: '',
    txt_prenomNom: "",
    txt_numLot: "",
    txt_superficie: "",
    txt_numTF: "",
    txt_lotissement: "",
    dt_date: "",
    txt_heure: "",
    txt_lieu: "",
    txt_nombrepiece: "",
    txt_objet: "",
    txt_expediteur: "",
    txt_agenttraiteur: "",
    txt_observation: "",
    fichierPDF: null,
});

// ════════════════════════════════════════════
// 4. Génération automatique du numéro de dossier (stable par année)
// ════════════════════════════════════════════
const anneeCourante = ref(null);
const numeroGenere = ref(null);

// Fonction utilitaire pour mettre à jour la date dans le numéro affiché
const mettreAJourDate = () => {
    if (!form.dt_datearrivee || numeroGenere.value === null) return;
    const dateObj = new Date(form.dt_datearrivee);
    if (isNaN(dateObj.getTime())) return;
    const day = String(dateObj.getDate()).padStart(2, "0");
    const month = String(dateObj.getMonth() + 1).padStart(2, "0");
    const year = dateObj.getFullYear();
    const dateFormatee = `${day}-${month}-${year}`;
    form.txt_numdordre = `${numeroGenere.value}/${dateFormatee}`;
};

// Récupération du prochain numéro d'ordre pour une année donnée
const fetchNextDossier = async (annee) => {
    // Si l'année est la même que celle déjà traitée, on met juste à jour la date
    if (annee === anneeCourante.value && numeroGenere.value !== null) {
        mettreAJourDate();
        return;
    }

    // Vérification de l'année
    if (!annee || isNaN(annee)) {
        toast.error("❌ Année invalide pour la génération du numéro de dossier.");
        return;
    }

    try {
        const response = await axios.get(route("arrivee.next", annee));
        const numero = response.data.num_dordre;
        if (!numero) throw new Error("Le serveur n'a pas retourné de numéro.");

        // Mémoriser l'année et le numéro
        anneeCourante.value = annee;
        numeroGenere.value = numero;

        // Mettre à jour le champ avec la date actuelle
        mettreAJourDate();
        toast.success(`✅ Numéro de dossier généré : ${form.txt_numdordre}`);

    } catch (error) {
        console.error("❌ Erreur lors de la génération :", error);

        let errorMsg = "❌ Erreur lors de la génération du numéro de dossier.";
        if (error.response) {
            if (error.response.status === 404) {
                errorMsg = "❌ Année non trouvée dans le système. Veuillez contacter l'administrateur.";
            } else if (error.response.status === 422) {
                const errors = error.response.data.errors;
                if (errors && errors.annee) {
                    errorMsg = `❌ ${errors.annee.join(', ')}`;
                } else {
                    errorMsg = "❌ Données invalides pour la génération.";
                }
            } else {
                errorMsg = `❌ Erreur serveur (${error.response.status}). Veuillez réessayer.`;
            }
        } else if (error.request) {
            errorMsg = "❌ Impossible de contacter le serveur. Vérifiez votre connexion.";
        } else {
            errorMsg = error.message || "❌ Une erreur inattendue s'est produite.";
        }
        toast.error(errorMsg);

        // Fallback : numéro temporaire (uniquement si aucun numéro n'existe)
        if (!form.txt_numdordre) {
            const date = new Date(form.dt_datearrivee) || new Date();
            const day = String(date.getDate()).padStart(2, "0");
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const year = date.getFullYear();
            const dateFormatee = `${day}-${month}-${year}`;
            form.txt_numdordre = `00001/${dateFormatee}`;
            toast.warning("⚠️ Numéro temporaire généré. Veuillez vérifier auprès de l'administrateur.");
            anneeCourante.value = year;
            numeroGenere.value = "00001";
        }
    }
};

// Watch sur la date : ne régénère que si l'année change
watch(() => form.dt_datearrivee, (nouvelleDate) => {
    if (!nouvelleDate) return;
    const dateObj = new Date(nouvelleDate);
    if (isNaN(dateObj.getTime())) {
        toast.error("❌ La date saisie est invalide.");
        return;
    }
    const annee = dateObj.getFullYear();

    if (annee !== anneeCourante.value) {
        fetchNextDossier(annee);
    } else {
        mettreAJourDate();
    }
});

// Appel initial au montage (si une date est déjà pré-remplie)
onMounted(() => {
    if (form.dt_datearrivee) {
        const annee = new Date(form.dt_datearrivee).getFullYear();
        fetchNextDossier(annee);
    }
});

// ════════════════════════════════════════════
// 5. Gestion des champs conditionnels (Catégorie, Bail, etc.)
// ════════════════════════════════════════════
const show = ref(false);
const handleCategorieChange = () => {
    show.value = form.txt_categorie === "Convocation - Invitation";
};
watch(() => form.txt_categorie, (newValue) => {
    show.value = newValue === "Convocation - Invitation";
});

const showBail = ref(false); 
watch(
    [() => form.txt_designation, () => form.txt_typecourrier],
    ([designation, typecourrier]) => {
        showBail.value =
            (designation === "Demande de Régularisation par voie de Bail" || designation === "Demande de CIC") &&
            typecourrier !== "BE";
    }
);

// ════════════════════════════════════════════
// 6. Listes déroulantes (Catégories / Désignations)
// ════════════════════════════════════════════
const designationsParCategorie = {
    'Demande SERVICES': ['Morcellements', 'Réquisition d\'immatriculation', 'Demande Avis Technique', 'Demande de terrain/Echange', 'Prospection de terrain', 
        'Autorisation de construction', 'Demande d\'extraits de plan', 'Autorisation de lotir', 'Demande d\'états des lieux', 'Demande de délimitation', 'Demande de reconstruction', 
        'Réquisition DSCOS', 'Tribunal', 'Litiges','Demande de situation foncière', 'Demande de Cession définitive', 'Demande d\'évaluation',
        'Demande de Cession définitive a Titre Gratuit', 'Demande de Régularisation par voie de Bail', 'Demande d\'attestation du Cadastre', 
        'Réceptions de lotissements', 'Demande de CIC', 'Duplication de CIC', 'Demande de Titre foncier', 
        'Autorisation de morceler'
    ],
    'Convocation - Invitation': ['Réunion', 'Audience Publique', 'Alerte', 'Visite de site', 
        'Rencontre', 'Randonnée', 'Marche', 'Session', 'Congré', 'Cérémonie', 'Inauguration',
        'Journée dédiée', 'Forum', 'Formation', 'Séminaire', 'Caravane', 'Atelier'
    ],
    'Information': ['Note de service', 'Circulaire', 'Rapport', 
        'Transmission lettre', 'Document administratif', 'Texte Juridique',
        'Lettre', 'Rapport d\'étude impact', 'PV - Compte - rendu', 'Texte juridique', 'Décision - Arrêté', 'Délibération', 'Résolution', 'Autres'
    ],
    'Réclamation - Signalement': ['Dénonciation', 'Plainte', 'Alerte'],
    'Réquisition - Instruction': ['Réquisition matériel', 'Instruction à suivre', 'DE'],
};

const designations = ref([]);
watch(() => form.txt_categorie, (newCategorie) => {
    designations.value = designationsParCategorie[newCategorie] || [];
    form.txt_designation = '';
});

// ════════════════════════════════════════════
// 7. Construction automatique de la référence
// ════════════════════════════════════════════
watch(
    () => [form.txt_numcourier, form.dt_datecourier],
    ([newNum, newDate]) => {
        if (!newDate) return;
        const date = new Date(newDate);
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        const dateFormatee = `${day}-${month}-${year}`;
        form.txt_reference = `${newNum} du ${dateFormatee}`;
    }
);

// ════════════════════════════════════════════
// 8. Soumission du formulaire (avec CSRF géré par Inertia)
// ════════════════════════════════════════════
const submitForm = function () {
    console.log("📤 Envoi du formulaire :", form);
    console.log("✅ Données finales :", form.data()); 

    form.post(route('arrivee.store'), {
        forceFormData: true,
        onSuccess: () => {
            toast.success('Enregistré avec succès');
            router.visit(route('arrivee.create'), { replace: true });
        },
        onError: (errors) => {
            Object.values(errors).forEach(msg => toast.error(msg));
        }
    });
};
</script>

<template>
    <Head title="Courrier Arrivées" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Enregistrement des Courriers Arrivées
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary flex items-center justify-between rounded-t-xl gap-2">
                            <h2 class="text-2xl leading-tight text-white font-bold">
                                Formulaire d'enregistrement des Courriers Arrivés
                            </h2>
                        </div>

                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm">
                          
                            <div class="p-6">
                                <h5 class="text-lg text-primary-txt font-bold mb-4">
                                    Références du courrier
                                </h5>
                                <div class="mb-6">
                                    <!-- Ligne 1 -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="N° Dordre Arrivée"
                                            name="txt_numdordre"
                                            autocomplete="on"
                                            v-model="form.txt_numdordre"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° BE"
                                            name="txt_numbordereau"
                                            autocomplete="on"
                                            v-model="form.txt_numbordereau"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° Dossier Foncier"
                                            name="txt_numdossier"
                                            autocomplete="off"
                                            v-model="form.txt_numdossier"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Date Réception"
                                            name="dt_datearrivee"
                                            autocomplete="off"
                                            type="date"
                                            required
                                            v-model="form.dt_datearrivee"
                                        />
                                        <BaseSelect
                                            class="sm:col-span-1"
                                            label="Type de Courrier"
                                            name="txt_typecourrier"
                                            required
                                            v-model="form.txt_typecourrier"
                                            :options="[
                                                { value: 'Individuel', label: 'Individuel' },
                                                { value: 'BE', label: 'BE' },
                                                { value: 'BE à réponses Multiples', label: 'BE à réponses Multiples' },
                                            ]"
                                        />
                                    </div>

                                    <br />

                                    <!-- Ligne 2 -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="N° Courrier du dossier"
                                            name="txt_numcourier"
                                            autocomplete="off"
                                            required
                                            v-model="form.txt_numcourier"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Date Courrier"
                                            name="dt_datecourier"
                                            type="date"
                                            :max="today"
                                            autocomplete="off"
                                            required
                                            v-model="form.dt_datecourier"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Réf.Courrier Arrivée"
                                            name="txt_reference"
                                            autocomplete="off"
                                            required
                                            v-model="form.txt_reference"
                                        />
                                        <BaseSelect
                                            class="sm:col-span-1"
                                            label="Catégorie"
                                            name="txt_categorie"
                                            autocomplete="off"
                                            required
                                            id="txt_categorie"
                                            @change="handleCategorieChange"
                                            v-model="form.txt_categorie"
                                            :options="[
                                                { value: 'Demande SERVICES', label: 'Demande SERVICES' },
                                                { value: 'Convocation - Invitation', label: 'Convocation - Invitation' },
                                                { value: 'Information', label: 'Information' },
                                                { value: 'Réclamation - Signalement', label: 'Réclamation - Signalement' },
                                                { value: 'Réquisition - Instruction', label: 'Réquisition - Instruction' },
                                            ]"
                                        />
                                        <BaseSelect
                                            class="sm:col-span-2"
                                            label="Désignation"
                                            name="txt_designation"
                                            autocomplete="off"
                                            required
                                            id="txt_designation"
                                            v-model="form.txt_designation"
                                            :options="designations.map(designation => ({
                                                value: designation,
                                                label: designation
                                            }))"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="Prénom et Nom"
                                            name="txt_prenomNom"
                                            autocomplete="off"
                                            v-model="form.txt_prenomNom"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° Lot"
                                            name="txt_numLot"
                                            autocomplete="off"
                                            v-model="form.txt_numLot"
                                        />
                                       <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="Superficie (m²)"
                                            name="txt_superficie"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            autocomplete="off"
                                            v-model="form.txt_superficie"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° Titre Mère"
                                            name="txt_numTF"
                                            autocomplete="off"
                                            v-model="form.txt_numTF"
                                        />
                                        <BaseInput
                                            v-if="showBail"
                                            class="sm:col-span-2"
                                            label="Lotissement"
                                            name="txt_lotissement"
                                            autocomplete="on"
                                            v-model="form.txt_lotissement"
                                        />
                                        <BaseInput
                                            v-if="show"
                                            class="sm:col-span-1"
                                            label="Date évenement"
                                            name="dt_date"
                                            autocomplete="off"
                                            v-model="form.dt_date"
                                            type="date"
                                        />
                                        <BaseInput
                                            v-if="show"
                                            class="sm:col-span-1"
                                            label="Heure évenement"
                                            name="txt_heure"
                                            autocomplete="off"
                                            v-model="form.txt_heure"
                                            type="time"
                                        />
                                        <BaseInput
                                            v-if="show"
                                            class="sm:col-span-1"
                                            label="Lieu évenement"
                                            name="txt_lieu"
                                            autocomplete="off"
                                            v-model="form.txt_lieu"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Nombre de pièces"
                                            name="txt_nombrepiece"
                                            autocomplete="off"
                                            v-model="form.txt_nombrepiece"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Objet"
                                            name="txt_objet"
                                            autocomplete="on"
                                            v-model="form.txt_objet"
                                        />
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="Expéditeur"
                                            name="txt_expediteur"
                                            autocomplete="on"
                                            v-model="form.txt_expediteur"
                                        />
                                        <BaseSelect
                                            class="sm:col-span-1"
                                            label="Agent Traiteur"
                                            name="txt_agenttraiteur"
                                            autocomplete="off"
                                            required
                                            id="txt_agenttraiteur"
                                            v-model="form.txt_agenttraiteur"
                                            :options="[
                                                { value: 'Saliou FAYE', label: 'Saliou FAYE' },
                                                { value: 'Moustapha Diop', label: 'Moustapha Diop' },
                                                { value: 'Daouda Ndiaye', label: 'Daouda Ndiaye' },
                                                { value: 'Abdoulaye Camara', label: 'Abdoulaye Camara' },
                                                { value: 'Oumar Diop', label: 'Oumar Diop' },
                                            ]"
                                        />
                                        <BaseSelect
                                            class="sm:col-span-2"
                                            label="Caractères"
                                            name="txt_caractere"
                                            autocomplete="off"
                                            id="txt_caractere"
                                            v-model="form.txt_caractere"
                                            :options="[
                                                { value: 'Confidentiel', label: 'Confidentiel' },
                                                { value: 'Urgent', label: 'Urgent' },
                                                { value: 'Secret', label: 'Secret' },
                                            ]"
                                        />
                                        <BaseInput
                                            class="sm:col-span-2"
                                            label="Observation"
                                            name="txt_observation"
                                            autocomplete="off"
                                            v-model="form.txt_observation"
                                        />
                                        <BaseInput
                                            type="file"
                                            class="sm:col-span-2"
                                            label="Importer le Fichier PDF"
                                            name="fichierPDF"
                                            accept="application/pdf"
                                            @change="handleFileUpload"
                                        />
                                    </div>
                                    <br />
                                </div>

                                <!-- Bouton de soumission -->
                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn
                                        type="submit"
                                        no-shadow
                                        no-hover-effect
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

<style scoped>
::v-deep(.custom-option:hover),
::v-deep(.custom-option:focus),
::v-deep(.custom-option[aria-selected="true"]) {
    background-color: #c7d2fe;
    color: #1e293b;
}
</style>