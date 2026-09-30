<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, ref, watch } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { route } from "ziggy-js";

defineOptions({ layout: DefaultLayout });

// ============ ÉTATS ============
const formVisible = ref(false);
const referencesLot = ref([]);
const fichierPDF = ref(null);
const show = ref(false);

// Sections Propriétaire (titulaire)
const showSectionPP = ref(false); // Personne Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // État

// Sections Acquéreur
const showSectionPP_Acq = ref(false);
const showSectionPM_Acq = ref(false);
const showSectionPA_Acq = ref(false);

// ============ FORMULAIRE ============
const form = useForm({
  // Terrain
  txt_num_lotissementMutation: "",
  txt_lotissementMutation: "",
  txt_num_dossier: "", // Ajouté (utilisé dans rechercherDossier)

  // ---- Propriétaire (titulaire) ----
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
  tel_telRepresentantMT: "", // corrigé (nom complet)

  // Personne physique (titulaire)
  slt_categoriePM_MT: "",
  txt_formJuridiquePM_MT: "",
  txt_denominationPM_MT: "",
  txt_nineaPM_MT: "",
  txt_perssonneRepresentantPM_MT: "",
  txt_fonctionPM_MT: "",
  txt_telephonePM_MT: "",
  txt_emailPM_MT: "",
  txt_adressePM_MT: "",

  // Personne morale / État (titulaire)
  slt_etablissementPA_MT: "",
  txt_personneResponsablePA_MT: "",
  txt_fonctionResponsablePA_MT: "",
  txt_telephonePA_MT: "",
  txt_emailPA_MT: "",
  txt_ministèreTutelePA_MT: "",

  // ---- Acquéreur ----
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

  // Personne physique (acquéreur)
  slt_categoriePM_Mutation: "",
  txt_formJuridiquePM_Mutation: "",
  txt_denominationPM_Mutation: "",
  txt_nineaPM_Mutation: "",
  txt_perssonneRepresentantPM_Mutation: "",
  txt_fonctionPM_Mutation: "",
  txt_telephonePM_Mutation: "",
  txt_emailPM_Mutation: "",
  txt_adressePM_Mutation: "",

  // Personne morale / État (acquéreur)
  slt_etablissementPA_Mutation: "",
  txt_personneResponsablePA_Mutation: "",
  txt_fonctionResponsablePA_Mutation: "",
  txt_telephonePA_Mutation: "",
  txt_emailPA_Mutation: "",
  txt_ministèreTutelePA_Mutation: "",

  // Fichier PDF
  fichierPDFMutation: null, // sera utilisé dans le formulaire
});

// ============ FICHIER PDF ============
function handleFileUpload(event) {
  const file = event.target.files[0];
  if (!file) return;

  if (file.type !== "application/pdf") {
    toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
    event.target.value = "";
    return;
  }

  if (file.size > 100 * 1024 * 1024) {
    toast.error("❌ Le fichier dépasse 100 Mo !");
    event.target.value = "";
    return;
  }

  fichierPDF.value = file;
  form.fichierPDFMutation = file; // ✅ correction : on utilise le bon champ
}

// ============ GESTION DES SECTIONS (Propriétaire) ============
const handleSelectChangeTitulaire = () => {
  showSectionPP.value = false;
  showSectionPM.value = false;
  showSectionPA.value = false;

  switch (form.slt_titulaireMT) {
    case "Personne Physique":
      showSectionPP.value = true;
      break;
    case "Personne Morale":
      showSectionPM.value = true;
      break;
    case "Etat":
      showSectionPA.value = true;
      break;
    default:
      // Pas de toast ici pour éviter les doublons, géré par le watch
      break;
  }
};

// Watch sur le titulaire (pas de toast en plus)
watch(() => form.slt_titulaireMT, (newVal) => {
  handleSelectChangeTitulaire();
  if (!["Personne Physique", "Personne Morale", "Etat"].includes(newVal) && newVal !== "") {
    toast.error("Veuillez choisir un titulaire valide.");
  }
});

// ============ GESTION DES SECTIONS (Acquéreur) ============
const handleSelectChangeTitulaireAcquereur = () => {
  showSectionPP_Acq.value = false;
  showSectionPM_Acq.value = false;
  showSectionPA_Acq.value = false;

  switch (form.slt_titulaireMutation) {
    case "Personne Physique":
      showSectionPP_Acq.value = true;
      break;
    case "Personne Morale":
      showSectionPM_Acq.value = true;
      break;
    case "Etat":
      showSectionPA_Acq.value = true;
      break;
    default:
      break;
  }
};

