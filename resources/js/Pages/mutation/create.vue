<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, ref, watch } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";
import { route } from "ziggy-js";

// ============ ÉTATS ============
const formVisible = ref(false);
const referencesLot = ref([]);
const fichierPDF = ref(null);
const show = ref(false);
const searching = ref(false);

// Sections Propriétaire (titulaire)
const showSectionPP = ref(false);
const showSectionPM = ref(false);
const showSectionPA = ref(false);

// Sections Acquéreur
const showSectionPP_Acq = ref(false);
const showSectionPM_Acq = ref(false);
const showSectionPA_Acq = ref(false);

// ============ LISTES D'OPTIONS ============
const TITULAIRE_OPTIONS = [
  { value: "Personne Physique", label: "Personne Physique" },
  { value: "Personne Morale", label: "Personne Morale" },
  { value: "Etat", label: "Etat" },
];

const CIVILITE_OPTIONS = [
  { value: "Mr", label: "Mr" },
  { value: "Mme", label: "Mme" },
];

const PIECE_OPTIONS = [
  { value: "CNI", label: "CNI" },
  { value: "Passeport", label: "Passeport" },
];

const CATEGORIE_PM_OPTIONS = [
  { value: "Station", label: "Station" },
  { value: "Industrie", label: "Industrie" },
  { value: "Restaurant", label: "Restaurant" },
  { value: "Hotel", label: "Hotel" },
  { value: "Hébergement", label: "Hébergement" },
  { value: "Elevage", label: "Elevage" },
  { value: "Agrobusiness", label: "Agrobusiness" },
  { value: "Epicerie", label: "Epicerie" },
  { value: "Alimentation Générale", label: "Alimentation Générale" },
  { value: "Quincaillerie", label: "Quincaillerie" },
  { value: "Transport", label: "Transport" },
  { value: "Coopérative Habitat", label: "Coopérative Habitat" },
  { value: "BTP", label: "BTP" },
  { value: "Organisation Religieuse", label: "Organisation Religieuse" },
];

const ETABLISSEMENT_OPTIONS = [
  { value: "Sanitaire", label: "Sanitaire" },
  { value: "Scolaire", label: "Scolaire" },
  { value: "Militaire", label: "Militaire" },
  { value: "Administratif", label: "Administratif" },
  { value: "Aéroportuaire", label: "Aéroportuaire" },
  { value: "Sportif", label: "Sportif" },
  { value: "Universitaire", label: "Universitaire" },
  { value: "Autres", label: "Autres" },
];

const ACTE_OPTIONS = [
  { value: "Acte Notarie", label: "Acte Notarié" },
  { value: "Acte Sous Seing Privé", label: "Acte sous Seing Privé" },
  { value: "Jugement", label: "Jugement" },
];

const NATURE_OPTIONS = [
  { value: "Vente", label: "Vente" },
  { value: "Echange", label: "Echange" },
  { value: "Donnation", label: "Donnation" },
  { value: "Partage-Heritage", label: "Partage-Heritage" },
  { value: "Autres", label: "Autres" },
];

