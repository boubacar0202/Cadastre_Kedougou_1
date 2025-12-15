<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import BaseInput from "@/Components/BaseInput.vue"
import BaseInput_2 from "@/Components/BaseInput.vue" 
import BaseSelect from "@/Components/BaseSelect.vue"
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { onMounted, ref, watch, watchEffect, computed  } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
 

defineOptions({ layout: DefaultLayout });
 
const slt_region = ref(); 
const slt_departement = ref();
const slt_arrondissement = ref();
const slt_commune = ref(); 
const departements = ref(null);
const arrondissements = ref(null);
const communes = ref(null); 
const txt_num_section = ref('');
const txt_num_parcelle = ref('');
const txt_appartement = ref(''); 
const fichierPDF = ref(null); 
const activeTab = ref("");   
const show = ref(false);
const showSectionPP = ref(false); // Perssone Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // Etat
  
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
 
const props = defineProps({
    regions: {
        type: Array,
        default: () => [],
    }, 
    communes: Array 
});
 
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


const selectedCommune = computed(() => {
    return props.communes.find(c => c.id == form.slt_commune);
});
const txt_nicad = computed(() => {
    const prefix = selectedCommune.value?.txt_codeCommune || "";

    const section = form.txt_num_section || "";
    const parcelle = form.txt_num_parcelle || "";
    const appart = form.txt_appartement || "";

    const value = `${prefix}${section}${parcelle}${appart}`.trim();

    const MIN_LENGTH = 9;
    return value.replace(/\s/g, "").length >= MIN_LENGTH ? value : "";
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

watch(() => form.slt_region, fetchDepartements);
watch(() => form.slt_departement, fetchArrondissements);
watch(() => form.slt_arrondissement, fetchCommunes);
watchEffect(() => {
    form.slt_region = slt_region.value;
    form.slt_departement = slt_departement.value;
    form.slt_arrondissement = slt_arrondissement.value;
    form.slt_commune = slt_commune.value;
});

// morcellement
const handleSelectChange = () => {
    show.value = form.ussu_bornage === "Morcellement de Copropriété";
};
watch(() => form.ussu_bornage, (newValue) => {
    show.value = newValue === "Morcellement de Copropriété";
});

// charge titulaire Etat Perssone Morale Personne Physique 
const handleSelectChangeTitulaire = () => {
    showSectionPP.value = form.slt_titulaire === "Personne Physique";
    showSectionPM.value = form.slt_titulaire === "Personne Morale";
    showSectionPA.value = form.slt_titulaire === "Etat";
};
watch(() => form.slt_titulaire, handleSelectChangeTitulaire);
 
const errorMessage = ref(""); 
const validateInput = () => {
    const value = (form.txt_appartement || "").toString();

    if (!/^\d+$/.test(value)) {
        errorMessage.value = "❌ Seulement des chiffres.";
    } else if (value.length !== 3) {
        errorMessage.value = "❌ 3 chiffres minimum.";
    } else { 
        errorMessage.value = "";
    }
};
 
const errorMessageNumSection = ref(""); 
const validateInputNumSection = (e) => {
    const value = e.target.value;
    if (!/^\d*$/.test(value)) {
        errorMessageNumSection.value = "❌ Seulement des chiffres.";
    } else if (value.length !== 3) {
        errorMessageNumSection.value = "❌ Exactement 3 chiffres.";
    } else {
        errorMessageNumSection.value = "";
    }
};
 
const errorMessageNumParcelle = ref(""); 
const validateInputNumParcelle = () => {
    const value = (form.txt_num_parcelle || "").toString();

    if (!/^\d+$/.test(value)) {
        errorMessageNumParcelle.value = "❌ Seulement des chiffres.";
    } else if (value.length !== 5) {
        errorMessageNumParcelle.value = "❌ Exactement 5 chiffres.";
    } else {
        errorMessageNumParcelle.value = "";
    }
};
 
// Submit du formulaire
const submitForm = function () {  
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
            toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
            Inertia.visit(route("secretariat.create"), { replace: true });
        },
        onError: (errors) => { 
            Object.values(errors).forEach(msg => toast.error(msg)); 
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
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <!-- En-tête du formulaire -->
                        <div   class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Formulaire</h1>
                        </div>
                        <!-- Corps du formulaire -->
                        <form autocomplete="on" @submit.prevent="submitForm">
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
                                    
                                        <BaseInput
                                            label="N° Dossier"
                                            name="txt_num_dossier"
                                            required="on"
                                            v-model="form.txt_num_dossier"
                                        />

                                        <BaseInput
                                            label="N° dordre"
                                            name="txt_num_dordre"
                                            required="on"
                                            v-model="form.txt_num_dordre"
                                        />
                                         
                                        <BaseSelect
                                            label="Service Rendu"
                                            name="slt_service_rendu"
                                            required="on"
                                            :options="[
                                                {value:'Delimitation', label:'Delimitation'},
                                                {value:'Extrait de plan', label:'Extrait de plan'},
                                                {value:'Implantation', label:'Implantation'},
                                                {value:'Plan de situation', label:'Plan de situation'},
                                                {value:'Division', label:'Division'},
                                                {value:'Reconstitution des limites', label:'Reconstitution des limites'},
                                                {value:'Etat des lieux', label:'Etat des lieux'},
                                                {value:'Expertise immobilière', label:'Expertise immobilière'},
                                                {value:'Enquête cadastrale', label:'Enquête cadastrale'},
                                            ]"
                                            v-model="form.slt_service_rendu"
                                        />
 
                                        <BaseInput
                                            label="Etat de cession"
                                            name="txt_etat_cession"
                                            autocomplete="on"
                                            required="on"
                                            v-model="form.txt_etat_cession"
                                        />
                                     
                                        <BaseInput
                                            label="Cession definitive"
                                            name="txt_cession_definitive"
                                            autocomplete="given-name"
                                            v-model="form.txt_cession_definitive"
                                        />
                                                                         
                                        <BaseInput
                                            label="Date d'enregistrement"
                                            name="dt_date_creation"
                                            type="date"
                                            required="on"
                                            v-model="form.dt_date_creation"
                                        />

                                    </div> 

                                    <!-- Parcelle -->
                                    <h5 class="text-lg text-primary-txt font-bold mt-8">
                                        Réferences Parcellelaires
                                    </h5>
                                    <br />
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                    >
                                       
                                        <BaseSelect
                                            label="Région"
                                            name="slt_region"
                                            :options="regions" 
                                            optionLabel="slt_region"
                                            optionValue="id"
                                            required="on"
                                            v-model="slt_region"
                                            @change="fetchDepartements"
                                        />
 

                                        <BaseSelect
                                            label="Département"
                                            name="slt_departement"
                                            :options="departements"
                                            optionLabel="slt_departement"
                                            optionValue="id"
                                            required="on"
                                            v-model="slt_departement"
                                            @change="fetchArrondissements"
                                        />
  
                                        <BaseSelect
                                            label="Arrondissement"
                                            name="slt_arrondissement"
                                            :options="arrondissements"
                                            optionLabel="slt_arrondissement"
                                            optionValue="id"
                                            required="on"
                                            v-model="slt_arrondissement"
                                            @change="fetchCommunes"
                                        /> 
              
                                        <BaseSelect
                                            label="Commune"
                                            name="slt_commune"
                                            :options="communes"
                                            optionLabel="slt_commune"
                                            optionValue="id"
                                            required="on"
                                            v-model="slt_commune" 
                                        />

  

 
                                        <BaseInput
                                            label="Lotissement/Quartier"
                                            name="txt_lotissement"
                                            autocomplete="address-level2" 
                                            v-model="form.txt_lotissement"
                                        />
 
                                        
                                        <BaseInput
                                            label="Hors Lotissement"
                                            name="txt_HorsLotissement" 
                                            autocomplete="address-level2"
                                            v-model="form.txt_HorsLotissement"
                                        />
                                      
                                        <BaseInput
                                            label="N° Lot"
                                            name="txt_num_lotissement"
                                            v-model="form.txt_num_lotissement"
                                        />
   
                                        <BaseInput
                                            label="Surface"
                                            name="nbr_surface"
                                            min="0.0"
                                            step="0.01"
                                            autocomplete="given-name"
                                            v-model="form.nbr_surface"
                                        /> 
                                        
                                        <BaseSelect
                                            label="Doc.Adminitratif"
                                            name="slt_document_admin"
                                            :options="[
                                                {value:'Lettre d\'atribution', label:'Lettre d\'atribution'},
                                                {value:'Extrait de plan', label:'Extrait de plan'},
                                                {value:'Notification', label:'Notification'},
                                                {value:'Bail', label:'Bail'},
                                                {value:'Acte adminitratif', label:'Acte adminitratif'},
                                                {value:'Permis d\'occupation', label:'Permis d\'occupation'},
                                                {value:'Déliberation', label:'Déliberation'},
                                                {value:'Atestation Foncière', label:'Atestation Foncière'},
                                                {value:'Acte de vente', label:'Acte de vente'},
                                                {value:'Sans Papier', label:'Sans Papier'}, 
                                            ]"
                                            v-model="form.slt_document_admin"
                                        /> 
                                                                           
                                        <BaseInput
                                            label="N° Déliberation"
                                            name="txt_num_deliberation"  
                                            v-model="form.txt_num_deliberation"
                                        /> 
                                                                                                                   
                                        <BaseInput
                                            label="Date Déliberation"
                                            name="dt_date_deliberation" 
                                            type="date" 
                                            v-model="form.dt_date_deliberation"
                                        />  
                                        
                                        <BaseInput
                                            label="N° Section"
                                            name="txt_num_section"  
                                            :maxlength="3"
                                            :minlength="3"
                                            v-model="form.txt_num_section" 
                                            :error="errorMessageNumSection" 
                                            @input="() => validateInputNumSection()"

                                        /> 

                                        <BaseInput
                                            label="N° Parcelle"
                                            name="txt_num_parcelle"  
                                            :maxlength="5"
                                            :minlength="5"
                                            v-model="form.txt_num_parcelle"
                                            @input="validateInputNumParcelle"
                                            :error="errorMessageNumParcelle" 
                                        />  
  
                                        <BaseInput
                                            class="sm:col-span-2"
                                            label="NICAD"
                                            name="txt_nicad"   
                                            :modelValue="txt_nicad"
                                            readonly 
                                        /> 
                                        

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
                                       
                                                <BaseSelect
                                                    class="sm:col-span-2"
                                                    label="Dépendance Domaine"
                                                    name="slt_dependant_domaine" 
                                                    :options="[
                                                        {value:'Domaine National', label:'Domaine National'},
                                                        {value:'Domaine Public', label:'Domaine Public'},
                                                        {value:'Domaine Fluvial', label:'Domaine Fluvial'},
                                                        {value:'Domaine Maritime', label:'Domaine Maritime'}, 
                                                    ]" 
                                                    v-model="form.slt_dependant_domaine" 
                                                /> 

                                            </div>
                                        </div>
                                    </div>

                                    <br/>
                                    <div v-if="activeTab.includes('Terrain Immatriculé')" class="maz-py-4">
                                        <!-- Contenu du Tab 2 ici -->
                                         <br>
                                        <div class="sm:col-span-12">
                                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                 
                                                <BaseSelect
                                                    class="sm:col-span-2"
                                                    label="Ussu de bornage"
                                                    name="ussu_bornage" 
                                                    :options="[
                                                        {value:'Immatriculation', label:'Immatriculation'},
                                                        {value:'Morcellement', label:'Morcellement'},
                                                        {value:'Rectificatif de Limite', label:'Rectificatif de Limite'},
                                                        {value:'Morcellement de Copropriété', label:'Morcellement de Copropriété'}, 
                                                    ]" 
                                                    v-model="form.ussu_bornage" 
                                                    @change="handleSelectChange"
                                                />  

                                                <BaseInput
                                                    label="Titre Mere"
                                                    name="txt_titre_mere"   
                                                    v-model="form.txt_titre_mere" 
                                                />   

                                                <BaseInput
                                                    label="N° Titre"
                                                    name="txt_num_titre"
                                                    v-model="form.txt_num_titre" 
                                                /> 

                                                <BaseSelect 
                                                    label="LF"
                                                    name="slt_lf" 
                                                    :options="[
                                                        {value:'NO', label:'NO'},
                                                        {value:'KG', label:'KG'},
                                                        {value:'SM', label:'SM'},
                                                        {value:'SR', label:'SR'}, 
                                                    ]" 
                                                    v-model="form.slt_lf"  
                                                />    

                                                <BaseInput 
                                                    label="N° Requisition"
                                                    name="txt_num_requisition"   
                                                    v-model="form.txt_num_requisition" 
                                                />  

                                                <BaseInput 
                                                    label="Surface au bornage"
                                                    name="txt_surface_bornage"   
                                                    v-model="form.txt_surface_bornage" 
                                                /> 
  
                                                <BaseInput 
                                                    label="Date Bornage"
                                                    name="dt_date_bornage"   
                                                    type="date"
                                                    v-model="form.dt_date_bornage" 
                                                /> 
 
                                                <BaseInput 
                                                    v-if="show" 
                                                    label="Appartement"
                                                    name="txt_appartement" 
                                                    @input="validateInput"
                                                    :maxlength="3"
                                                    :minlength="3"
                                                    v-model="form.txt_appartement"
                                                    :error="errorMessageNumSection"  
                                                /> 
 
                                                <BaseInput 
                                                    class="sm:col-span-2"
                                                    label="ApparNom Géomètre"
                                                    name="txt_nom_geometre" 
                                                    v-model="form.txt_nom_geometre" 
                                                /> 


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
 
                                        <BaseSelect
                                            class="sm:col-span-2"
                                            label="Titulaire"
                                            name="slt_titulaire" 
                                            v-model="form.slt_titulaire" 
                                            @change="handleSelectChangeTitulaire"

                                            :options="[
                                                {value: 'Personne Physique', label: 'Perssonne Physique'},
                                                {value: 'Personne Morale', label: 'Personne Morale'},
                                                {value: 'Etat', label: 'Etat'},
                                            ]"
                                        /> 
                                        
                                    </div>
                                    <br>
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"
                                    >
 
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            label="Nationalite"
                                            name="txt_nationalite" 
                                            autocomplete="given-name"
                                            v-model="form.txt_nationalite" 
                                        /> 
 
                                        <BaseSelect
                                            v-if="showSectionPP" 
                                            label="Civilité"
                                            name="slt_civilite"  
                                            v-model="form.slt_civilite" 
                                            :options="[
                                                {value: 'Mr', label: 'Mr'},
                                                {value: 'Mme', label: 'Mme'},
                                            ]"
                                        /> 
 
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            label="Prénom"
                                            name="txt_prenom"  
                                            v-model="form.txt_prenom" 
                                        />
 
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            label="Nom"
                                            name="txt_nom"  
                                            v-model="form.txt_nom" 
                                        />
 
                                        <BaseSelect
                                            v-if="showSectionPP" 
                                            label="Types de Piece"
                                            name="slt_piece"  
                                            v-model="form.slt_piece" 
                                            :options="[
                                                {value: 'CNI', label: 'CNI'},
                                                {value: 'Passepotr', label: 'Passeport'}
                                            ]"
                                        />
 
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            label="N° Pièce"
                                            name="txt_numPiece"  
                                            v-model="form.txt_numPiece" 
                                        />
                                    
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            type="date"
                                            label="Date Délivrance"
                                            name="dt_date_delivrance"  
                                            v-model="form.dt_date_delivrance" 
                                            :max="new Date().toISOString().split('T')[0]" 
                                        />
 
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            type="date"
                                            label="Date de Naissance"
                                            name="dt_date_naissance"  
                                            v-model="form.dt_date_naissance" 
                                            :max="new Date().toISOString().split('T')[0]" 
                                        />
 
                                        <BaseInput 
                                            v-if="showSectionPP"  
                                            label="Lieu de Naissance"
                                            name="txt_lieu_naissance" 
                                            autocomplete="given-name" 
                                            v-model="form.txt_lieu_naissance"  
                                        />
 
                                        <BaseInput 
                                            v-if="showSectionPP"  
                                            label="Adresse"
                                            name="txt_adresse"  
                                            v-model="form.txt_adresse" 
                                            autocomplete="given-name"
                                        />
                                     
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            type="tel" 
                                            label="Téléphone"
                                            name="tel_telephone"  
                                            maxlength="15"
                                            v-model="form.tel_telephone"  
                                            @input="validatePhone"
                                        />
                                                                              
                                        <BaseInput 
                                            v-if="showSectionPP" 
                                            type="email" 
                                            label="Email"
                                            name="eml_email"   
                                            v-model="form.eml_email"   
                                        />
                                                                                                                       
                                        <BaseInput 
                                            v-if="showSectionPP"  
                                            label="Representant"
                                            name="txt_representant"   
                                            v-model="form.txt_representant"   
                                        />
                                                                                                                                                              
                                        <BaseInput 
                                            v-if="showSectionPP"  
                                            label="Tél.Representant"
                                            name="tel_telRepresentant"   
                                            v-model="form.tel_telRepresentant"   
                                        />

                                        <!-- Perssonne Morale -->
                                                                                                                                                                                                
                                        <BaseSelect
                                            v-if="showSectionPM"  
                                            label="Catégorie"
                                            name="slt_categoriePM"   
                                            v-model="form.slt_categoriePM"  
                                            :options="[
                                                {value: 'Station', label: 'Station'},
                                                {value: 'Industie', label: 'Industrie'},
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
                                            name="txt_formJuridiquePM"  
                                            v-model="form.txt_formJuridiquePM"
                                            autocomplete="given-name"  
                                        /> 
                                                                                                                                                                                                                                                                                        
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            label="Dénomination"
                                            name="txt_denominationPM"  
                                            v-model="form.txt_denominationPM"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            label="Ninea"
                                            name="txt_nineaPM"  
                                            v-model="form.txt_nineaPM"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                        
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            label="Nom.Personne Responsable"
                                            name="txt_perssonneRepresentantPM"  
                                            v-model="form.txt_perssonneRepresentantPM"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                                                                
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            label="Fonction Responsable"
                                            name="txt_fonctionPM"  
                                            v-model="form.txt_fonctionPM"  
                                            autocomplete="given-name"
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            label="Tél.Responsable"
                                            name="txt_telephonePM"  
                                            v-model="form.txt_telephonePM"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
                                        <BaseInput 
                                            v-if="showSectionPM"  
                                            type="email"
                                            label="Email"
                                            name="txt_emailPM"  
                                            v-model="form.txt_emailPM"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                        <BaseInput 
                                            v-if="showSectionPM"
                                            label="Adresse Sciège"
                                            name="txt_adressePM"  
                                            v-model="form.txt_adressePM"  
                                            autocomplete="given-name"
                                        />

                                        <!-- Etat -->
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
                                        <BaseSelect
                                            v-if="showSectionPA"
                                            label="Etablissement"
                                            name="slt_etablissementPA"  
                                            v-model="form.slt_etablissementPA"
                                            :options="[
                                                {value: 'Sanitaite', label: 'Sanitaire'},
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
                                            name="txt_personneResponsablePA"  
                                            v-model="form.txt_personneResponsablePA"  
                                        /> 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        
                                        <BaseInput 
                                            v-if="showSectionPA"
                                            label="Fonction Responsable"
                                            name="txt_fonctionResponsablePA"  
                                            v-model="form.txt_fonctionResponsablePA"
                                            autocomplete="given-name"  
                                        /> 

                                        <BaseInput 
                                            v-if="showSectionPA"
                                            label="Téléphone"
                                            type="tel"
                                            name="txt_telephonePA"
                                            v-model="form.txt_telephonePA"  
                                        /> 
                                        
                                        <BaseInput 
                                            v-if="showSectionPA"
                                            label="Email"
                                            type="email"
                                            name="txt_emailPA"
                                            v-model="form.txt_emailPA"  
                                        /> 
                                                                                
                                        <BaseInput 
                                            v-if="showSectionPA"
                                            label="Ministère Tutelle" 
                                            name="txt_ministèreTutelePA"
                                            v-model="form.txt_ministèreTutelePA" 
                                            autocomplete="given-name" 
                                        /> 
                                        
                                        <BaseInput 
                                            type="file"
                                            accept="application/pdf"
                                            class="sm:col-span-2"
                                            label="Importer le Fichier PDF" 
                                            name="fichierPDF" 
                                            @change="handleFileUpload" 
                                        /> 
 
                                    </div>
                                </div>
                                <br />

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

