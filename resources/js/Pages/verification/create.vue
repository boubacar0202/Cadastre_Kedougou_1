<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
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
const TITULAIRE_OPTIONS = [
    { label: "Personne Physique", value: "Personne Physique" },
    { label: "Personne Morale", value: "Personne Morale" },
    { label: "État", value: "Etat" },
];
const CIVILITE_OPTIONS = [
    { label: "Mr", value: "Mr" },
    { label: "Mme", value: "Mme" },
];
const PIECE_OPTIONS = [
    { label: "CNI", value: "CNI" },
    { label: "Passeport", value: "Passeport" },
];
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
                                        <BaseInput
                                            type="text"
                                            name="txt_numdossier"
                                            v-model="form.txt_numdossier"
                                            id="txt_numdossier"
                                            required
                                            placeholder="Entrer le numéro dossier"
                                            wrapper-class="sm:col-span-4"
                                        />  
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
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                        <BaseSelect
                                            label="Titulaire"
                                            name="slt_titulaire"
                                            id="Titulaire"
                                            v-model="form.slt_titulaire"
                                            :options="TITULAIRE_OPTIONS"
                                            placeholder="Choisir ici"
                                            required
                                            wrapper-class="sm:col-span-2"
                                        />
                                        <BaseInput label="Nationalite" name="txt_nationalite" id="Nationalite" v-model="form.txt_nationalite" autocomplete="address-level2" />
                                        <BaseSelect label="Civilité" name="slt_civilite" id="Civilite" v-model="form.slt_civilite" :options="CIVILITE_OPTIONS" placeholder="Choisir ici" />
                                        <BaseInput label="Prénom" name="txt_prenom" id="Prenom" v-model="form.txt_prenom" autocomplete="address-level2" />
                                        <BaseInput label="Nom" name="txt_nom" id="nom" v-model="form.txt_nom" autocomplete="address-level2" />
                                        <BaseSelect label="Types de Piece" name="slt_piece" id="selectePiece" v-model="form.slt_piece" :options="PIECE_OPTIONS" placeholder="Choisir ici" />
                                        <BaseInput label="N° Pièce" name="txt_num_piece" id="numPiece" v-model="form.txt_numPiece" autocomplete="address-level2" />
                                        <BaseInput label="Date Delivrance" name="dt_date_delivrance" id="dateDelivrance" type="date" v-model="form.dt_date_delivrance" :max="today" autocomplete="address-level2" />
                                        <BaseInput label="Date de Naissance" name="dt_date_naissance" id="dateNaissance" type="date" v-model="form.dt_date_naissance" :max="today" autocomplete="address-level2" />
                                        <BaseInput label="Lieu de Naissance" name="txt_lieu_naissance" id="lieuNaissance" v-model="form.txt_lieu_naissance" autocomplete="address-level2" />
                                        <BaseInput label="Adresse" name="txt_adresse" id="adresse" v-model="form.txt_adresse" autocomplete="address-level2" />
                                        <BaseInput label="Telephone" name="tel_telephone" id="telephone" type="tel" v-model="form.tel_telephone" autocomplete="address-level2" />
                                        <BaseInput label="Email" name="eml_email" id="Email" type="email" v-model="form.eml_email" autocomplete="address-level2" />
                                        <BaseInput label="Representant" name="txt_representant" id="txt_representant" v-model="form.txt_representant" autocomplete="address-level2" />
                                        <BaseInput label="Telephone Representant" name="tel_telRepresentant" id="tel_telRepresentant" v-model="form.tel_telRepresentant" autocomplete="address-level2" />
                                    </div>

                                    <h5 class="text-lg text-primary-txt font-bold mt-8">
                                        Références du nouvelle acquéreur
                                    </h5>
                                    <br />
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                        <BaseInput
                                            label="Importer le fichier"
                                            type="file"
                                            name="fichierPDF"
                                            accept="application/pdf"
                                            id="fichierPDF"
                                            autocomplete="off"
                                            wrapper-class="sm:col-span-2"
                                            @change="handleFileUpload"
                                        />
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