// ============ FORMULAIRE ============
const form = useForm({
  txt_num_lotissementMutation: "",
  txt_lotissementMutation: "",
  txt_num_dossier: "",

  // Propriétaire (titulaire)
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

  // Personne morale (titulaire)
  slt_categoriePM_MT: "",
  txt_formJuridiquePM_MT: "",
  txt_denominationPM_MT: "",
  txt_nineaPM_MT: "",
  txt_personneRepresentantPM_MT: "",
  txt_fonctionPM_MT: "",
  txt_telephonePM_MT: "",
  txt_emailPM_MT: "",
  txt_adressePM_MT: "",

  // État (titulaire)
  slt_etablissementPA_MT: "",
  txt_personneResponsablePA_MT: "",
  txt_fonctionResponsablePA_MT: "",
  txt_telephonePA_MT: "",
  txt_emailPA_MT: "",
  txt_ministereTutelePA_MT: "",

  // Acquéreur
  slt_titulaireMutation: "",
  txt_prenomMutation: "",
  txt_nomMutation: "",
  txt_nationaliteMutation: "",
  slt_civiliteMutation: "",
  slt_pieceMutation: "",
  txt_numPieceMutation: "",
  dt_date_delivranceMutation: "",
  dt_date_naissanceMutation: "",
  txt_lieu_naissanceMutation: "",
  txt_adresseMutation: "",
  tel_telephoneMutation: "",
  txt_representantMutation: "",
  tel_telRepresentantMutation: "",
  txt_typeActeMutation: "",
  txt_nomNotaireMutation: "",
  dt_dateMutation: "",
  txt_natureMutation: "",
  txt_jugementMutation: "",
  txt_observationMutation: "",

  // Personne morale (acquéreur)
  slt_categoriePM_Mutation: "",
  txt_formJuridiquePM_Mutation: "",
  txt_denominationPM_Mutation: "",
  txt_nineaPM_Mutation: "",
  txt_personneRepresentantPM_Mutation: "",
  txt_fonctionPM_Mutation: "",
  txt_telephonePM_Mutation: "",
  txt_emailPM_Mutation: "",
  txt_adressePM_Mutation: "",

  // État (acquéreur)
  slt_etablissementPA_Mutation: "",
  txt_personneResponsablePA_Mutation: "",
  txt_fonctionResponsablePA_Mutation: "",
  txt_telephonePA_Mutation: "",
  txt_emailPA_Mutation: "",
  txt_ministereTutelePA_Mutation: "",

  // Fichier PDF
  fichierPDFMutation: null,
});

// ============ FICHIER PDF ============
function handleFileUpload(event) {
  const file = event.target.files?.[0];
  if (!file) {
    fichierPDF.value = null;
    form.fichierPDFMutation = null;
    return;
  }

  if (file.type !== "application/pdf") {
    toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
    event.target.value = "";
    fichierPDF.value = null;
    form.fichierPDFMutation = null;
    return;
  }

  if (file.size > 100 * 1024 * 1024) {
    toast.error("❌ Le fichier dépasse 100 Mo !");
    event.target.value = "";
    fichierPDF.value = null;
    form.fichierPDFMutation = null;
    return;
  }

  fichierPDF.value = file;
  form.fichierPDFMutation = file;
}

// ============ GESTION DES SECTIONS ============
function resetTitulaireSections() {
  showSectionPP.value = false;
  showSectionPM.value = false;
  showSectionPA.value = false;
}

function resetAcquereurSections() {
  showSectionPP_Acq.value = false;
  showSectionPM_Acq.value = false;
  showSectionPA_Acq.value = false;
}

const handleSelectChangeTitulaire = () => {
  resetTitulaireSections();
  switch (form.slt_titulaireMT) {
    case "Personne Physique": showSectionPP.value = true; break;
    case "Personne Morale": showSectionPM.value = true; break;
    case "Etat": showSectionPA.value = true; break;
  }
};

watch(() => form.slt_titulaireMT, (newVal) => {
  handleSelectChangeTitulaire();
  if (newVal && !["Personne Physique", "Personne Morale", "Etat"].includes(newVal)) {
    toast.error("Veuillez choisir un titulaire valide.");
  }
});

const handleSelectChangeTitulaireAcquereur = () => {
  resetAcquereurSections();
  switch (form.slt_titulaireMutation) {
    case "Personne Physique": showSectionPP_Acq.value = true; break;
    case "Personne Morale": showSectionPM_Acq.value = true; break;
    case "Etat": showSectionPA_Acq.value = true; break;
  }
};

watch(() => form.slt_titulaireMutation, (newVal) => {
  handleSelectChangeTitulaireAcquereur();
  if (newVal && !["Personne Physique", "Personne Morale", "Etat"].includes(newVal)) {
    toast.error("Veuillez choisir un titulaire valide.");
  }
});

