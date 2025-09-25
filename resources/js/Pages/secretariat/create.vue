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
const slt_region = ref();
const slt_departement = ref();
const slt_arrondissement = ref();
const slt_commune = ref();

const departements = ref(null);
const arrondissements = ref(null);
const communes = ref(null);
const slt_dependant_domaine = ref(null); // ou ref({}) selon l'utilisation
const txt_num_section = ref('');
const txt_num_parcelle = ref('');
const txt_appartement = ref('');
const today = new Date().toISOString().split('T')[0];
const fichierPDF = ref(null);

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

const txt_nicad = computed(() => {
    if (slt_commune.value === 1) {
        return `13110100 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 2) {
        return `13120101 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 3) {
        return `13120102 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 4) {
        return `14120103 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 5) {
        return `13120104 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 6) {
        return `13120201 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 7) {
        return `13120202 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 8) {
        return `13210100 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 9) {
        return `13220101 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 10) {
        return `13220102 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 11) {
        return `13220201 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 12) {
        return `13220202 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 13) {
        return `13220103 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 14) {
        return `13310100 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    } else if (slt_commune.value === 15) {
        return `13320101 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 16) {
        return `13320102 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 17) {
        return `13320201 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 18) {
        return `13310202 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else if (slt_commune.value === 19) {
        return `13220203 ${txt_num_section.value} ${txt_num_parcelle.value} ${txt_appartement.value}`;
    }else {
        return `${txt_num_section.value} ${txt_num_parcelle.value}  ${txt_appartement.value}`;
    }
});

const props = defineProps({
    regions: {
        type: Array,
        default: () => [],
    },
});

// const activeTab = ref(1);
const activeTab = ref(""); // Valeur de la tab active

const form = useForm({
    //  Dossier
    txt_num_dossier: "",
    txt_num_dordre: "",
    slt_service_rendu: "",
    txt_etat_cession: "",
    txt_cession_definitive: "",
    dt_date_creation: "",
    // Terrain
    slt_region: "",
    slt_departement: "",
    slt_arrondissement: "",
    slt_commune: "",
    txt_lotissement: "",
    txt_HorsLotissement:"",
    txt_num_lotissement: "", 
    txt_num_section: "",
    txt_num_parcelle: "",
    txt_appartement:"",
    txt_nicad: "", 
    txt_num_titre: "",
    txt_titre_mere: "", // Supprimez l'une des occurrences
    nbr_surface: "",
    slt_document_admin: "",
    txt_num_deliberation: "",
    dt_date_deliberation: "",
    //  ReferenceCadastrale
    rd_immatriculation_terrain:"",
    slt_dependant_domaine: "",
    ussu_bornage: "",
    slt_lf: "",
    txt_num_requisition: "",
    txt_surface_bornage: "",
    dt_date_bornage: "",
    txt_nom_geometre: "",
    //  Titulaire
    slt_titulaire: "",
    txt_nationalite: "",
    slt_civilite: "",
    txt_prenom: "",
    txt_nom: "",
    slt_piece: "",
    txt_numPiece: "",
    dt_date_delivrance: "",
    dt_date_naissance: "",
    txt_lieu_naissance: "",
    txt_adresse: "",
    tel_telephone: "", 
    eml_email: "",
    txt_representant: "",
    tel_telRepresentant: "", 
        // Personne physique
    slt_categoriePM:"",
    txt_formJuridiquePM:"", 
    txt_denominationPM:"", 
    txt_nineaPM:"", 
    txt_perssonneRepresentantPM:"", 
    txt_fonctionPM:"", 
    txt_telephonePM: "", 
    txt_emailPM:"", 
    txt_adressePM:"", 
        // Personne morale
    slt_etablissementPA:"",
    txt_personneResponsablePA:"",
    txt_fonctionResponsablePA:"",
    txt_telephonePA:"",
    txt_emailPA:"",
    txt_ministèreTutelePA:"",

    fichierPDF: "",
});
 
const fetchNextDossier = async () => {
    try {
        const response = await axios.get("/dossier/next");
        form.txt_num_dossier = response.data.num_dossier || "";
        form.txt_num_dordre = response.data.num_dordre || "";
    } catch (error) {
        console.error("Erreur lors de la récupération du numéro de dossier :", error);
    }
};
onMounted(fetchNextDossier);
 

// Mettez à jour les watchers pour utiliser form.selectedRegion, etc.
watch(
    () => form.slt_region,
    (newValue) => {
        console.log("🔄 Région sélectionnée :", newValue);
        fetchDepartements();
    }
);

watch(
    () => form.slt_departement,
    (newValue) => {
        console.log("🔄 Département sélectionné :", newValue);
        fetchArrondissements();
    }
);

watch(
    () => form.slt_arrondissement,
    (newValue) => {
        console.log("🔄 Arrondissement sélectionné :", newValue);
        fetchCommunes();
    }
);

const fetchDepartements = async () => {
    if (!slt_region.value) {
        departements.value = [];
        return;
    }

    try {
        const response = await axios.get(`/departements/${slt_region.value}`);
        departements.value = response.data.departements;
        console.log("Départements: ", departements.value);
    } catch (error) {
        console.error("Erreur lors du chargement des départements :", error);
    }
};

const fetchArrondissements = async () => {
    if (!slt_departement.value) {
        arrondissements.value = [];
        return;
    }

    try {
        const response = await axios.get(
            `/arrondissements/${slt_departement.value}`
        );
        arrondissements.value = response.data.arrondissements;
        console.log("Arrondissements : ", arrondissements.value);
    } catch (error) {
        console.error("Erreur lors du chargement des arrondissements :", error);
    }
};

const fetchCommunes = async () => {
    if (!slt_arrondissement.value) {
        communes.value = [];
        return;
    }

    try {
        const response = await axios.get(
            `/communes/${slt_arrondissement.value}`
        );
        communes.value = response.data.communes;
        console.log("communes : ", communes.value);
    } catch (error) {
        console.error("Erreur lors du chargement des communes :", error);
    }
};

onMounted(() => {
    console.log("Les régions: ", props.regions);
    // Vous pouvez ajouter une logique pour initialiser les données ici
});


const show = ref(false);
const showSectionPP = ref(false); // Perssone Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // Etat
const handleSelectChange = () => {
    show.value = form.ussu_bornage === "Morcellement de Copropriété";
};
watch(() => form.ussu_bornage, (newValue) => {
    show.value = newValue === "Morcellement de Copropriété";
});

// charge titulaire Etat Perssone Morale Personne Physique
let hasShownError = false;
const handleSelectChangeTitulaire = () => {
    // Reset toutes les sections
    showSectionPP.value = false;
    showSectionPM.value = false;
    showSectionPA.value = false;

    switch (form.slt_titulaire) {
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
watch(() => form.slt_titulaire, (newValue) => {

    handleSelectChangeTitulaire(); 

    if (!["Personne Physique", "Personne Morale", "Etat"].includes(newValue)) {
            toast.error("Veuillez choisir un titulaire");
        }
    }
);

const errorMessage = ref("");
const validateInput = () => {
    const value = txt_appartement.value.toString();

    if (value.length > 3) {
        txt_appartement.value = value.slice(0, 3); // Coupe à 3 chiffres max
    }

    if (value.length < 3) {
        errorMessage.value = "❌ 3 chiffres.";
    } else {
        errorMessage.value = "";
    }
};

const errorMessageNumSection = ref("");
// Validation de l'input (saisie à 3 caractères uniquement)
const validateInputNumSection = () => {
    const value = txt_num_section.value.toString();

    // Si la longueur est plus grande que 3, on la coupe
    if (value.length > 3) {
        txt_num_section.value = value.slice(0, 3); // Limiter à 3 caractères
    }
    // Validation pour une saisie de 3 caractères exactement
    if (value.length < 3) {
        errorMessageNumSection.value = "❌ 3 chiffres.";
    } else {
        errorMessageNumSection.value = "";
    }
};

const errorMessageNumParcelle = ref("");

// Validation de l'input (saisie à 5 caractères uniquement)
const validateInputNumParcelle = () => {
    const value = txt_num_parcelle.value.toString();

    // Si la longueur est plus grande que 5, on la coupe
    if (value.length > 5) {
        txt_num_parcelle.value = value.slice(0, 5); // Limiter à 5 caractères
    }

    // Validation pour une saisie de 5 caractères exactement
    if (value.length < 5) {
        errorMessageNumParcelle.value = "❌ 5 chiffres.";
    } else {
        errorMessageNumParcelle.value = "";
    }
};
const mazTabs = [
    { label: "Terrain Non Immatriculé", disabled: false },
    {
        label: "Terrain Immatriculé",
        disabled: false,
    },
];

 
const submitForm = function () {  // Ajoutez `async` ici
    console.log("📤 Envoi du formulaire :", form);
    console.log("✅ Données finales envoyées à Laravel :", form.data());
    form.rd_immatriculation_terrain = activeTab.value || "";  // Mise à jour de la donnée
    form.slt_region = slt_region?.value || ""; // Accès via this.slt_region
    form.slt_departement = slt_departement?.value || ""; // Accès via this.slt_departement
    form.slt_arrondissement = slt_arrondissement?.value || ""; // Accès via this.slt_arrondissement
    form.slt_commune = slt_commune?.value || ""; // Accès via this.slt_commune
    form.txt_nicad = txt_nicad?.value || ""; // Accès via this.slt_commune
    form.txt_num_section = txt_num_section?.value || ""; // Accès via this.slt_commune
    form.txt_num_parcelle = txt_num_parcelle?.value || ""; // Accès via this.slt_commune
    form.txt_appartement = txt_appartement?.value || ""; // Accès via this.slt_commune
  
    // Formulaire Laravel
    form.post(route('secretariat.store'), {
        onSuccess: (page) => {
            console.log("✅ Enregistré !", page);
            toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
            Inertia.visit(route("secretariat.create"), { replace: true });
        },
        onError: (errors) => {
            console.error("❌ Erreurs de validation :", errors);
            Object.entries(errors).forEach(([key, msg]) => {
                toast.error(`${key} : ${msg}`);
        });
        },
        onFinish: () => {
            console.log("🟡 Requête terminée");
        }
    });

};


</script>

<template>
    <Head title="Secretariat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Secretariat
                
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
                                <!-- Section Parcelle -->
                                <h5 class="text-lg text-primary-txt font-bold">
                                    Réferences Dossiers
                                </h5>
                                <br />
                                <div class="mb-6">
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                    >
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label 
                                                    for="txt_num_dossier"
                                                    class="block text-sm/6 font-medium text-primary-txt">
                                                    N° Dossier
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        type="text"
                                                        name="txt_num_dossier"
                                                        v-model="form.txt_num_dossier" 
                                                        required 
                                                        id="txt_num_dossier"
                                                        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
 
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label 
                                                    for="txt_num_dordre"
                                                    class="block text-sm/6 font-medium text-primary-txt">
                                                    N° dordre
                                                </label>
                                                <div class="mt-2">
                                                    <input
                                                        type="text"
                                                        name="txt_num_dordre"
                                                        v-model.number="form.txt_num_dordre" 
                                                        min="1"
                                                        readonly
                                                        id="txt_num_dordre"
                                                        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
 
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label
                                                    for="slt_service_rendu"
                                                    class="block text-sm/6 font-medium text-primary-txt"
                                                    >Service Rendu</label
                                                >
                                                <div class="mt-2">
                                                    <select
                                                        name="slt_service_rendu"
                                                        v-model="
                                                            form.slt_service_rendu
                                                        "
                                                        id="slt_service_rendu"
                                                        autocomplete="address-level2"
                                                        class="h-7  scrollbar-thin scrollbar-thumb-primary scrollbar-track-gray-300 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        style="max-height: 200px;" 
                                                    >
                                                        <option
                                                            value=""
                                                            desabled
                                                        ></option>
                                                        <option
                                                            
                                                            value="Delimitation"
                                                        >
                                                            Delimitation
                                                        </option>
                                                        <option
                                                            value="Extrait de plan"
                                                        >
                                                            Extrait de plan
                                                        </option>
                                                        <option
                                                            value="Implatation"
                                                        >
                                                            Implatation
                                                        </option>
                                                        <option
                                                            value="Plan de situation"
                                                        >
                                                            Plan de situation
                                                        </option>
                                                        <option
                                                            value="Division"
                                                        >
                                                            Division
                                                        </option>
                                                        <option
                                                            value="Reconstitution des limites"
                                                        >
                                                            Reconstitution des
                                                            limites
                                                        </option>
                                                        <option
                                                            value="Etat des lieux"
                                                        >
                                                            Etat des lieux
                                                        </option>
                                                        <option
                                                            value="Expertise immobilière"
                                                        >
                                                            Expertise
                                                            immobilière
                                                        </option>
                                                        <option
                                                            value="Enquête cadastrale"
                                                        >
                                                            Enquête cadastrale
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label
                                                    for="txt_etat_cession"
                                                    class="block text-sm/6 font-medium text-primary-txt"
                                                    >Etat de cession</label
                                                >
                                                <div class="mt-2">
                                                    <input
                                                        type="text"
                                                        name="txt_etat_cession"
                                                        v-model="
                                                            form.txt_etat_cession
                                                        "
                                                        id="txt_etat_cession"
                                                        autocomplete="address-level2"
                                                        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label
                                                    for="txt_cession_definitive"
                                                    class="block text-sm/6 font-medium text-primary-txt"
                                                    >Cession définitive</label
                                                >
                                                <div class="mt-2">
                                                    <input
                                                        type="text"
                                                        name="txt_cession_definitive"
                                                        v-model="
                                                            form.txt_cession_definitive
                                                        "
                                                        id="txt_cession_definitive"
                                                        autocomplete="address-level2"
                                                        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <div class="sm:col-span-1">
                                                <label
                                                    for="dt_date_creation"
                                                    class="block text-sm/6 font-medium text-primary-txt"
                                                    >Date d'enregistrement</label
                                                >
                                                <div class="mt-2">
                                                    <input
                                                        type="Date"
                                                        name="dt_date_creation"
                                                        v-model="
                                                            form.dt_date_creation
                                                        "
                                                        :max="today"
                                                        required
                                                        id="dt_date_creation"
                                                        autocomplete="address-level2"
                                                        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div> 

                                    <!-- Parcelle -->
                                    <h5 class="text-lg text-primary-txt font-bold mt-8">
                                        Réferences Parcellelaires
                                    </h5>
                                    <br />
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                    >
                                        <div class="sm:col-span-1">
                                            <label
                                                for="slt_region"
                                                class="block text-sm font-medium text-primary-txt"
                                            >
                                                Région
                                            </label>
                                            <div class="mt-2">
                                                <select
                                                    id="slt_region"
                                                    name="slt_region"
                                                    v-model="slt_region"
                                                    required
                                                    @change="
                                                        fetchDepartements()
                                                    "
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option
                                                        v-for="region in regions"
                                                        :key="region.id"
                                                        :value="region.id"
                                                    >
                                                        {{ region?.slt_region }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="departements"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Département</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    id="departements"
                                                    name="slt_departement"
                                                    v-model="slt_departement"
                                                    required
                                                    @change="
                                                        fetchArrondissements()
                                                    "
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option
                                                        value=""
                                                        disabled
                                                        selected
                                                    >
                                                        Choisir ici
                                                    </option>
                                                    <option
                                                        v-for="departement in departements"
                                                        :key="departement.id"
                                                        :value="departement.id"
                                                    >
                                                        {{
                                                            departement.slt_departement
                                                        }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="arrondissements"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Arrondissement
                                            </label>
                                            <div class="mt-2">
                                                <select
                                                    id="arrondissements"
                                                    name="slt_arrondissement"
                                                    v-model="slt_arrondissement"
                                                    required
                                                    @change="fetchCommunes()"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option
                                                        value=""
                                                        disabled
                                                        selected
                                                    >
                                                        Choisir ici
                                                    </option>
                                                    <option
                                                        v-for="arrondissement in arrondissements"
                                                        :key="arrondissement.id"
                                                        :value="
                                                            arrondissement.id
                                                        "
                                                    >
                                                        {{
                                                            arrondissement.slt_arrondissement
                                                        }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="communes"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Commune</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    id="communes"
                                                    name="slt_commune  "
                                                    v-model="slt_commune"
                                                    required
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option
                                                        value=""
                                                        disabled
                                                        selected
                                                    >
                                                        Choisir ici
                                                    </option>
                                                    <option
                                                        v-for="commune in communes"
                                                        :key="commune.id"
                                                        :value="commune.id"
                                                    >
                                                        {{
                                                            commune.slt_commune
                                                        }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="txt_lotissement"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Lotissement/Quartier</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_lotissement"
                                                    v-model="
                                                        form.txt_lotissement
                                                    "
                                                    id="txt_lotissement"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="txt_HorsLotissement"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Hors Lotissement</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_HorsLotissement"
                                                    v-model="
                                                        form.txt_HorsLotissement
                                                    "
                                                    id="txt_HorsLotissement"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="txt_num_lotissement"
                                                class="block text-sm font-medium text-primary-txt"
                                                >N° Lot</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_lotissement"
                                                    v-model="
                                                        form.txt_num_lotissement
                                                    "
                                                    id="txt_num_lotissement"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                       
                                        <div class="sm:col-span-1">
                                            <label
                                                for="surface"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Surface</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="number"
                                                    step="0.01" min="0" 
                                                    name="nbr_surface"
                                                    v-model="form.nbr_surface"
                                                    id="surface"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="Document_admin"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Doc adminitratif</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_document_admin"
                                                    v-model="
                                                        form.slt_document_admin
                                                    "
                                                    id="Document_admin"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option
                                                        selected
                                                        desabled
                                                    ></option>
                                                    <option
                                                        value="Lettre D'atribution"
                                                    >
                                                        Lettre D'atribution
                                                    </option>
                                                    <option
                                                        value="Attestation Foncière"
                                                    >
                                                        Attestation Foncière
                                                    </option>
                                                    <option
                                                        value="Notification"
                                                    >
                                                        Notification
                                                    </option>
                                                    <option value="Bail">
                                                        Bail
                                                    </option>
                                                    <option
                                                        value="Acte adminitratif"
                                                    >
                                                        Acte adminitratif
                                                    </option>
                                                    <option
                                                        value="Permis d'occupation"
                                                    >
                                                        Permis d'occupation
                                                    </option>
                                                    <option
                                                        value="Déliberation"
                                                    >
                                                        Déliberation
                                                    </option>
                                                    <option value="Sans Papier">
                                                        Sans Papier
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="Num_deliberation"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >N° Déliberation</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_deliberation"
                                                    v-model="
                                                        form.txt_num_deliberation
                                                    "
                                                    id="Num_deliberation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="Date_deliberation"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Date Déliberation</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_deliberation"
                                                    v-model="
                                                        form.dt_date_deliberation
                                                    "
                                                    id="Date_deliberation"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="Num_section"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >N° Section</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_section"
                                                    v-model="
                                                        txt_num_section
                                                    "
                                                    id="Num_section"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @input="validateInputNumSection"
                                                    maxlength="3"
                                                    minlength="3"
                                                />
                                            </div>
                                            <p v-if="errorMessageNumSection" class="text-red-500 text-sm mt-1">{{ errorMessageNumSection }}</p>
                                        </div>
                                     
                                        <div class="sm:col-span-1">
                                            <label for="Num_parcelle" class="block text-sm/6 font-medium text-primary-txt">N° Parcelle</label>
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_parcelle"
                                                    v-model="txt_num_parcelle"
                                                    id="Num_parcelle"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @input="validateInputNumParcelle"
                                                    maxlength="5"
                                                    minlength="5"
                                                />
                                            </div>
                                            <p v-if="errorMessageNumParcelle" class="text-red-500 text-sm mt-1">{{ errorMessageNumParcelle }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label 
                                                for="txt_nicad" 
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >
                                                Nicad</label>
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nicad"
                                                    v-model="
                                                        txt_nicad
                                                    "
                                                    id="txt_nicad"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    readonly
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- exemple de tabs avec MazUi -->

                                <h5 class="text-lg text-primary-txt font-bold">
                                    Réferences Cadastrales
                                </h5>
                                <br />

                                <div>
                                    <!-- Exemple de tabs avec MazUi --> 
                                    <div class="sm:col-span-2">
                                        <label
                                            for="slt_dependant_domaine"
                                            class="block font-medium text-primary-txt"
                                        >
                                            Nature Juridique
                                        </label>
                                        <div class="mt-2">
                                            <MazRadio
                                                class="text-primary text-sm/6 ml-4"
                                                v-model="activeTab"
                                                value="Terrain Non Immatriculé"
                                                label="Terrain Non Immatriculé"
                                            />
                                            <MazRadio
                                                class="text-primary text-sm/6 ml-4"
                                                v-model="activeTab"
                                                value="Terrain Immatriculé"
                                                label="Terrain Immatriculé"
                                            />
                                        </div>
                                    </div>
                                    <!-- Contenus de chaque section selon les checkboxes sélectionnées -->
                                    <div v-if="activeTab.includes('Terrain Non Immatriculé')" class="maz-py-4">
                                        <!-- Contenu du Tab 1 ici -->
                                        <br/>
                                        <div class="sm:col-span-12">
                                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                <div class="sm:col-span-2">
                                                    <label
                                                        for="slt_dependant_domaine"
                                                        class="block text-sm font-medium text-primary-txt"
                                                    >
                                                        Dépendant du domaine
                                                    </label>
                                                    <div class="mt-2">
                                                        <select
                                                            v-model="form.slt_dependant_domaine"
                                                            name="slt_dependant_domaine"
                                                            id="slt_dependant_domaine"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        >
                                                            <option selected disabled>choisi ici</option>
                                                            <option value="Domaine National">Domaine National</option>
                                                            <option value="Domaine Public">Domaine Public</option>
                                                            <option value="Domaine Fluvial">Domaine Fluvial</option>
                                                            <option value="Domaine Maritime">Domaine Maritime</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <br/>
                                    <div v-if="activeTab.includes('Terrain Immatriculé')" class="maz-py-4">
                                        <!-- Contenu du Tab 2 ici -->
                                         <br>
                                        <div class="sm:col-span-12">
                                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                <div class="sm:col-span-1">
                                                    <label for="Bornage"
                                                        class="block text-sm font-medium text-primary-txt" >Ussu de bornage</label>
                                                    <div class="mt-2">
                                                        <select
                                                            v-model="form.ussu_bornage"
                                                            name="issu_bornage"
                                                            id="Bornage"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                            @change="handleSelectChange"
                                                        >
                                                            <option selected disabled>choisir ici</option>
                                                            <option value="Immatriculation">Immatriculation</option>
                                                            <option value="Morcellement">Morcellement</option>
                                                            <option value="Rectificatif de Limite">Rectificatif de Limite</option>
                                                            <option value="Fusion">Fusion</option>
                                                            <option value="Morcellement de Copropriété">Morcellement de Copropriété</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label for="Titre_mere"
                                                    class="block text-sm font-medium text-primary-txt">Titre Mere</label>
                                                    <div class="mt-2">
                                                        <input
                                                            v-model="form.txt_titre_mere"
                                                            type="text"
                                                            name="txt_titre_mere"
                                                            id="Titre_mere"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label
                                                        for="numTitre"
                                                        class="block text-sm/6 font-medium text-primary-txt"
                                                        >N° Titre</label
                                                    >
                                                    <div class="mt-2">
                                                        <input
                                                            type="text"
                                                            name="txt_num_titre"
                                                            v-model="form.txt_num_titre"
                                                            id="numTitre"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label for="LF"
                                                        class="block text-sm font-medium text-primary-txt">LF</label>
                                                    <div class="mt-2">
                                                        <select
                                                            v-model="form.slt_lf"
                                                            name="slt_lf"
                                                            id="LF"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        >
                                                            <option selected desabled></option>
                                                            <option value="NO">NO</option>
                                                            <option value="KG">KG</option>
                                                            <option value="SM">SM</option>
                                                            <option value="SR">SR</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label for="Num_requisition"
                                                        class="block text-sm font-medium text-primary-txt">N° Requisition</label>
                                                    <div class="mt-2">
                                                        <input
                                                            v-model="form.txt_num_requisition"
                                                            type="text"
                                                            name="txt_num_requisition"
                                                            id="Num_requisition"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label for="Surface_bornage"
                                                        class="block text-sm font-medium text-primary-txt">Surfacce au bornage</label>
                                                    <div class="mt-2">
                                                        <input
                                                            v-model="form.txt_surface_bornage"
                                                            type="text"
                                                            name="txt_surface_bornage"
                                                            id="Surface_bornage"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <label for="Date_bornage"
                                                        class="block text-sm font-medium text-primary-txt">Date Bornage</label>
                                                    <div class="mt-2">
                                                        <input
                                                            v-model="form.dt_date_bornage"
                                                            type="date"
                                                            name="dt_date_bornage"
                                                            id="Date_bornage"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                                <div v-if="show" class="sm:col-span-1">
                                                    <label for="txt_appartement" 
                                                        class="block text-sm/6 font-medium text-primary-txt">
                                                        Appartement
                                                    </label>
                                                    <div class="mt-2">
                                                        <input
                                                            type="text"
                                                            name="txt_appartement"
                                                            v-model="txt_appartement"
                                                            id="txt_appartement"
                                                            autocomplete="off"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                            @input="validateInput"
                                                            maxlength="3"
                                                            minlength="3"
                                                        />
                                                        <p v-if="errorMessage" class="text-red-500 text-sm mt-1 font-medium text-primary-txt">{{ errorMessage }}</p>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label for="Nom_geometre"
                                                        class="block text-sm font-medium text-primary-txt">Nom Geometre</label>
                                                    <div class="mt-2">
                                                        <input
                                                            v-model="form.txt_nom_geometre"
                                                            type="text"
                                                            name="txt_nom_geometre"
                                                            id="Nom_geometre"
                                                            autocomplete="address-level2"
                                                            class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> 

                                <!-- Section Titulaire -->
                                <h5 class="text-lg text-primary-txt font-bold mt-8">
                                    Identité Titulaire de droit
                                </h5>
                                <br />
                                <div class="mb-6">
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                    >
                                        <div class="sm:col-span-2">
                                            <label
                                                for="Titulaire"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Titulaire</label
                                            >
                                            <div class="mt-2 grid grid-cols-1">
                                                <select
                                                    name="slt_titulaire"
                                                    v-model="form.slt_titulaire"
                                                    id="Titulaire"
                                                    required
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @change="handleSelectChangeTitulaire"
                                                >
                                                    >
                                                    <option selected disabled>
                                                        Choisir ici
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
                                    </div>
                                    <br>
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                        >
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="Nationalite"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Nationalite</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nationalite"
                                                    v-model="
                                                        form.txt_nationalite
                                                    "
                                                    required
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
                                                    v-model="form.slt_civilite"
                                                    required
                                                    id="Civilite"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                >
                                                    <option selected disabled>
                                                        Choisir ici
                                                    </option>
                                                    <option value="Mr">
                                                        Mr
                                                    </option>
                                                    <option value="Mme">
                                                        Mme
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="Prenom"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Prénom</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_prenom"
                                                    v-model="form.txt_prenom"
                                                    required
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Nom</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_nom"
                                                    v-model="form.txt_nom"
                                                    required
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
                                                >Types de Piece</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_piece"
                                                    v-model="form.slt_piece"
                                                    required
                                                    id="selectePiece"
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
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="numPiece"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >N° Pièce</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_num_piece"
                                                    v-model="form.txt_numPiece"
                                                    required
                                                    id="numPiece"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div v-if="showSectionPP" class="sm:col-span-1">
                                            <label
                                                for="dateDelivrance"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Date Delivrance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_delivrance"
                                                    v-model="
                                                        form.dt_date_delivrance
                                                    "
                                                    required
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Date de Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="date"
                                                    name="dt_date_naissance"
                                                    v-model="
                                                        form.dt_date_naissance
                                                    "
                                                    :max="new Date().toISOString().split('T')[0]"
                                                    required
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Lieu de Naissance</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_lieu_naissance"
                                                    v-model="
                                                        form.txt_lieu_naissance
                                                    "
                                                    required
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Adresse</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_adresse"
                                                    v-model="form.txt_adresse"
                                                    required
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Telephone</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="tel"
                                                    name="tel_telephone"
                                                    v-model="form.tel_telephone"
                                                    required
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
                                                for="Email"
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Email</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="eml_email"
                                                    v-model="form.eml_email"
                                                    id="Email"
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Representant</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_representant"
                                                    v-model="
                                                        form.txt_representant
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
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Telephone Representant</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="tel_telRepresentant"
                                                    v-model="
                                                        form.tel_telRepresentant
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
                                                    v-model="form.slt_categoriePM"
                                                    required
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
                                                        form.txt_formJuridiquePM 
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
                                                        form.txt_denominationPM
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
                                                        form.txt_nineaPM
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
                                                        form.txt_perssonneRepresentantPM
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
                                                        form.txt_fonctionPM
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
                                                        form.txt_telephonePM
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
                                                >Fonction Responsable</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="email"
                                                    name="txt_emailPM"
                                                    v-model="
                                                        form.txt_emailPM
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
                                                        form.txt_adressePM
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
                                                    v-model="form.slt_etablissementPA"
                                                    required
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
                                                        form.txt_personneResponsablePA
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
                                                        form.txt_fonctionResponsablePA
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
                                                        form.txt_telephonePA
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
                                                        form.txt_emailPA
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
                                                >Ministère Tutele</label
                                            >
                                            <div class="mt-2">
                                                <input
                                                    type="text"
                                                    name="txt_ministèreTutelePA"
                                                    v-model="
                                                        form.txt_ministèreTutelePA
                                                    "
                                                    id="txt_ministèreTutelePA"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                          
                                        <div  class="sm:col-span-2"> 
                                            <label 
                                                for="fichierPDF" 
                                                class="block text-sm/6 font-medium text-primary-txt"
                                                >Importer le fichier</label
                                            >
                                            <div class="mt-2">
                                                <input 
                                                type="file"  
                                                name="fichierPDF"
                                                accept="application/pdf"
                                                @change="handleFileUpload" 
                                                id="fichierPDF"
                                                autocomplete="off"  
                                                class="h-7 block w-full rounded-md bg-white 
                                                    px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                    outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                    focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                            </div> 
                                        </div>
                                         
                                    </div>
                                </div>
                                <br />

                                <!-- Bouton de soumission -->

                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn type="submit" no-shadow no-hover-effect
                                            class="bg-gradient-to-r from-primary via-primary-light to-primary-dark 
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-primary 
                                                dark:focus:ring-primary-dark shadow-lg shadow-primary/50 
                                                dark:shadow-lg dark:shadow-primary-dark font-medium rounded-lg text-sm 
                                                px-5 py-2.5 text-center">
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

