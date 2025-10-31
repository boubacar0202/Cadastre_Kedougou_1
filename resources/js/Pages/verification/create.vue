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

const txt_nicad = computed(() => {
 
    const codes = {
        1: "13110100",
        2: "13120101",
        3: "13120102",
        4: "14120103",
        5: "13120104",
        6: "13120201",
        7: "13120202",
        8: "13210100",
        9: "13220101",
        10: "13220102",
        11: "13220201",
        12: "13220202",
        13: "13220103",
        14: "13310100",
        15: "13320101",
        16: "13320102",
        17: "13320201",
        18: "13310202",
        19: "13220203"
    }

    const prefix = codes[slt_commune.value] || ''
    const value = `${prefix}${txt_num_section.value}${txt_num_parcelle.value}${txt_appartement.value}`.trim()
 
    // 🔎 Vérification longueur totale
    if (value.replace(/\s/g, "").length <= 8) {
        return null;
    }

    return value;
});

const props = defineProps({
    regions: {
        type: Array,
        default: () => [],
    },
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
 
     
// Submit du formulaire
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
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-2xl "
                                    > 
                                        <div class="sm:col-span-4">
                                            <input
                                                type="text"
                                                name="txt_numdossier"
                                                v-model="form.txt_numdossier"
                                                id="txt_numdossier"
                                                required
                                                class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    placeholder="Entrer le numéro dossier"
                                                
                                            > 
                                        </div>  
                                        <MazBtn 
                                            :loading="isLoading"
                                            type="button"
                                            title="Confirmer"
                                            @click="rechercherDossier"
                                            class="w-36 h-auto text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                                hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                                dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                                dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
                                            size="medium"
                                        >
                                             
                                            Charger
                                        </MazBtn>
                                    </div><br>
                                </div>
                                <h5 class="text-lg text-primary-txt font-bold mt-8">
                                    Références  Propriétaire
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
                                                    class="h- block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                   
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
                                    
                                        <div class="sm:col-span-1">
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
                                                    id="Nationalite"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div  class="sm:col-span-1">
                                            <label
                                                for="Civilite"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Civilité</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_civilite"
                                                    v-model="form.slt_civilite" 
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
                                        <div class="sm:col-span-1">
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
                                                    id="Prenom"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    id="nom"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <label
                                                for="selectePiece"
                                                class="block text-sm font-medium text-primary-txt"
                                                >Types de Piece</label
                                            >
                                            <div class="mt-2">
                                                <select
                                                    name="slt_piece"
                                                    v-model="form.slt_piece" 
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
                                        <div class="sm:col-span-1">
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
                                                    id="numPiece"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    :max="new Date().toISOString().split('T')[0]"
                                                    id="dateDelivrance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    id="dateNaissance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    id="lieuNaissance"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    id="adresse"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1">
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
                                                    id="telephone"
                                                    autocomplete="address-level2"
                                                    class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                />
                                            </div>
                                        </div> 
                                        <div class="sm:col-span-1">
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
                                        <div class="sm:col-span-1">
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
                                        <div class="sm:col-span-1">
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
                                    </div>
                                    
                                    <!-- Section acquereurre -->
                                    <h5 class="text-lg text-primary-txt font-bold mt-8">
                                        Références du nouvelle acquéreur
                                    </h5>
                                    <br />
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
   
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