// ============ AFFICHAGE NOTAIRE ============
watch(() => form.txt_typeActeMutation, (newVal) => {
  show.value = newVal === "Acte Notarie";
});

// ============ CHARGEMENT DES RÉFÉRENCES ============
onMounted(async () => {
  try {
    const res = await axios.post("/fetchReferencesLotissement");
    const refs = res.data.referencesLot || [];
    referencesLot.value = [...new Set(refs)].sort();
  } catch (e) {
    console.error("❌ Erreur chargement références :", e);
    referencesLot.value = [];
  }
});

// ============ RECHERCHE DOSSIER ============
const rechercherDossier = async () => {
  if (!form.txt_num_lotissementMutation || !form.txt_lotissementMutation) {
    toast.error("Veuillez renseigner le N° de lot et le lotissement.");
    return;
  }

  searching.value = true;
  try {
    const { data } = await axios.post("/dossier/verifyLotissement", {
      txt_num_lotissement: form.txt_num_lotissementMutation,
      txt_lotissement: form.txt_lotissementMutation,
    });

    if (data.duplicate) {
      formVisible.value = false;
      toast.warning(data.message || "Ce dossier existe déjà.");
      return;
    }

    if (!data.exists) {
      formVisible.value = false;
      toast.error(data.error || "Terrain introuvable.");
      return;
    }

    formVisible.value = true;
    toast.success(data.message || "Terrain trouvé !");

    if (data.terrain) {
      form.txt_num_lotissementMutation = data.terrain.txt_num_lotissement ?? form.txt_num_lotissementMutation;
      form.txt_lotissementMutation = data.terrain.txt_lotissement ?? form.txt_lotissementMutation;
    }

    if (data.titulaire) {
      const t = data.titulaire;
      form.slt_titulaireMT = t.slt_titulaire ?? "";
      form.txt_nationaliteMT = t.txt_nationalite ?? "";
      form.slt_civiliteMT = t.slt_civilite ?? "";
      form.txt_prenomMT = t.txt_prenom ?? "";
      form.txt_nomMT = t.txt_nom ?? "";
      form.slt_pieceMT = t.slt_piece ?? "";
      form.txt_numPieceMT = t.txt_numPiece ?? "";
      form.dt_date_delivranceMT = t.dt_date_delivrance ?? "";
      form.dt_date_naissanceMT = t.dt_date_naissance ?? "";
      form.txt_lieu_naissanceMT = t.txt_lieu_naissance ?? "";
      form.txt_adresseMT = t.txt_adresse ?? "";
      form.tel_telephoneMT = t.tel_telephone ?? "";
      form.txt_representantMT = t.txt_representant ?? "";
      form.tel_telRepresentantMT = t.tel_telRepresentant ?? "";

      form.slt_categoriePM_MT = t.slt_categoriePM ?? "";
      form.txt_formJuridiquePM_MT = t.txt_formJuridiquePM ?? "";
      form.txt_denominationPM_MT = t.txt_denominationPM ?? "";
      form.txt_nineaPM_MT = t.txt_nineaPM ?? "";
      form.txt_personneRepresentantPM_MT = t.txt_perssonneRepresentantPM ?? "";
      form.txt_fonctionPM_MT = t.txt_fonctionPM ?? "";
      form.txt_telephonePM_MT = t.txt_telephonePM ?? "";
      form.txt_emailPM_MT = t.txt_emailPM ?? "";
      form.txt_adressePM_MT = t.txt_adressePM ?? "";

      form.slt_etablissementPA_MT = t.slt_etablissementPA ?? "";
      form.txt_personneResponsablePA_MT = t.txt_personneResponsablePA ?? "";
      form.txt_fonctionResponsablePA_MT = t.txt_fonctionResponsablePA ?? "";
      form.txt_telephonePA_MT = t.txt_telephonePA ?? "";
      form.txt_emailPA_MT = t.txt_emailPA ?? "";
      form.txt_ministereTutelePA_MT = t.txt_ministèreTutelePA ?? t.txt_ministereTutelePA ?? "";
    }

    if (data.dossier) {
      form.txt_num_dossier = data.dossier.txt_num_dossier ?? "";
    }
  } catch (err) {
    formVisible.value = false;
    if (err.response) {
      toast.error(err.response.data?.error || "Entrez le numéro et le lotissement.");
    } else if (err.request) {
      toast.error("Aucune réponse du serveur.");
    } else {
      toast.error(err.message);
    }
  } finally {
    searching.value = false;
  }
};