watch(() => form.slt_titulaireMutation, (newVal) => {
  handleSelectChangeTitulaireAcquereur();
  if (!["Personne Physique", "Personne Morale", "Etat"].includes(newVal) && newVal !== "") {
    toast.error("Veuillez choisir un titulaire valide.");
  }
});

// ============ AFFICHAGE NOTAIRE ============
const mutationNotaire = () => {
  show.value = form.txt_typeActeMutation === "Acte Notarie";
};
watch(() => form.txt_typeActeMutation, mutationNotaire);

// ============ CHARGEMENT DES RÉFÉRENCES ============
onMounted(async () => {
  try {
    const res = await axios.post("/fetchReferencesLotissement");
    referencesLot.value = res.data.referencesLot || [];
  } catch (e) {
    console.error("❌ Erreur chargement références :", e);
    referencesLot.value = [];
  }
});

// ============ RECHERCHE DOSSIER ============
const rechercherDossier = async () => {
  try {
    const { data } = await axios.post("/dossier/verifyLotissement", {
      txt_num_lotissement: form.txt_num_lotissementMutation,
      txt_lotissement: form.txt_lotissementMutation,
    });

    if (data.duplicate) {
      formVisible.value = false;
      toast.success(data.message);
      return;
    }

    if (!data.exists) {
      formVisible.value = false;
      toast.error(data.error || "Terrain introuvable.");
      return;
    }

    formVisible.value = true;
    toast.success(data.message);

    // Remplir les champs terrain
    form.txt_num_lotissementMutation = data.terrain.txt_num_lotissement;
    form.txt_lotissementMutation = data.terrain.txt_lotissement;

    // Remplir les infos du titulaire
    if (data.titulaire) {
      form.slt_titulaireMT = data.titulaire.slt_titulaire ?? "";
      form.txt_nationaliteMT = data.titulaire.txt_nationalite ?? "";
      form.slt_civiliteMT = data.titulaire.slt_civilite ?? "";
      form.txt_prenomMT = data.titulaire.txt_prenom ?? "";
      form.txt_nomMT = data.titulaire.txt_nom ?? "";
      form.slt_pieceMT = data.titulaire.slt_piece ?? "";
      form.txt_numPieceMT = data.titulaire.txt_numPiece ?? "";
      form.dt_date_delivranceMT = data.titulaire.dt_date_delivrance ?? "";
      form.dt_date_naissanceMT = data.titulaire.dt_date_naissance ?? "";
      form.txt_lieu_naissanceMT = data.titulaire.txt_lieu_naissance ?? "";
      form.txt_adresseMT = data.titulaire.txt_adresse ?? "";
      form.tel_telephoneMT = data.titulaire.tel_telephone ?? "";
      form.txt_representantMT = data.titulaire.txt_representant ?? "";
      form.tel_telRepresentantMT = data.titulaire.tel_telRepresentant ?? "";

      // Personne physique
      form.slt_categoriePM_MT = data.titulaire.slt_categoriePM ?? "";
      form.txt_formJuridiquePM_MT = data.titulaire.txt_formJuridiquePM ?? "";
      form.txt_denominationPM_MT = data.titulaire.txt_denominationPM ?? "";
      form.txt_nineaPM_MT = data.titulaire.txt_nineaPM ?? "";
      form.txt_perssonneRepresentantPM_MT = data.titulaire.txt_perssonneRepresentantPM ?? "";
      form.txt_fonctionPM_MT = data.titulaire.txt_fonctionPM ?? "";
      form.txt_telephonePM_MT = data.titulaire.txt_telephonePM ?? "";
      form.txt_emailPM_MT = data.titulaire.txt_emailPM ?? "";
      form.txt_adressePM_MT = data.titulaire.txt_adressePM ?? "";

      // Personne morale / État
      form.slt_etablissementPA_MT = data.titulaire.slt_etablissementPA ?? "";
      form.txt_personneResponsablePA_MT = data.titulaire.txt_personneResponsablePA ?? "";
      form.txt_fonctionResponsablePA_MT = data.titulaire.txt_fonctionResponsablePA ?? "";
      form.txt_telephonePA_MT = data.titulaire.txt_telephonePA ?? "";
      form.txt_emailPA_MT = data.titulaire.txt_emailPA ?? "";
      form.txt_ministèreTutelePA_MT = data.titulaire.txt_ministèreTutelePA ?? "";
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
  }
};

// ============ SOUMISSION ============
const submitForm = function () {
  form.post(route("mutation.store"), {
    onSuccess: (page) => {
      toast.success(page.props.flash?.success || "✅ Dossier enregistré !");
      router.visit(route("mutation.create"), { replace: true });
    },
    onError: (errors) => {
      console.error("❌ Erreurs de validation :", errors);
      Object.entries(errors).forEach(([key, message]) => {
        if (Array.isArray(message)) {
          message.forEach((msg) => toast.error(msg));
        } else {
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
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">Mutations</h2>
    </template>

    <div class="py-12">
      <div class="flex justify-center">
        <div class="w-full max-w-8xl">
          <div class="bg-white shadow-md rounded-xl">
            <!-- En-tête -->
            <div class="p-4 border-b bg-primary rounded-t-xl">
              <h1 class="text-lg text-white font-semibold">Formulaire</h1>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="submitForm">
              <div class="p-6">
                <!-- Recherche du dossier -->
                <div class="flex justify-center">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-xl">
                    <div class="flex relative sm:col-span-4">
                      <div class="sm:col-span-1 mr-2">
                        <input
                          type="text"
                          v-model="form.txt_num_lotissementMutation"
                          required
                          placeholder="Numéro Lot"
                          class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                          @keyup.enter="rechercherDossier"
                        />
                      </div>
                      <div class="sm:col-span-1 mr-2">
                        <select
                          v-model="form.txt_lotissementMutation"
                          class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
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
                      class="w-36 h-auto text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-xs px-3 py-1 text-center me-2 mb-2"
                      size="medium"
                    >
                      Charger
                    </MazBtn>
                  </div>
                </div>

                <!-- Propriétaire -->
                <h5 class="text-lg text-primary-txt font-bold mt-6">Références Propriétaire</h5>
                <div class="mb-6">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-2">
                    <BaseSelect
                      label="Titulaire"
                      v-model="form.slt_titulaireMT"
                      name="slt_titulaireMT"
                      :options="[
                        { value: 'Personne Physique', label: 'Personne Physique' },
                        { value: 'Personne Morale', label: 'Personne Morale' },
                        { value: 'Etat', label: 'Etat' },
                      ]"
                    />

                    <!-- Personne Physique -->
                    <template v-if="showSectionPP">
                      <BaseInput label="Nationalité" v-model="form.txt_nationaliteMT" name="txt_nationaliteMT" type="text" />
                      <BaseSelect
                        label="Civilité"
                        v-model="form.slt_civiliteMT"
                        name="slt_civiliteMT"
                        :options="[
                          { value: 'Mr', label: 'Mr' },
                          { value: 'Mme', label: 'Mme' },
                        ]"
                      />
                      <BaseInput label="Prénom" v-model="form.txt_prenomMT" name="txt_prenomMT" type="text" />
                      <BaseInput label="Nom" v-model="form.txt_nomMT" name="txt_nomMT" type="text" />
                      <BaseSelect
                        label="Type Pièce"
                        v-model="form.slt_pieceMT"
                        name="slt_pieceMT"
                        :options="[
                          { value: 'CNI', label: 'CNI' },
                          { value: 'Passeport', label: 'Passeport' },
                        ]"
                      />
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
                      <BaseInput label="Téléphone" v-model="form.tel_telephoneMT" name="tel_telephoneMT" type="text" />
                      <BaseInput label="Représentant" v-model="form.txt_representantMT" name="txt_representantMT" type="text" />
                      <BaseInput
                        label="Tél. Représentant"
                        v-model="form.tel_telRepresentantMT"
                        name="tel_telRepresentantMT"
                        type="text"
                      />
                    </template>

                    <!-- Personne Morale -->
                    <template v-if="showSectionPM">
                      <BaseSelect
                        label="Catégorie"
                        v-model="form.slt_categoriePM_MT"
                        name="slt_categoriePM_MT"
                        :options="[
                          { value: 'Station', label: 'Station' },
                          { value: 'Industrie', label: 'Industrie' },
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
                        label="Forme Juridique"
                        v-model="form.txt_formJuridiquePM_MT"
                        name="txt_formJuridiquePM_MT"
                        type="text"
                      />
                      <BaseInput
                        label="Dénomination"
                        v-model="form.txt_denominationPM_MT"
                        name="txt_denominationPM_MT"
                        type="text"
                      />
                      <BaseInput label="Ninea" v-model="form.txt_nineaPM_MT" name="txt_nineaPM_MT" type="text" />
                      <BaseInput
                        label="Nom Responsable"
                        v-model="form.txt_perssonneRepresentantPM_MT"
                        name="txt_perssonneRepresentantPM_MT"
                        type="text"
                      />
                      <BaseInput
                        label="Fonction Responsable"
                        v-model="form.txt_fonctionPM_MT"
                        name="txt_fonctionPM_MT"
                        type="text"
                      />
                      <BaseInput
                        label="Tél. Responsable"
                        v-model="form.txt_telephonePM_MT"
                        name="txt_telephonePM_MT"
                        type="text"
                      />
                      <BaseInput label="Email" v-model="form.txt_emailPM_MT" name="txt_emailPM_MT" type="email" />
                      <BaseInput label="Adresse Siège" v-model="form.txt_adressePM_MT" name="txt_adressePM_MT" type="text" />
                    </template>

                    <!-- État -->
                    <template v-if="showSectionPA">
                      <BaseSelect
                        label="Établissement"
                        v-model="form.slt_etablissementPA_MT"
                        name="slt_etablissementPA_MT"
                        :options="[
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
                        label="Personne Responsable"
                        v-model="form.txt_personneResponsablePA_MT"
                        name="txt_personneResponsablePA_MT"
                        type="text"
                      />
                      <BaseInput
                        label="Fonction Responsable"
                        v-model="form.txt_fonctionResponsablePA_MT"
                        name="txt_fonctionResponsablePA_MT"
                        type="text"
                      />
                      <BaseInput
                        label="Téléphone"
                        v-model="form.txt_telephonePA_MT"
                        name="txt_telephonePA_MT"
                        type="text"
                      />
                      <BaseInput label="Email" v-model="form.txt_emailPA_MT" name="txt_emailPA_MT" type="email" />
                      <BaseInput
                        label="Ministère Tutelle"
                        v-model="form.txt_ministèreTutelePA_MT"
                        name="txt_ministèreTutelePA_MT"
                        type="text"
                      />
                    </template>
                  </div>
                </div>

                <!-- Acquéreur -->
                <h5 class="text-lg text-primary-txt font-bold mt-6">Références Acquéreur</h5>
                <div class="mb-6">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-2">
                    <BaseSelect
                      label="Titulaire"
                      v-model="form.slt_titulaireMutation"
                      name="slt_titulaireMutation"
                      :options="[
                        { value: 'Personne Physique', label: 'Personne Physique' },
                        { value: 'Personne Morale', label: 'Personne Morale' },
                        { value: 'Etat', label: 'Etat' },
                      ]"
                    />

                    <!-- Personne Physique -->
                    <template v-if="showSectionPP_Acq">
                      <BaseInput label="Prénom" v-model="form.txt_prenomMutation" name="txt_prenomMutation" type="text" />
                      <BaseInput label="Nom" v-model="form.txt_nomMutation" name="txt_nomMutation" type="text" />
                      <BaseInput
                        label="Nationalité"
                        v-model="form.txt_nationaliteMutation"
                        name="txt_nationaliteMutation"
                        type="text"
                      />
                      <BaseSelect
                        label="Civilité"
                        v-model="form.slt_civiliteMutation"
                        name="slt_civiliteMutation"
                        :options="[
                          { value: 'Mr', label: 'Mr' },
                          { value: 'Mme', label: 'Mme' },
                        ]"
                      />
                      <BaseSelect
                        label="Type Pièce"
                        v-model="form.slt_pieceMutation"
                        name="slt_pieceMutation"
                        :options="[
                          { value: 'CNI', label: 'CNI' },
                          { value: 'Passeport', label: 'Passeport' },
                        ]"
                      />
                      <BaseInput
                        label="Numéro Pièce"
                        v-model="form.txt_numPieceMutation"
                        name="txt_numPieceMutation"
                        type="text"
                      />
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
                      />
                      <BaseInput
                        label="Lieu Naissance"
                        v-model="form.txt_lieu_naissanceMutation"
                        name="txt_lieu_naissanceMutation"
                        type="text"
                      />
                      <BaseInput label="Adresse" v-model="form.txt_adresseMutation" name="txt_adresseMutation" type="text" />
                      <BaseInput label="Téléphone" v-model="form.tel_telephoneMutation" name="tel_telephoneMutation" type="text" />
                    </template>

                    <!-- Personne Morale (acquéreur) -->
                    <template v-if="showSectionPM_Acq">
                      <BaseSelect
                        label="Catégorie"
                        v-model="form.slt_categoriePM_Mutation"
                        name="slt_categoriePM_Mutation"
                        :options="[
                          { value: 'Station', label: 'Station' },
                          { value: 'Industrie', label: 'Industrie' },
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
                        label="Forme Juridique"
                        v-model="form.txt_formJuridiquePM_Mutation"
                        name="txt_formJuridiquePM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Dénomination"
                        v-model="form.txt_denominationPM_Mutation"
                        name="txt_denominationPM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Ninea"
                        v-model="form.txt_nineaPM_Mutation"
                        name="txt_nineaPM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Nom Responsable"
                        v-model="form.txt_perssonneRepresentantPM_Mutation"
                        name="txt_perssonneRepresentantPM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Fonction Responsable"
                        v-model="form.txt_fonctionPM_Mutation"
                        name="txt_fonctionPM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Tél. Responsable"
                        v-model="form.txt_telephonePM_Mutation"
                        name="txt_telephonePM_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Email"
                        v-model="form.txt_emailPM_Mutation"
                        name="txt_emailPM_Mutation"
                        type="email"
                      />
                      <BaseInput
                        label="Adresse Siège"
                        v-model="form.txt_adressePM_Mutation"
                        name="txt_adressePM_Mutation"
                        type="text"
                      />
                    </template>

                    <!-- État (acquéreur) -->
                    <template v-if="showSectionPA_Acq">
                      <BaseSelect
                        label="Établissement"
                        v-model="form.slt_etablissementPA_Mutation"
                        name="slt_etablissementPA_Mutation"
                        :options="[
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
                        label="Personne Responsable"
                        v-model="form.txt_personneResponsablePA_Mutation"
                        name="txt_personneResponsablePA_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Fonction Responsable"
                        v-model="form.txt_fonctionResponsablePA_Mutation"
                        name="txt_fonctionResponsablePA_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Téléphone"
                        v-model="form.txt_telephonePA_Mutation"
                        name="txt_telephonePA_Mutation"
                        type="text"
                      />
                      <BaseInput
                        label="Email"
                        v-model="form.txt_emailPA_Mutation"
                        name="txt_emailPA_Mutation"
                        type="email"
                      />
                      <BaseInput
                        label="Ministère Tutelle"
                        v-model="form.txt_ministèreTutelePA_Mutation"
                        name="txt_ministèreTutelePA_Mutation"
                        type="text"
                      />
                    </template>

                    <!-- Acte et autres champs -->
                    <BaseSelect
                      label="Actes"
                      v-model="form.txt_typeActeMutation"
                      name="txt_typeActeMutation"
                      :options="[
                        { value: 'Acte Notarie', label: 'Acte Notarié' },
                        { value: 'Acte Sous Seing Privé', label: 'Acte sous Seing Privé' },
                        { value: 'Jugement', label: 'Jugement' },
                      ]"
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
                      :options="[
                        { value: 'Vente', label: 'Vente' },
                        { value: 'Echange', label: 'Echange' },
                        { value: 'Donnation', label: 'Donnation' },
                        { value: 'Partage-Heritage', label: 'Partage-Heritage' },
                        { value: 'Autres', label: 'Autres' },
                      ]"
                    />
                    <BaseInput label="Date Acte" v-model="form.dt_dateMutation" name="dt_dateMutation" type="date" />
                    <BaseInput label="Jugement" v-model="form.txt_jugementMutation" name="txt_jugementMutation" type="text" />
                    <BaseInput label="Observation" v-model="form.txt_observationMutation" name="txt_observationMutation" type="text" />

                    <!-- Fichier PDF -->
                    <BaseInput  
                      type="file" 
                      name="fichierPDFMutation"
                      accept="application/pdf"
                      @change="handleFileUpload"
                      id="fichierPDFMutation"
                      autocomplete="off"
                    />
 

                  </div>
                </div>

                <!-- Bouton de soumission -->
                <div class="flex justify-center mt-6">
                  <MazBtn
                    type="submit"
                    no-shadow
                    no-hover-effect
                    class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-light to-primary-dark hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-primary dark:focus:ring-primary-dark shadow-lg shadow-primary/50 dark:shadow-lg dark:shadow-primary-dark font-medium rounded-lg text-sm px-5 py-2.5 text-center"
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