// ============ RESET COMPLET ============
function resetAll() {
  form.reset();
  form.clearErrors();
  formVisible.value = false;
  fichierPDF.value = null;
  resetTitulaireSections();
  resetAcquereurSections();
  show.value = false;
  const input = document.getElementById("fichierPDFMutation");
  if (input) input.value = "";
}

// ============ SOUMISSION ============
const submitForm = function () {
  form.post(route("mutation.store"), {
    preserveScroll: true,
    onSuccess: (page) => {
      toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
      resetAll();
    },
    onError: (errors) => {
      console.error("❌ Erreurs de validation :", errors);
      const seen = new Set();
      Object.entries(errors).forEach(([key, message]) => {
        const msgs = Array.isArray(message) ? message : [message];
        msgs.forEach((msg) => {
          if (!seen.has(msg)) {
            seen.add(msg);
            toast.error(msg);
          }
        });
      });
    },
  });
};
</script>

<template>
  <Head title="Mutations" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">Mutations</h2>
    </template>

    <div class="py-12">
      <div class="flex justify-center">
        <div class="w-full max-w-8xl px-4">
          <div class="bg-white shadow-md rounded-xl">
            <!-- En-tête -->
            <div class="p-4 border-b bg-primary rounded-t-xl">
              <h1 class="text-lg text-white font-semibold">Formulaire de mutation</h1>
            </div>

            <form @submit.prevent="submitForm">
              <div class="p-6">
                <!-- ============ RECHERCHE DU DOSSIER ============ -->
                <div class="flex justify-center">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-3xl">
                    <BaseInput
                      label="N° Dossier"
                      v-model="form.txt_num_dossier"
                      name="txt_num_dossier"
                      type="text"
                      placeholder="Numéro Dossier"
                      class="lg:col-span-2"
                      @keyup.enter="rechercherDossier"
                    />
                    <BaseInput
                      label="N° Lot"
                      v-model="form.txt_num_lotissementMutation"
                      name="txt_num_lotissementMutation"
                      type="text"
                      required
                      placeholder="Numéro Lot"
                      class="lg:col-span-1"
                      @keyup.enter="rechercherDossier"
                    />
                    <BaseSelect
                      label="Lotissement"
                      v-model="form.txt_lotissementMutation"
                      name="txt_lotissementMutation"
                      :options="referencesLot.map(r => ({ value: r, label: r }))"
                      placeholder="Choisir le lotissement"
                      required
                      class="lg:col-span-2"
                    />
                    <div class="flex items-end lg:col-span-1">
                      <MazBtn
                        type="button"
                        :loading="searching"
                        :disabled="searching"
                        @click="rechercherDossier"
                        class="w-full h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 shadow-lg shadow-blue-500/50 font-medium rounded-lg text-sm"
                      >
                        {{ searching ? "Recherche…" : "Charger" }}
                      </MazBtn>
                    </div>
                  </div>
                </div>

                <!-- ============ PROPRIÉTAIRE ============ -->
                <h5 class="text-lg text-primary-txt font-bold mt-8 mb-2">Références Propriétaire</h5>
                <div class="mb-6 border-t pt-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <BaseSelect
                      label="Titulaire"
                      v-model="form.slt_titulaireMT"
                      name="slt_titulaireMT"
                      :options="TITULAIRE_OPTIONS"
                    />

                    <!-- Personne Physique -->
                    <template v-if="showSectionPP">
                      <BaseInput label="Nationalité" v-model="form.txt_nationaliteMT" name="txt_nationaliteMT" type="text" />
                      <BaseSelect label="Civilité" v-model="form.slt_civiliteMT" name="slt_civiliteMT" :options="CIVILITE_OPTIONS" />
                      <BaseInput label="Prénom" v-model="form.txt_prenomMT" name="txt_prenomMT" type="text" />
                      <BaseInput label="Nom" v-model="form.txt_nomMT" name="txt_nomMT" type="text" />
                      <BaseSelect label="Type Pièce" v-model="form.slt_pieceMT" name="slt_pieceMT" :options="PIECE_OPTIONS" />
                      <BaseInput label="N° Pièce" v-model="form.txt_numPieceMT" name="txt_numPieceMT" type="text" />
                      <BaseInput
                        label="Date Délivrance"
                        v-model="form.dt_date_delivranceMT"
                        name="dt_date_delivranceMT"
                        type="date"
                        :max="new Date().toISOString().split('T')[0]"
                      />
                      <BaseInput
                        label="Date Naissance"
                        v-model="form.dt_date_naissanceMT"
                        name="dt_date_naissanceMT"
                        type="date"
                        :max="new Date().toISOString().split('T')[0]"
                      />
                      <BaseInput label="Lieu Naissance" v-model="form.txt_lieu_naissanceMT" name="txt_lieu_naissanceMT" type="text" />
                      <BaseInput label="Adresse" v-model="form.txt_adresseMT" name="txt_adresseMT" type="text" />
                      <BaseInput label="Téléphone" v-model="form.tel_telephoneMT" name="tel_telephoneMT" type="tel" />
                      <BaseInput label="Représentant" v-model="form.txt_representantMT" name="txt_representantMT" type="text" />
                      <BaseInput label="Tél. Représentant" v-model="form.tel_telRepresentantMT" name="tel_telRepresentantMT" type="tel" />
                    </template>

                    <!-- Personne Morale -->
                    <template v-if="showSectionPM">
                      <BaseSelect label="Catégorie" v-model="form.slt_categoriePM_MT" name="slt_categoriePM_MT" :options="CATEGORIE_PM_OPTIONS" />
                      <BaseInput label="Forme Juridique" v-model="form.txt_formJuridiquePM_MT" name="txt_formJuridiquePM_MT" type="text" />
                      <BaseInput label="Dénomination" v-model="form.txt_denominationPM_MT" name="txt_denominationPM_MT" type="text" />
                      <BaseInput label="Ninea" v-model="form.txt_nineaPM_MT" name="txt_nineaPM_MT" type="text" />
                      <BaseInput label="Nom Responsable" v-model="form.txt_personneRepresentantPM_MT" name="txt_personneRepresentantPM_MT" type="text" />
                      <BaseInput label="Fonction Responsable" v-model="form.txt_fonctionPM_MT" name="txt_fonctionPM_MT" type="text" />
                      <BaseInput label="Tél. Responsable" v-model="form.txt_telephonePM_MT" name="txt_telephonePM_MT" type="tel" />
                      <BaseInput label="Email" v-model="form.txt_emailPM_MT" name="txt_emailPM_MT" type="email" />
                      <BaseInput label="Adresse Siège" v-model="form.txt_adressePM_MT" name="txt_adressePM_MT" type="text" />
                    </template>

                    <!-- État -->
                    <template v-if="showSectionPA">
                      <BaseSelect label="Établissement" v-model="form.slt_etablissementPA_MT" name="slt_etablissementPA_MT" :options="ETABLISSEMENT_OPTIONS" />
                      <BaseInput label="Personne Responsable" v-model="form.txt_personneResponsablePA_MT" name="txt_personneResponsablePA_MT" type="text" />
                      <BaseInput label="Fonction Responsable" v-model="form.txt_fonctionResponsablePA_MT" name="txt_fonctionResponsablePA_MT" type="text" />
                      <BaseInput label="Téléphone" v-model="form.txt_telephonePA_MT" name="txt_telephonePA_MT" type="tel" />
                      <BaseInput label="Email" v-model="form.txt_emailPA_MT" name="txt_emailPA_MT" type="email" />
                      <BaseInput label="Ministère Tutelle" v-model="form.txt_ministereTutelePA_MT" name="txt_ministereTutelePA_MT" type="text" />
                    </template>
                  </div>
                </div>

                <!-- ============ ACQUÉREUR ============ -->
                <h5 class="text-lg text-primary-txt font-bold mt-8 mb-2">Références Acquéreur</h5>
                <div class="mb-6 border-t pt-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <BaseSelect
                      label="Titulaire"
                      v-model="form.slt_titulaireMutation"
                      name="slt_titulaireMutation"
                      :options="TITULAIRE_OPTIONS"
                    />

                    <!-- Personne Physique -->
                    <template v-if="showSectionPP_Acq">
                      <BaseInput label="Prénom" v-model="form.txt_prenomMutation" name="txt_prenomMutation" type="text" />
                      <BaseInput label="Nom" v-model="form.txt_nomMutation" name="txt_nomMutation" type="text" />
                      <BaseInput label="Nationalité" v-model="form.txt_nationaliteMutation" name="txt_nationaliteMutation" type="text" />
                      <BaseSelect label="Civilité" v-model="form.slt_civiliteMutation" name="slt_civiliteMutation" :options="CIVILITE_OPTIONS" />
                      <BaseSelect label="Type Pièce" v-model="form.slt_pieceMutation" name="slt_pieceMutation" :options="PIECE_OPTIONS" />
                      <BaseInput label="Numéro Pièce" v-model="form.txt_numPieceMutation" name="txt_numPieceMutation" type="text" />
                      <BaseInput
                        label="Date Délivrance"
                        v-model="form.dt_date_delivranceMutation"
                        name="dt_date_delivranceMutation"
                        type="date"
                        :max="new Date().toISOString().split('T')[0]"
                      />
                      <BaseInput
                        label="Date Naissance"
                        v-model="form.dt_date_naissanceMutation"
                        name="dt_date_naissanceMutation"
                        type="date"
                        :max="new Date().toISOString().split('T')[0]"
                      />
                      <BaseInput label="Lieu Naissance" v-model="form.txt_lieu_naissanceMutation" name="txt_lieu_naissanceMutation" type="text" />
                      <BaseInput label="Adresse" v-model="form.txt_adresseMutation" name="txt_adresseMutation" type="text" />
                      <BaseInput label="Téléphone" v-model="form.tel_telephoneMutation" name="tel_telephoneMutation" type="tel" />
                    </template>

                    <!-- Personne Morale -->
                    <template v-if="showSectionPM_Acq">
                      <BaseSelect label="Catégorie" v-model="form.slt_categoriePM_Mutation" name="slt_categoriePM_Mutation" :options="CATEGORIE_PM_OPTIONS" />
                      <BaseInput label="Forme Juridique" v-model="form.txt_formJuridiquePM_Mutation" name="txt_formJuridiquePM_Mutation" type="text" />
                      <BaseInput label="Dénomination" v-model="form.txt_denominationPM_Mutation" name="txt_denominationPM_Mutation" type="text" />
                      <BaseInput label="Ninea" v-model="form.txt_nineaPM_Mutation" name="txt_nineaPM_Mutation" type="text" />
                      <BaseInput label="Nom Responsable" v-model="form.txt_personneRepresentantPM_Mutation" name="txt_personneRepresentantPM_Mutation" type="text" />
                      <BaseInput label="Fonction Responsable" v-model="form.txt_fonctionPM_Mutation" name="txt_fonctionPM_Mutation" type="text" />
                      <BaseInput label="Tél. Responsable" v-model="form.txt_telephonePM_Mutation" name="txt_telephonePM_Mutation" type="tel" />
                      <BaseInput label="Email" v-model="form.txt_emailPM_Mutation" name="txt_emailPM_Mutation" type="email" />
                      <BaseInput label="Adresse Siège" v-model="form.txt_adressePM_Mutation" name="txt_adressePM_Mutation" type="text" />
                    </template>

                    <!-- État -->
                    <template v-if="showSectionPA_Acq">
                      <BaseSelect label="Établissement" v-model="form.slt_etablissementPA_Mutation" name="slt_etablissementPA_Mutation" :options="ETABLISSEMENT_OPTIONS" />
                      <BaseInput label="Personne Responsable" v-model="form.txt_personneResponsablePA_Mutation" name="txt_personneResponsablePA_Mutation" type="text" />
                      <BaseInput label="Fonction Responsable" v-model="form.txt_fonctionResponsablePA_Mutation" name="txt_fonctionResponsablePA_Mutation" type="text" />
                      <BaseInput label="Téléphone" v-model="form.txt_telephonePA_Mutation" name="txt_telephonePA_Mutation" type="tel" />
                      <BaseInput label="Email" v-model="form.txt_emailPA_Mutation" name="txt_emailPA_Mutation" type="email" />
                      <BaseInput label="Ministère Tutelle" v-model="form.txt_ministereTutelePA_Mutation" name="txt_ministereTutelePA_Mutation" type="text" />
                    </template>
                  </div>

                  <!-- Infos mutation -->
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                    <BaseSelect
                      label="Actes"
                      v-model="form.txt_typeActeMutation"
                      name="txt_typeActeMutation"
                      :options="ACTE_OPTIONS"
                    />
                    <BaseInput
                      v-if="show"
                      label="Nom Notaire"
                      v-model="form.txt_nomNotaireMutation"
                      name="txt_nomNotaireMutation"
                      type="text"
                    />
                    <BaseSelect
                      label="Nature"
                      v-model="form.txt_natureMutation"
                      name="txt_natureMutation"
                      :options="NATURE_OPTIONS"
                    />
                    <BaseInput label="Date Acte" v-model="form.dt_dateMutation" name="dt_dateMutation" type="date" />
                    <BaseInput label="Jugement" v-model="form.txt_jugementMutation" name="txt_jugementMutation" type="text" />
                    <BaseInput label="Observation" v-model="form.txt_observationMutation" name="txt_observationMutation" type="text" />

                    <!-- Fichier PDF -->
                    <BaseInput
                      id="fichierPDFMutation"
                      label="Document PDF (max 100 Mo)"
                      name="fichierPDFMutation"
                      type="file"
                      accept="application/pdf"
                      @change="handleFileUpload"
                      autocomplete="off" 
                    />
                  </div>

                  <!-- Aperçu du fichier sélectionné -->
                  <p v-if="fichierPDF" class="mt-2 text-xs text-green-600">
                    ✅ {{ fichierPDF.name }} ({{ (fichierPDF.size / 1024 / 1024).toFixed(2) }} Mo)
                  </p>
                  <p v-if="form.errors.fichierPDFMutation" class="mt-1 text-xs text-red-500">
                    {{ form.errors.fichierPDFMutation }}
                  </p>
                </div>

                <!-- Bouton de soumission -->
                <div class="flex justify-center mt-8 gap-3">
                  <button
                    type="button"
                    @click="resetAll"
                    class="px-5 py-2.5 text-sm font-medium text-primary-txt bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Réinitialiser
                  </button>
                  <MazBtn
                    type="submit"
                    :loading="form.processing"
                    :disabled="form.processing || !formVisible"
                    class="w-64 h-8 text-white bg-gradient-to-r from-primary via-primary-light to-primary-dark hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-primary shadow-lg shadow-primary/50 font-medium rounded-lg text-sm"
                  >
                    {{ form.processing ? "Enregistrement…" : "Enregistrer" }}
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