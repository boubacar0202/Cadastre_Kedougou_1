<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { defineProps, onMounted, ref, computed, watch, watchEffect } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";
import AppCodeAccee from "@/Components/AppCodeAccee.vue";
import MazRadio from "maz-ui/components/MazRadio";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";

defineOptions({ layout: DefaultLayout });

// Props
const { terrain, regions, departements, arrondissements, communes, titulaire, lotissements, references_usages, occupants, occupantsBP } =
  defineProps({
    terrain: Object,
    regions: Array,
    departements: Array,
    arrondissements: Array,
    communes: Array,
    lotissements: Array,
    references_usages: Array,
    occupants: Array,
    occupantsBP: Array,
  });

// États réactifs
const formVisible = ref(false);
const showModal = ref(true);
const isLoading = ref(false);
const maxOccupants = 25;
const maxOccupantsBP = 4;
const maxOccupantsCA = 3;
const maxOccupantsCL = 3;
const maxOccupantsAP = 3;
const showSectionPP = ref(false);
const showSectionPM = ref(false);
const showSectionPA = ref(false);
const showSectionPPMutation = ref(false);
const showSectionPMMutation = ref(false);
const showSectionPAMutation = ref(false);
const errorMessage = ref("");
const errorMessageNumSection = ref("");
const errorMessageNumParcelle = ref("");
const activeTab = ref("terrain"); // onglet par défaut
const showMorcellement = ref(false); 
const showAllSectionsMutation = ref(false)
const showNomNotaire = ref(false); 
const showAnnulationNICAD = ref(false); 
 
 
// ✅ Fonction setActiveTab (définie avant toute utilisation)
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

// Catégories de bâtiment
const categories = {
  "Maison individuelle": {
    "1": 205891,
    "2": 190883,
    "3": 180937,
    "4": 158411,
    "5": 119942,
    "6": 88201,
    "7": 58801,
    "8": 40663,
    "9": 11117,
    "10": 10000,
    "11": 8000,
  },
  "Immeuble collectif": {
    A: 244257,
    B: 226543,
    C: 204069,
    D: 177171,
    E: 142293,
    F: 123552,
    G: 93878,
    H: 61602,
    I: 53793,
    J: 25000,
    K: 22000,
    L: 20000,
    M: 8000,
  },
};

// ===== OPTIONS POUR BASESELECT =====
const serviceRenduOptions = [
  { value: 'Delimitation', label: 'Delimitation' },
  { value: 'Extrait de plan', label: 'Extrait de plan' },
  { value: 'Implatation', label: 'Implatation' },
  { value: 'Plan de situation', label: 'Plan de situation' },
  { value: 'Division', label: 'Division' },
  { value: 'Reconstitution des limites', label: 'Reconstitution des limites' },
  { value: 'Etat des lieux', label: 'Etat des lieux' },
  { value: 'Expertise immobilière', label: 'Expertise immobilière' },
  { value: 'Enquête cadastrale', label: 'Enquête cadastrale' },
]

const documentAdminOptions = [
  { value: "Lettre D'atribution", label: "Lettre D'atribution" },
  { value: 'Attestation Foncière', label: 'Attestation Foncière' },
  { value: 'Extrait de plan', label: 'Extrait de plan' },
  { value: 'Notification', label: 'Notification' },
  { value: 'Bail', label: 'Bail' },
  { value: 'Acte adminitratif', label: 'Acte adminitratif' },
  { value: "Permis d'occupation", label: "Permis d'occupation" },
  { value: 'Déliberation', label: 'Déliberation' },
  { value: 'Acte de vente', label: 'Acte de vente' },
  { value: 'Sans Papier', label: 'Sans Papier' },
]

const statutNicadOptions = [
  { value: 'Actif', label: 'Actif' },
  { value: 'Désactivé', label: 'Désactivé' },
  { value: 'Annulé', label: 'Annulé' },
  { value: 'Supprimé', label: 'Supprimé' },
]

const annulationNICADOptions = [
  {value:'Fusion', label:'Fusion'},
  {value:'Division/Morcellement', label:'Division/Morcellement'},
  {value:'Affecté par un Projet', label:'Affecté par un Projet'},
  {value:'Règlement Judiciaire', label:'Règlement Judiciaire'},
]

const signalementOptions = [
  { value: 'Néant', label: 'Néant' },
  { value: 'Litige', label: 'Litige' },
  { value: 'Anomalie', label: 'Anomalie' },
]
 
const dependantDomaineOptions = [
  { value: 'Domaine National', label: 'Domaine National' },
  { value: 'Domaine Public', label: 'Domaine Public' },
  { value: 'Domaine Fluvial', label: 'Domaine Fluvial' },
  { value: 'Domaine Maritime', label: 'Domaine Maritime' },
]

const ussuBornageOptions = [
  { value: 'Immatriculation', label: 'Immatriculation' },
  { value: 'Morcellement', label: 'Morcellement' },
  { value: 'Rectificatif de Limite', label: 'Rectificatif de Limite' },
  { value: 'Fusion', label: 'Fusion' },
  { value: 'Morcellement de Copropriété', label: 'Morcellement de Copropriété' },
]

const lfOptions = [
  { value: 'NO', label: 'NO' },
  { value: 'KG', label: 'KG' },
  { value: 'SM', label: 'SM' },
  { value: 'SR', label: 'SR' },
]
// Titulaire
const titulaireOptions = [
  { value: 'Personne Physique', label: 'Personne Physique' },
  { value: 'Personne Morale', label: 'Personne Morale' },
  { value: 'Etat', label: 'Etat' },
]

const civiliteOptions = [
  { value: 'Mr', label: 'Mr' },
  { value: 'Mme', label: 'Mme' },
]

const pieceOptions = [
  { value: 'CNI', label: 'CNI' },
  { value: 'Passeport', label: 'Passeport' }, 
]

const categoriePMOptions = [
  { value: 'Station', label: 'Station' },
  { value: 'Industrue', label: 'Industrue' },
  { value: 'Restaurant', label: 'Restaurant' },
  { value: 'Hotel', label: 'Hotel' },
  { value: 'Hoberge', label: 'Hoberge' },
  { value: 'Elevage', label: 'Elevage' },
  { value: 'Agrobusness', label: 'Agrobusness' },
  { value: 'Epicerie', label: 'Epicerie' },
  { value: 'Alimentation Générale', label: 'Alimentation Générale' },
  { value: 'Etablissement financier', label: 'Etablissement financier' },
  { value: 'Qincaillerie', label: 'Qincaillerie' },
  { value: 'Transport', label: 'Transport' },
  { value: 'Coopérative Habitat', label: 'Coopérative Habitat' },
  { value: 'BTP', label: 'BTP' },
  { value: 'Organisation Religieux', label: 'Organisation Religieux' },
]

const etablissementPAOptions = [
  { value: 'Sanitaire', label: 'Sanitaire' },
  { value: 'Scolaire', label: 'Scolaire' },
  { value: 'Millitaire', label: 'Millitaire' },
  { value: 'Administratif', label: 'Administratif' },
  { value: 'Aéroportuaire', label: 'Aéroportuaire' },
  { value: 'Sportif', label: 'Sportif' },
  { value: 'Universitaire', label: 'Universitaire' },
  { value: 'Autres', label: 'Autres' },
]

// Acquereur 
const titulaireOptionsMutation = [
  { value: '', label: '' },
  { value: 'Personne Physique', label: 'Personne Physique' },
  { value: 'Personne Morale', label: 'Personne Morale' },
  { value: 'Etat', label: 'Etat' },
]

const civiliteOptionsMutation = [
  { value: 'Mr', label: 'Mr' },
  { value: 'Mme', label: 'Mme' },
]

const pieceOptionsMutation = [
  { value: 'CNI', label: 'CNI' },
  { value: 'Passeport', label: 'Passeport' },
]

const categoriePMOptionsMutation = [
  { value: 'Station', label: 'Station' },
  { value: 'Industrue', label: 'Industrue' },
  { value: 'Restaurant', label: 'Restaurant' },
  { value: 'Hotel', label: 'Hotel' },
  { value: 'Hoberge', label: 'Hoberge' },
  { value: 'Elevage', label: 'Elevage' },
  { value: 'Agrobusness', label: 'Agrobusness' },
  { value: 'Epicerie', label: 'Epicerie' },
  { value: 'Alimentation Générale', label: 'Alimentation Générale' },
  { value: 'Etablissement financier', label: 'Etablissement financier' },
  { value: 'Qincaillerie', label: 'Qincaillerie' },
  { value: 'Transport', label: 'Transport' },
  { value: 'Coopérative Habitat', label: 'Coopérative Habitat' },
  { value: 'BTP', label: 'BTP' },
  { value: 'Organisation Religieux', label: 'Organisation Religieux' },
]


const etablissementPAOptionsMutation = [
  { value: 'Sanitaire', label: 'Sanitaire' },
  { value: 'Scolaire', label: 'Scolaire' },
  { value: 'Millitaire', label: 'Millitaire' },
  { value: 'Administratif', label: 'Administratif' },
  { value: 'Aéroportuaire', label: 'Aéroportuaire' },
  { value: 'Sportif', label: 'Sportif' },
  { value: 'Universitaire', label: 'Universitaire' },
  { value: 'Autres', label: 'Autres' },
]

const usageOptions = [
  { value: 'Habitation', label: 'Habitation' },
  { value: 'Commercial', label: 'Commercial' },
  { value: 'Agricole', label: 'Agricole' },
  { value: 'Industriel', label: 'Industriel' },
  { value: 'Professionnelle', label: 'Professionnelle' },
  { value: 'Culte', label: 'Culte' },
  { value: 'Administratif', label: 'Administratif' },
]

const residenceOptions = [
  { value: 'Residence Principal', label: 'Residence Principal' },
  { value: 'Residence Secondaire', label: 'Residence Secondaire' },
]

const acteMutationOptions = [
  { value: 'Acte Notarie', label: 'Acte Notarié' },
  { value: 'Acte Sous Seing Privé', label: 'Acte sous Seing Privé' },
  { value: 'Jugement', label: 'Jugement' },
]

const natureMutationOptions = [ 
  { value: 'Vente', label: 'Vente' },
  { value: 'Echange', label: 'Echange' },
  { value: 'Donnation', label: 'Donnation' },
  { value: 'Partage-Heritage', label: 'Partage-Heritage' },
  { value: 'Autres', label: 'Autres' }
]
const secteurOptions = [
  { value: 1, label: '1' },
  { value: 2, label: '2' },
  { value: 3, label: '3' },
]

const categorieMaisonOptions = Array.from({ length: 11 }, (_, i) => ({
  value: i + 1, label: String(i + 1),
}))

const categorieImmeubleOptions = ['A','B','C','D','E','F','G','H','I','J','K','L','M']
  .map(v => ({ value: v, label: v }))

const categorieCAOptions = Array.from({ length: 4 }, (_, i) => ({
  value: i + 1, label: String(i + 1),
}))

const categorieCLOptions = Array.from({ length: 7 }, (_, i) => ({
  value: i + 1, label: String(i + 1),
}))

const coefficientOptions = [
  { value: '0.10', label: '0.1' },
  { value: '0.20', label: '0.2' },
  { value: '0.30', label: '0.3' },
  { value: '0.40', label: '0.4' },
  { value: '0.50', label: '0.5' },
  { value: '0.60', label: '0.6' },
  { value: '0.70', label: '0.7' },
  { value: '0.80', label: '0.8' },
]

const coefficientAPOptions = [
  ...coefficientOptions,
  { value: '0.90', label: '0.9' },
  { value: '1.00', label: '1' },
]

const batimentSecondaireOptions = [
  { value: 'Secondaire', label: 'Secondaire' },
  { value: 'Tertiaire', label: 'Tertiaire' },
  { value: 'Dépendance', label: 'Dépendance' },
  { value: 'Partie Saillante', label: 'Partie Saillante' },
]

const coursAmenageesOptions = [
  { value: 'Cours Amenagées Totale', label: 'Cours Amenagées Totale' },
  { value: 'Cours Avant', label: 'Cours Avant' },
  { value: 'Cours Arrière', label: 'Cours Arrière' },
  { value: 'Cours Latérale Droite', label: 'Cours Latérale Droite' },
  { value: 'Cours Latérale Gauche', label: 'Cours Latérale Gauche' },
  { value: 'Cours Restante', label: 'Cours Restante' },
]

const clotureOptions = [
  { value: 'Longueur Totale', label: 'Longueur Totale' },
  { value: 'Longueur Avant', label: 'Longueur Avant' },
  { value: 'Longueur Arrière', label: 'Longueur Arrière' },
  { value: 'Longueur Latérale Droite', label: 'Longueur Latérale Droite' },
  { value: 'Longueur Latérale Gauche', label: 'Longueur Latérale Gauche' },
  { value: 'Longueur Restante', label: 'Longueur Restante' },
]


// Formatage des occupants
function formatOccupants(references_usages) {
  if (Array.isArray(references_usages)) return references_usages.map((o) => formatOccupant(o));
  if (references_usages) return [formatOccupant(references_usages)];
  return [formatOccupant()];
}
function formatOccupant(o = {}) {
  return {
    txt_nomOccupantTG: o.txt_nomOccupantTG || "",
    txt_numAppartementTG: o.txt_numAppartementTG || "",
    txt_activiteTG: o.txt_activiteTG || "",
    txt_nineaTG: o.txt_nineaTG || "",
    tel_telephoneTG: o.tel_telephoneTG || "",
    nbr_montantLoyerTG: o.nbr_montantLoyerTG || 0,
    txt_dateLieuNaissanceTG: o.txt_dateLieuNaissanceTG || "",
    txt_cniPasseportTG: o.txt_cniPasseportTG || "",
    dt_dateDelivranceTG: o.dt_dateDelivranceTG || "",
  };
}
function formatOccupantsBP(evaluations_batis) {
  if (Array.isArray(evaluations_batis)) return evaluations_batis.map((o) => formatOccupantBP(o));
  if (evaluations_batis) return [formatOccupantBP(evaluations_batis)];
  return [formatOccupantBP()];
}
function formatOccupantBP(o = {}) {
  return {
    slt_dependant_domaineTG: o.slt_dependant_domaineTG || "",
    slt_categorieTG: o.slt_categorieTG || "",
    nbr_prix_metre_carreTG: o.nbr_prix_metre_carreTG || "",
    nbr_surface_bati_solTG: o.nbr_surface_bati_solTG || "",
    nbr_niveauTG: o.nbr_niveauTG || "",
    nbr_surface_utileTG: o.nbr_surface_utileTG || "",
    slt_coeffTG: o.slt_coeffTG || "",
    nbr_surface_corrigerTG: o.nbr_surface_corrigerTG || "",
    nbr_valeurTG: o.nbr_valeurTG || "",
  };
}
function formatOccupantsCA(evaluations_cours_amenagees) {
  if (Array.isArray(evaluations_cours_amenagees)) return evaluations_cours_amenagees.map((o) => formatOccupantCA(o));
  if (evaluations_cours_amenagees) return [formatOccupantCA(evaluations_cours_amenagees)];
  return [formatOccupantCA()];
}
function formatOccupantCA(o = {}) {
  return {
    slt_cours_amenager_totale: o.slt_cours_amenager_totale || "",
    nbr_surface_ca_total: o.nbr_surface_ca_total || "",
    slt_categorie_ca_total: o.slt_categorie_ca_total || "",
    nbr_prix_metre_carre_ca_total: o.nbr_prix_metre_carre_ca_total || "",
    nbr_coefficient_ca_total: o.nbr_coefficient_ca_total || "",
    nbr_valeur_ca_total: o.nbr_valeur_ca_total || "",
  };
}
function formatOccupantsCL(evaluations_clotures) {
  if (Array.isArray(evaluations_clotures)) return evaluations_clotures.map((o) => formatOccupantCL(o));
  if (evaluations_clotures) return [formatOccupantCL(evaluations_clotures)];
  return [formatOccupantCL()];
}
function formatOccupantCL(o = {}) {
  return {
    slt_cours_amenager_clo: o.slt_cours_amenager_clo || "",
    nbr_longueur_avant_clo: o.nbr_longueur_avant_clo || "",
    slt_categorie_clo: o.slt_categorie_clo || "",
    nbr_prix_metre_carre_clo: o.nbr_prix_metre_carre_clo || "",
    nbr_coefficient_clo: o.nbr_coefficient_clo || "",
    nbr_valeur_clo: o.nbr_valeur_clo || "",
  };
}
function formatOccupantsAP(evaluations_amenagements) {
  if (Array.isArray(evaluations_amenagements)) return evaluations_amenagements.map((o) => formatOccupantAP(o));
  if (evaluations_amenagements) return [formatOccupantAP(evaluations_amenagements)];
  return [formatOccupantAP()];
}
function formatOccupantAP(o = {}) {
  return {
    txt_designation_am: o.txt_designation_am || "",
    nbr_valeur_unitaire_am: o.nbr_valeur_unitaire_am || "",
    nbr_quantile_am: o.nbr_quantile_am || "",
    slt_coeficien_am: o.slt_coeficien_am || "",
    nbr_valeur_am: o.nbr_valeur_am || "",
  };
}

// Formatage date
const formatDate = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

// Mutation 
const mutationSource = terrain?.mutation || terrain?.mutations?.[0] || {};

// ================= FORMULAIRE =================
const form = useForm({ 
  txt_nicad: terrain?.txt_nicad || "",
  // Dossier
  txt_num_dossier: terrain?.dossier?.txt_num_dossier ?? "",
  txt_num_dordre: terrain?.dossier?.txt_num_dordre || "",
  slt_service_rendu: terrain?.dossier?.slt_service_rendu || "",
  txt_etat_cession: terrain?.dossier?.txt_etat_cession || "",
  txt_cession_definitive: terrain?.dossier?.txt_cession_definitive || "",
  dt_date_creation: formatDate(terrain?.dossier?.dt_date_creation) || "",
  // Terrain
  slt_region: terrain?.region?.id || "",
  slt_departement: terrain?.departement?.id || "",
  slt_commune: terrain?.commune?.id || "",
  txt_codeCommune: terrain?.commune?.txt_codeCommune || "",
  slt_arrondissement: terrain?.arrondissement?.id || "",
  txt_lotissement: terrain?.txt_lotissement || "",
  txt_HorsLotissement: terrain?.txt_HorsLotissement || "",
  txt_num_lotissement: terrain?.txt_num_lotissement || "",
  txt_num_section: terrain?.txt_num_section || "",
  txt_num_parcelle: terrain?.txt_num_parcelle || "",
  txt_num_titre: terrain?.txt_num_titre || "",
  txt_titre_mere: terrain?.txt_titre_mere || "", 
  txt_appartement: terrain?.txt_appartement || "",
  nbr_surface: terrain?.nbr_surface || "",
  slt_document_admin: terrain?.slt_document_admin || "",
  txt_num_deliberation: terrain?.txt_num_deliberation || "",
  dt_date_deliberation: terrain?.dt_date_deliberation || "",
  slt_statut_nicad: terrain?.slt_statut_nicad || "",
  slt_motif_annulation_nicad: terrain?.slt_motif_annulation_nicad || "",
  slt_signalement: terrain?.slt_signalement || "",
  slt_designationSignalement: terrain?.slt_designationSignalement || "",
  // Ref. cadastrales
  rd_immatriculation_terrain: terrain?.references_cadastrales?.rd_immatriculation_terrain || "",
  slt_dependant_domaine: terrain?.references_cadastrales?.slt_dependant_domaine || "",
  ussu_bornage: terrain?.references_cadastrales?.ussu_bornage || "",
  slt_lf: terrain?.references_cadastrales?.slt_lf || "",
  txt_num_requisition: terrain?.references_cadastrales?.txt_num_requisition || "",
  txt_surface_bornage: terrain?.references_cadastrales?.txt_surface_bornage || "",
  dt_date_bornage: terrain?.references_cadastrales?.dt_date_bornage || "",
  txt_nom_geometre: terrain?.references_cadastrales?.txt_nom_geometre || "",
  // Titulaire
  slt_titulaire: terrain?.titulaire?.slt_titulaire || "",
  txt_nationalite: terrain?.titulaire?.txt_nationalite || "",
  slt_civilite: terrain?.titulaire?.slt_civilite || "",
  txt_prenom: terrain?.titulaire?.txt_prenom || "",
  txt_nom: terrain?.titulaire?.txt_nom || "",
  slt_piece: terrain?.titulaire?.slt_piece || "",
  txt_numPiece: terrain?.titulaire?.txt_numPiece || "",
  dt_date_delivrance: terrain?.titulaire?.dt_date_delivrance || "",
  dt_date_naissance: terrain?.titulaire?.dt_date_naissance || "",
  txt_lieu_naissance: terrain?.titulaire?.txt_lieu_naissance || "",
  txt_adresse: terrain?.titulaire?.txt_adresse || "",
  tel_telephone: terrain?.titulaire?.tel_telephone || "",
  txt_ninea: terrain?.titulaire?.txt_ninea || "",
  eml_email: terrain?.titulaire?.eml_email || "",
  txt_representant: terrain?.titulaire?.txt_representant || "",
  tel_telRepresentant: terrain?.titulaire?.tel_telRepresentant || "",
  slt_categoriePM: terrain?.titulaire?.slt_categoriePM || "",
  txt_formJuridiquePM: terrain?.titulaire?.txt_formJuridiquePM || "",
  txt_denominationPM: terrain?.titulaire?.txt_denominationPM || "",
  txt_nineaPM: terrain?.titulaire?.txt_nineaPM || "",
  txt_perssonneRepresentantPM: terrain?.titulaire?.txt_perssonneRepresentantPM || "",
  txt_fonctionPM: terrain?.titulaire?.txt_fonctionPM || "",
  txt_telephonePM: terrain?.titulaire?.txt_telephonePM || "",
  txt_emailPM: terrain?.titulaire?.txt_emailPM || "",
  txt_adressePM: terrain?.titulaire?.txt_adressePM || "",
  slt_etablissementPA: terrain?.titulaire?.slt_etablissementPA || "",
  txt_personneResponsablePA: terrain?.titulaire?.txt_personneResponsablePA || "",
  txt_fonctionResponsablePA: terrain?.titulaire?.txt_fonctionResponsablePA || "",
  txt_telephonePA: terrain?.titulaire?.txt_telephonePA || "",
  txt_emailPA: terrain?.titulaire?.txt_emailPA || "",
  txt_ministereTutelePA: terrain?.titulaire?.txt_ministereTutelePA || "",
  fichierPDF: terrain?.titulaire?.fichierPDF || null,
  // Muration  
 
  slt_titulaireMutation: mutationSource.slt_titulaireMutation || "",
  txt_prenomMutation: mutationSource.txt_prenomMutation || "",
  txt_nomMutation: mutationSource.txt_nomMutation || "",
  txt_nationaliteMutation: mutationSource.txt_nationaliteMutation || "",
  slt_civiliteMutation: mutationSource.slt_civiliteMutation || "",
  slt_pieceMutation: mutationSource.slt_pieceMutation || "",
  txt_numPieceMutation: mutationSource.txt_numPieceMutation || "",
  dt_date_delivranceMutation: mutationSource.dt_date_delivranceMutation || "",
  dt_date_naissanceMutation: mutationSource.dt_date_naissanceMutation || "",
  txt_lieu_naissanceMutation: mutationSource.txt_lieu_naissanceMutation || "",
  txt_adresseMutation: mutationSource.txt_adresseMutation || "",
  tel_telephoneMutation: mutationSource.tel_telephoneMutation || "",
  txt_representantMutation: mutationSource.txt_representantMutation || "",
  tel_telRepresentantMutation: mutationSource.tel_telRepresentantMutation || "",
  txt_typeActeMutation: mutationSource.txt_typeActeMutation || "",
  txt_nomNotaireMutation: mutationSource.txt_nomNotaireMutation || "",
  dt_dateMutation: mutationSource.dt_dateMutation || "",
  txt_natureMutation: mutationSource.txt_natureMutation || "",
  txt_jugementMutation: mutationSource.txt_jugementMutation || "",
  txt_observationMutation: mutationSource.txt_observationMutation || "",

  slt_categoriePM_Mutation: mutationSource.slt_categoriePM_Mutation || "",
  txt_formJuridiquePM_Mutation: mutationSource.txt_formJuridiquePM_Mutation || "",
  txt_denominationPM_Mutation: mutationSource.txt_denominationPM_Mutation || "",
  txt_nineaPM_Mutation: mutationSource.txt_nineaPM_Mutation || "",
  txt_perssonneRepresentantPM_Mutation: mutationSource.txt_perssonneRepresentantPM_Mutation || "",
  txt_fonctionPM_Mutation: mutationSource.txt_fonctionPM_Mutation || "",
  txt_telephonePM_Mutation: mutationSource.txt_telephonePM_Mutation || "",
  txt_emailPM_Mutation: mutationSource.txt_emailPM_Mutation || "",
  txt_adressePM_Mutation: mutationSource.txt_adressePM_Mutation || "",

  slt_etablissementPA_Mutation: mutationSource.slt_etablissementPA_Mutation || "",
  txt_personneResponsablePA_Mutation: mutationSource.txt_personneResponsablePA_Mutation || "",
  txt_fonctionResponsablePA_Mutation: mutationSource.txt_fonctionResponsablePA_Mutation || "",
  txt_telephonePA_Mutation: mutationSource.txt_telephonePA_Mutation || "",
  txt_emailPA_Mutation: mutationSource.txt_emailPA_Mutation || "",
  txt_ministereTutelePA_Mutation: mutationSource.txt_ministereTutelePA_Mutation || "",

  fichierPDFMutation: mutationSource.fichierPDFMutation || null,
  // Références usage
  slt_usage: terrain?.references_usages?.[0]?.slt_usage || "",
  slt_residence: terrain?.references_usages?.[0]?.slt_residence || "",
  terrain_id: terrain?.terrain?.id || null,
  occupants: formatOccupants(terrain?.references_usages),
  nbr_montantLoyerTotal: terrain?.references_usages?.nbr_montantLoyerTotal || "",
  nbr_TVATotal: terrain?.references_usages?.nbr_TVATotal || "",
  // Évaluation terrain
  txt_superficie_totale: terrain?.evaluations_terrains?.txt_superficie_totale || "",
  txt_superficie_bati_sol: terrain?.evaluations_terrains?.txt_superficie_bati_sol || "",
  slt_secteur: terrain?.evaluations_terrains?.slt_secteur || "",
  nbr_prix_metre_carre: terrain?.evaluations_terrains?.nbr_prix_metre_carre || "",
  nbr_valeur_terrain: terrain?.evaluations_terrains?.nbr_valeur_terrain || "",
  nbr_valeurVenaleLimeuble: terrain?.evaluations_terrains?.nbr_valeurVenaleLimeuble ?? 0,
  nbr_valeurLocative: terrain?.evaluations_terrains?.nbr_valeurLocative || 0,
  dt_dateEvaluation: terrain?.evaluations_terrains?.dt_dateEvaluation || "",
  // Évaluation bâti
  txt_dependant_domainePR: terrain?.evaluations_batis?.txt_dependant_domainePR || "",
  slt_categoriePR: terrain?.evaluations_batis?.slt_categoriePR || "",
  nbr_prix_metre_carrePR: terrain?.evaluations_batis?.nbr_prix_metre_carrePR || "",
  nbr_surface_bati_solPR: terrain?.evaluations_batis?.nbr_surface_bati_solPR || "",
  nbr_niveauPR: terrain?.evaluations_batis?.nbr_niveauPR || "",
  nbr_surface_utilePR: terrain?.evaluations_batis?.nbr_surface_utilePR || "",
  slt_coeffPR: terrain?.evaluations_batis?.slt_coeffPR || "",
  nbr_surface_corrigerPR: terrain?.evaluations_batis?.nbr_surface_corrigerPR || "",
  nbr_valeurPR: terrain?.evaluations_batis?.nbr_valeurPR || "",
  currentCat: terrain?.evaluations_batis?.currentCat || "Maison individuelle",
  occupantsBP: formatOccupantsBP(terrain?.evaluations_batis),
  txt_valeur_terrain_bati: terrain?.evaluations_batis?.txt_valeur_terrain_bati || "",
  // Cours aménagées
  occupantsCA: formatOccupantsCA(terrain?.evaluations_cours_amenagees),
  nbr_valeur_total_ca: terrain?.evaluations_cours_amenagees?.nbr_valeur_total_ca || "",
  // Clôtures
  occupantsCL: formatOccupantsCL(terrain?.evaluations_clotures),
  nbr_valeur_total_clotur: terrain?.evaluations_clotures?.nbr_valeur_total_clotur || "",
  // Aménagements particuliers
  occupantsAP: formatOccupantsAP(terrain?.evaluations_amenagements),
  nbr_valeur_totale_ap: terrain?.evaluations_amenagements?.nbr_valeur_totale_ap || "",
  // Champs ajoutés
  txt_superficie_non_bati: "",
});

// ================= WATCHERS DE NETTOYAGE ================= 

// 1. Nettoyage des champs selon le titulaire
watch(() => form.slt_titulaire, (newVal, oldVal) => {
  if (!newVal || newVal === oldVal) return;

  const resetFields = (section) => {
    const fields = {
      PP: ['txt_nationalite', 'slt_civilite', 'txt_prenom', 'txt_nom', 'slt_piece',
           'txt_numPiece', 'dt_date_delivrance', 'dt_date_naissance', 'txt_lieu_naissance',
           'txt_adresse', 'tel_telephone', 'txt_representant', 'tel_telRepresentant',
           'eml_email', 'txt_ninea'],
      PM: ['slt_categoriePM', 'txt_formJuridiquePM', 'txt_denominationPM', 'txt_nineaPM',
           'txt_perssonneRepresentantPM', 'txt_fonctionPM', 'txt_telephonePM',
           'txt_emailPM', 'txt_adressePM'],
      PA: ['slt_etablissementPA', 'txt_personneResponsablePA', 'txt_fonctionResponsablePA',
           'txt_telephonePA', 'txt_emailPA', 'txt_ministereTutelePA']
    };
    fields[section]?.forEach(field => { form[field] = ''; });
  };

  resetFields('PP');
  resetFields('PM');
  resetFields('PA');
  handleSelectChangeTitulaire();
});

// 1. Nettoyage des champs selon le titulaire Mutation
watch(() => form.slt_titulaireMutation, (newVal, oldVal) => {
  if (!newVal || newVal === oldVal) return;

  const resetFields = (section) => {
    const fields = {
      PP_M: ['txt_nationaliteMutation', 'slt_civiliteMutation', 'txt_prenomMutation', 'txt_nomMutation', 'slt_pieceMutation',
           'txt_numPieceMutation', 'dt_date_delivranceMutation', 'dt_date_naissanceMutation', 'txt_lieu_naissanceMutation',
           'txt_adresseMutation', 'tel_telephoneMutation', 'txt_representantMutation', 'tel_telRepresentantMutation',
           'eml_emailMutation', 'txt_nineaMutation'],
      PM_M: ['slt_categoriePM_Mutation', 'txt_formJuridiquePM_Mutation', 'txt_denominationPM_Mutation', 'txt_nineaPM_Mutation',
           'txt_perssonneRepresentantPM_Mutation', 'txt_fonctionPM_Mutation', 'txt_telephonePM_Mutation',
           'txt_emailPM_Mutation', 'txt_adressePM_Mutation'],
      PA_M: ['slt_etablissementPA_Mutation', 'txt_personneResponsablePA_Mutation', 'txt_fonctionResponsablePA_Mutation',
           'txt_telephonePA_Mutation', 'txt_emailPA_Mutation', 'txt_ministereTutelePA_Mutation']
    };
    fields[section]?.forEach(field => { form[field] = ''; });
  };

  resetFields('PP_M');
  resetFields('PM_M');
  resetFields('PA_M');
  handleSelectChangeTitulaireMutation();
});

// Titulaire
const handleSelectChangeTitulaire = () => {
  showSectionPP.value = false;
  showSectionPM.value = false;
  showSectionPA.value = false;
  // showAllSections.value = false;

  switch (form.slt_titulaire) {
    case "Personne Physique": showSectionPP.value = true; break;
    case "Personne Morale": showSectionPM.value = true; break;
    case "Etat": showSectionPA.value = true; break;
  }
};
watch(() => form.slt_titulaire, handleSelectChangeTitulaire);
onMounted(() => {
  handleSelectChangeTitulaire();
  handleSelectChangeTitulaireMutation();
});

// Mutation
const handleSelectChangeTitulaireMutation = () => {
  showSectionPPMutation.value = false;
  showSectionPMMutation.value = false;
  showSectionPAMutation.value = false; 

  switch (form.slt_titulaireMutation) {
    case "Personne Physique": showSectionPPMutation.value = true; break;
    case "Personne Morale": showSectionPMMutation.value = true; break;
    case "Etat": showSectionPAMutation.value = true; break;
  }
};
// watch(() => form.slt_titulaire_MT, handleSelectChangeTitulaireMutation);
watch(() => form.slt_titulaireMutation, (newVal) => {
  if (newVal) {
    showAllSectionsMutation.value = true;  // ✅ Ouvre automatiquement la grille
  }
  handleSelectChangeTitulaireMutation();
});

// AFFICHAGE NOTAIRE 
const mutationNotaire = () => {
  showNomNotaire.value = form.txt_typeActeMutation === "Acte Notarie";
};
watch(() => form.txt_typeActeMutation, mutationNotaire);

// 2. Nettoyage de txt_appartement si on sort du Morcellement
watch(() => form.ussu_bornage, (newVal) => {
  if (newVal !== "Morcellement de Copropriété") {
    form.txt_appartement = '';
  }
});

// 3. Réinitialisation des catégories de bâtiment lors du changement de type
watch(() => form.currentCat, () => {
  form.slt_categoriePR = '';
  form.occupantsBP.forEach(occ => {
    occ.slt_categorieTG = '';
  });
});

// 4. Réinitialisation des champs selon immatriculation
watch(() => form.rd_immatriculation_terrain, (newVal) => {
  const isImm = (newVal || '').trim().toLowerCase() === 'terrain immatriculé';
  if (!isImm) {
    form.ussu_bornage = '';
    form.txt_titre_mere = '';
    form.txt_num_titre = '';
    form.slt_lf = '';
    form.txt_num_requisition = '';
    form.txt_surface_bornage = '';
    form.dt_date_bornage = '';
    form.txt_appartement = '';
    form.txt_nom_geometre = '';
    showMorcellement.value = false;
  } else {
    form.slt_dependant_domaine = '';
  }
});

// ================= NICAD CALCULÉ =================
const selectedCommune = computed(() =>
  (communes || []).find((commune) => commune.id == form.slt_commune)
);

const calculerNicad = () => {
  const prefix = String(selectedCommune.value?.txt_codeCommune || '').replace(/\s/g, '');
  const section = String(form.txt_num_section || '').replace(/\s/g, '');
  const parcelle = String(form.txt_num_parcelle || '').replace(/\s/g, '');
  const appartement = String(form.txt_appartement || '').replace(/\s/g, '');

  // Validation de base : préfixe non vide, section 3 chiffres, parcelle 5 chiffres
  if (prefix && /^\d{3}$/.test(section) && /^\d{5}$/.test(parcelle)) {
    // Si appartement est présent, il doit faire 3 chiffres
    if (appartement.length > 0 && !/^\d{3}$/.test(appartement)) {
      return null; // appartement invalide
    }
    // Construire le NICAD
    let nicad = prefix + section + parcelle + appartement;
    // Longueur minimale 16
    if (nicad.length >= 16) {
      return nicad;
    }
  }
  return null;
};

// Watcher pour mettre à jour le NICAD lorsque les champs changent
watch(
  [() => form.slt_commune, () => form.txt_num_section, () => form.txt_num_parcelle, () => form.txt_appartement],
  () => {
    const nicad = calculerNicad();
    if (nicad) {
      form.txt_nicad = nicad;
    }
    // Si le calcul échoue, on garde l'ancienne valeur (chargée ou vide)
  },
  { immediate: true }
);
 
// Validation des champs numériques
const validateNumericField = (field, maxLength, errorRef, label) => {
  let value = (form[field] ?? "").toString().replace(/\D/g, "");
  if (value.length > maxLength) value = value.slice(0, maxLength);
  form[field] = value;
  errorRef.value = value.length < maxLength ? `❌ ${maxLength} chiffres MAX.` : "";
};
const validateInputApartement = () => {
  validateNumericField("txt_appartement", 3, errorMessage, "Appartement");
};
const validateInputNumSection = () => {
  validateNumericField("txt_num_section", 3, errorMessageNumSection, "Section");
};
const validateInputNumParcelle = () => {
  validateNumericField("txt_num_parcelle", 5, errorMessageNumParcelle, "Parcelle");
};

// Annulation NICAD
const handleSelectChangeAnnulationNICAD = () => {
    showAnnulationNICAD.value = form.slt_statut_nicad === 'Annulé';
    if (!showAnnulationNICAD.value) {
        form.slt_motif_annulation_nicad = '';
    }
};
watch(() => form.slt_statut_nicad, handleSelectChangeAnnulationNICAD);
 
// Statut Signalement 
const showDesignationSignalement = ref(false); 

// Méthode
const handleSelectChangeDesignationSignalement = () => {
  showDesignationSignalement.value = (form.slt_signalement === 'Litige') || (form.slt_signalement === 'Anomalie');
    if(!showDesignationSignalement.value) {
        form.slt_designationSignalement = '';
    }
}  
watch(() => form.slt_signalement, handleSelectChangeDesignationSignalement);

// Designation Signalement
const designationsSignalementOptions = { 
    'Litige': ['Pluralité de titres', 'Cession multiple', 'Instance judiciaire','Contestation de propriété','Contestation de limites',
            'Contentieux entre ayants droit','Revendication de propriété','Succession litigieuse','Autre litige'
    ], 
    'Anomalie': ['Absence de document justificatif','Dossier incomplet','Document irrégulier',
            'Document non conforme','Empiètement','Limites incertaines','Limites non matérialisées','Superficie à vérifier',
            'Géométrie à corriger','Données cadastrales incohérentes','Situation foncière à régulariser','Autre anomalie'
    ]
};  
const designationsSignalement = ref(designationsSignalementOptions[form.slt_signalement] || []);
handleSelectChangeDesignationSignalement();
watch(() => form.slt_signalement, (newCategorie) => {
    designationsSignalement.value = designationsSignalementOptions[newCategorie] || [];
    form.slt_designationSignalement = '';
});

// Gestion fichier PDF
function handleFileChangePDF(event) {
  const file = event.target.files[0];
  if (file && file.type === "application/pdf") {
    form.fichierPDF = file;
  } else {
    form.fichierPDF = null;
    toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
  }
}

// Gestion fichier PDF Mutation
function handleFileChangePDFMutation(event) {
  const file = event.target.files[0];
  if (file && file.type === "application/pdf") {
    form.fichierPDFMutation = file;
  } else {
    form.fichierPDFMutation = null;
    toast.error("❌ Veuillez sélectionner un fichier PDF valide.");
  }
}

// Immatriculation
const isImmatricule = computed(() => {
  const valeur = (form.rd_immatriculation_terrain || "").trim().toLowerCase();
  return valeur === "terrain immatriculé";
});

// Bornage
const handleSelectChange = () => {
  showMorcellement.value = form.ussu_bornage === "Morcellement de Copropriété";
};
watch(() => form.ussu_bornage, handleSelectChange);
onMounted(() => {
  handleSelectChange();
});

  
// Occupants : ajout/retrait
function addBlock() {
  if (form.occupants.length < maxOccupants) {
    form.occupants.push({
      id: Date.now() + Math.random(),
      txt_nomOccupantTG: "",
      txt_numAppartementTG: "",
      txt_activiteTG: "",
      txt_nineaTG: "",
      tel_telephoneTG: "",
      nbr_montantLoyerTG: 0,
      txt_dateLieuNaissanceTG: "",
      txt_cniPasseportTG: "",
      dt_dateDelivranceTG: "",
    });
  }
}
function removeBlock(i) {
  if (form.occupants.length > 1) form.occupants.pop();
}
function addBlockBP() {
  if (form.occupantsBP.length < maxOccupantsBP) {
    form.occupantsBP.push({
      id: Date.now() + Math.random(),
      slt_dependant_domaineTG: "",
      slt_categorieTG: "",
      nbr_prix_metre_carreTG: "",
      nbr_surface_bati_solTG: "",
      nbr_niveauTG: "",
      nbr_surface_utileTG: "",
      slt_coeffTG: "",
      nbr_surface_corrigerTG: "",
      nbr_valeurTG: "",
    });
  } else {
    toast.error("Impossible d'ajouter plus de 5 occupants.");
  }
}
function removeBlockBP(i) {
  if (form.occupantsBP.length > 1) form.occupantsBP.pop();
}
function addBlockCA() {
  if (form.occupantsCA.length < maxOccupantsCA) {
    form.occupantsCA.push({
      id: Date.now() + Math.random(),
      slt_cours_amenager_totale: "",
      nbr_surface_ca_total: "",
      slt_categorie_ca_total: "",
      nbr_prix_metre_carre_ca_total: "",
      nbr_coefficient_ca_total: "",
      nbr_valeur_ca_total: "",
    });
  } else {
    toast.error("Impossible d'ajouter plus de 3 occupants.");
  }
}
function removeBlockCA(i) {
  if (form.occupantsCA.length > 1) form.occupantsCA.pop();
}
function addBlockCL() {
  if (form.occupantsCL.length < maxOccupantsCL) {
    form.occupantsCL.push({
      id: Date.now() + Math.random(),
      slt_cours_amenager_clo: "",
      nbr_longueur_avant_clo: "",
      slt_categorie_clo: "",
      nbr_prix_metre_carre_clo: "",
      nbr_coefficient_clo: "",
      nbr_valeur_clo: "",
    });
  } else {
    toast.error("Impossible d'ajouter plus de 3 occupants.");
  }
}
function removeBlockCL(i) {
  if (form.occupantsCL.length > 1) form.occupantsCL.pop();
}
function addBlockAP() {
  if (form.occupantsAP.length < maxOccupantsAP) {
    form.occupantsAP.push({
      id: Date.now() + Math.random(),
      txt_designation_am: "",
      nbr_valeur_unitaire_am: "",
      nbr_quantile_am: "",
      slt_coeficien_am: "",
      nbr_valeur_am: "",
    });
  } else {
    toast.error("Impossible d'ajouter plus de 3 occupants.");
  }
}
function removeBlockAP(i) {
  if (form.occupantsAP.length > 1) form.occupantsAP.pop();
}

// Arrondi
function arrondirDeuxDecimales(valeur) {
  const num = parseFloat(valeur);
  if (isNaN(num)) return 0;
  return Math.round((num + Number.EPSILON) * 100) / 100;
}

// ================= CALCULS AUTOMATIQUES =================

// Montant loyer
const montantLoyerTotal = computed(() => {
  const totalExistants = (terrain.references_usages || []).reduce((acc, usage) => acc + (Number(usage.nbr_montantLoyerTG) || 0), 0);
  const totalOccupants = (form.occupants || []).reduce((acc, occupant) => acc + (Number(occupant.nbr_montantLoyerTG) || 0), 0);
  return totalExistants + totalOccupants;
});
watchEffect(() => {
  form.nbr_montantLoyerTotal = montantLoyerTotal.value;
});
const nbr_TVATotal = computed(() => {
  return (Number(form.nbr_montantLoyerTotal) || 0) * 0.18;
});
watchEffect(() => {
  form.nbr_TVATotal = nbr_TVATotal.value.toFixed(2);
});

// Surfaces
watchEffect(() => {
  const bati = Number(form.txt_superficie_bati_sol);
  const surfacebatiSolPR = Number(form.nbr_surface_bati_solPR);
  const surface = Number(form.nbr_surface);
  form.txt_superficie_non_bati = (surface - bati).toFixed(2);
  if (bati > surface) {
    toast.error("Surface bâtie dépasse la superficie terrain.");
    form.txt_superficie_bati_sol = surface;
  }
  if (surfacebatiSolPR > bati) {
    toast.error("Surface bâtiment Principal dépasse la Superficie Bati.");
    form.nbr_surface_bati_solPR = bati;
  }
});

// Prix secteur
const prixParSecteur = { 1: 3000, 2: 2000, 3: 1000 };
watchEffect(() => {
  const secteur = Number(form.slt_secteur);
  form.nbr_prix_metre_carre = prixParSecteur[secteur] || 0;
});
const nbr_valeur_terrain = computed(() => {
  const prix = parseFloat(form.nbr_prix_metre_carre) || 0;
  const bati = parseFloat(form.txt_superficie_bati_sol) || 0;
  const total = parseFloat(form.nbr_surface) || 0;
  return ((prix / 2) * bati + prix * (total - bati)).toFixed(2);
});
watchEffect(() => {
  form.nbr_valeur_terrain = nbr_valeur_terrain.value;
});

// Bâti principal
watch(
  () => [form.currentCat, form.slt_categoriePR],
  ([type, cat]) => {
    if (type && cat && categories[type]?.[cat]) {
      form.nbr_prix_metre_carrePR = categories[type][cat];
    } else {
      form.nbr_prix_metre_carrePR = null;
    }
  }
);
watchEffect(() => {
  const type = form.currentCat;
  form.occupantsBP.forEach((occupant) => {
    const cat = occupant.slt_categorieTG;
    occupant.nbr_prix_metre_carreTG = categories[type] && categories[type][cat] ? categories[type][cat] : null;
  });
});
watchEffect(() => {
  form.txt_dependant_domainePR = "Primaire";
});
watchEffect(() => {
  const surfaceBS = parseFloat(form.nbr_surface_bati_solPR) || 0;
  const nbrNiveau = parseFloat(form.nbr_niveauPR) || 0;
  form.nbr_surface_utilePR = (surfaceBS * nbrNiveau * 0.78).toFixed(2);
});
watchEffect(() => {
  const utile = parseFloat(form.nbr_surface_utilePR) || 0;
  const coeff = parseFloat(form.slt_coeffPR) || 0;
  form.nbr_surface_corrigerPR = (utile * coeff).toFixed(2);
});
watchEffect(() => {
  const prixmetrecarre = parseFloat(form.nbr_prix_metre_carrePR) || 0;
  const surfaceCorrige = parseFloat(form.nbr_surface_corrigerPR) || 0;
  form.nbr_valeurPR = (prixmetrecarre * surfaceCorrige).toFixed(2);
});

// Bâtiments secondaires
watchEffect(() => {
  form.occupantsBP.forEach((occupant) => {
    const surfaceBS = parseFloat(occupant.nbr_surface_bati_solTG) || 0;
    const nbrNiveau = parseFloat(occupant.nbr_niveauTG) || 0;
    occupant.nbr_surface_utileTG = (surfaceBS * nbrNiveau * 0.78).toFixed(2);
  });
});
watchEffect(() => {
  form.occupantsBP.forEach((occupant) => {
    const utile = parseFloat(occupant.nbr_surface_utileTG) || 0;
    const coeff = parseFloat(occupant.slt_coeffTG) || 0;
    occupant.nbr_surface_corrigerTG = (utile * coeff).toFixed(2);
  });
});
watchEffect(() => {
  form.occupantsBP.forEach((occupant) => {
    const prixmetrecarre = parseFloat(occupant.nbr_prix_metre_carreTG) || 0;
    const surfaceCorrige = parseFloat(occupant.nbr_surface_corrigerTG) || 0;
    occupant.nbr_valeurTG = (prixmetrecarre * surfaceCorrige).toFixed(2);
  });
});
const txt_valeur_terrain_bati = computed(() => {
  const valeurPR = parseFloat(form.nbr_valeurPR) || 0;
  return form.occupantsBP.reduce((total, occupant) => total + (parseFloat(occupant.nbr_valeurTG) || 0) + valeurPR, 0);
});
watchEffect(() => {
  form.txt_valeur_terrain_bati = txt_valeur_terrain_bati.value;
});

// Cours aménagées
const coeffCAValues = { 1: 17500, 2: 13500, 3: 10500, 4: 5000 };
watchEffect(() => {
  form.occupantsCA.forEach((occupant) => {
    const categorie = Number(occupant.slt_categorie_ca_total) || 0;
    const prixMetre = coeffCAValues[categorie] || 0;
    const surfaceCA = parseFloat(occupant.nbr_surface_ca_total) || 0;
    const coeffCA = parseFloat(occupant.nbr_coefficient_ca_total) || 0;
    occupant.nbr_prix_metre_carre_ca_total = prixMetre;
    occupant.nbr_valeur_ca_total = (surfaceCA * prixMetre * coeffCA).toFixed(2);
  });
});
const nbr_valeur_total_ca = computed(() => {
  return form.occupantsCA.reduce((total, occupant) => total + (parseFloat(occupant.nbr_valeur_ca_total) || 0), 0);
});
watchEffect(() => {
  form.nbr_valeur_total_ca = nbr_valeur_total_ca.value;
});

// Clôtures
const coeffCLValues = { 1: 48269, 2: 35105, 3: 28084, 4: 25744, 5: 11848, 6: 4209, 7: 853 };
watchEffect(() => {
  form.occupantsCL.forEach((occupant) => {
    const categorieCL = Number(occupant.slt_categorie_clo) || 0;
    const prixmetrecarreCL = coeffCLValues[categorieCL] || 0;
    const lineaire = parseFloat(occupant.nbr_longueur_avant_clo) || 0;
    const coeffCL = parseFloat(occupant.nbr_coefficient_clo) || 0;
    occupant.nbr_prix_metre_carre_clo = prixmetrecarreCL;
    occupant.nbr_valeur_clo = (lineaire * prixmetrecarreCL * coeffCL).toFixed(2);
  });
});
const nbr_valeur_total_clotur = computed(() => {
  return form.occupantsCL.reduce((total, occupant) => total + (parseFloat(occupant.nbr_valeur_clo) || 0), 0);
});
watchEffect(() => {
  form.nbr_valeur_total_clotur = nbr_valeur_total_clotur.value;
});

// Aménagements particuliers
watchEffect(() => {
  form.occupantsAP.forEach((occupant) => {
    const valeurUnitaire = parseFloat(occupant.nbr_valeur_unitaire_am) || 0;
    const quantite = parseFloat(occupant.nbr_quantile_am) || 0;
    const coeffCA = parseFloat(occupant.slt_coeficien_am) || 0;
    occupant.nbr_valeur_am = (valeurUnitaire * quantite * coeffCA).toFixed(2);
  });
});
const nbr_valeur_totale_ap = computed(() => {
  return form.occupantsAP.reduce((total, occupant) => total + (parseFloat(occupant.nbr_valeur_am) || 0), 0);
});
watchEffect(() => {
  form.nbr_valeur_totale_ap = nbr_valeur_totale_ap.value;
});

// Valeur vénale et locative
const nbr_valeurVenaleLimeuble = computed(() => {
  const terrain = parseFloat(txt_valeur_terrain_bati.value) || 0;
  const ca = parseFloat(nbr_valeur_total_ca.value) || 0;
  const clotur = parseFloat(nbr_valeur_total_clotur.value) || 0;
  const ap = parseFloat(nbr_valeur_totale_ap.value) || 0;
  return terrain + ca + clotur + ap;
});
watchEffect(() => {
  form.nbr_valeurVenaleLimeuble = nbr_valeurVenaleLimeuble.value;
});

const isPrincipalSelected = computed(() => form.txt_dependant_domainePR === "Principale");
const tauxValeurLocative = computed(() => {
  if (!isPrincipalSelected.value) return 0;
  const cat = Number(form.nbr_prix_metre_carrePR) || 0;
  if ([244257, 205891].includes(cat)) return 0.1344;
  if ([226543, 204069, 180937, 190883].includes(cat)) return 0.12;
  return 0.10;
});
const nbr_valeurLocative = computed(() => {
  if (!isPrincipalSelected.value) return 0;
  const valeurVenale = parseFloat(form.nbr_valeurVenaleLimeuble) || 0;
  const taux = tauxValeurLocative.value;
  return valeurVenale * taux;
});
watchEffect(() => {
  form.nbr_valeurLocative = nbr_valeurLocative.value;
});

// ================= CODE D'ACCÈS =================
const codeDaccees = async (code) => {
  if (!code) {
    toast.error("Veuillez entrer un code d'accès.");
    return;
  }
  isLoading.value = true;
  try {
    const { data } = await axios.post(route("code.verify"), { code });
    if (data.exists) {
      showModal.value = false;
      formVisible.value = true;
      toast.success("Modification autorisée !");
    } else {
      toast.error("Vérifie ton code d'accès !");
      formVisible.value = false;
    }
  } catch (err) {
    formVisible.value = false;
    if (err.response?.status === 422 && err.response.data.errors) {
      Object.values(err.response.data.errors).forEach((msg) => toast.error(msg));
    } else {
      toast.error("Une erreur est survenue lors de la vérification.");
    }
  } finally {
    isLoading.value = false;
  }
};

// ================= SOUMISSION =================
async function submit() {
    try {
        isLoading.value = true;

        // 1. Upload PDF titulaire
        if (form.fichierPDF instanceof File) {
            const pdfForm = new FormData();
            pdfForm.append("fichierPDF", form.fichierPDF);

            await axios.post(
                route("secretariat.uploadPDF", terrain.id),
                pdfForm,
                { headers: { "Content-Type": "multipart/form-data" } }
            );

            form.fichierPDF = null; // ✅ reset
        }

        // 2. Upload PDF mutation
        if (form.fichierPDFMutation instanceof File) {
            const pdfFormMutation = new FormData();
            pdfFormMutation.append("fichierPDFMutation", form.fichierPDFMutation);

            await axios.post(
                route("secretariat.uploadPDFMutation", terrain.id),
                pdfFormMutation,
                { headers: { "Content-Type": "multipart/form-data" } }
            );

            form.fichierPDFMutation = null; // ✅ reset
        }

        // 3. Vérification titulaire
        if (!form.slt_titulaire) {
            console.warn("⚠️ slt_titulaire est vide, le backend utilisera la valeur existante.");
        }

        // 4. Nettoyage des ids temporaires
        const cleanArray = (arr) => (arr || []).map(({ id, ...rest }) => rest);
        form.occupants   = cleanArray(form.occupants);
        form.occupantsBP = cleanArray(form.occupantsBP);
        form.occupantsCA = cleanArray(form.occupantsCA);
        form.occupantsCL = cleanArray(form.occupantsCL);
        form.occupantsAP = cleanArray(form.occupantsAP);

        // 5. Debug
        console.log("=== PAYLOAD ENVOYÉ ===");
        console.log("slt_titulaire:", JSON.stringify(form.slt_titulaire));
        console.log("txt_nom:", JSON.stringify(form.txt_nom));
        console.log("slt_titulaireMutation:", JSON.stringify(form.slt_titulaireMutation));
        console.log("TOUT:", form.data());

        // 6. Mise à jour du terrain
        await form.put(route("secretariat.update", terrain.id), { // ✅ await ajouté
            preserveScroll: true,

            onSuccess: (page) => {
                console.log("✅ Terrain mis à jour avec succès.", page);
                toast.success(
                    `Dossier ${form.txt_num_dossier} a été mis à jour avec succès.`
                );
            },

            onError: (errors) => {
                console.error("❌ Erreurs de validation :", errors);
                Object.entries(errors).forEach(([field, message]) => {
                    console.error(`${field} : ${message}`);
                    toast.error(`${field} : ${message}`);
                });
            },

            onFinish: () => {
                isLoading.value = false;
            },
        });

    } catch (error) {
        console.error("❌ Erreur submit :", error);
        toast.error("❌ Une erreur est survenue lors de la modification.");
        isLoading.value = false;
    }
}

</script>

<template>
  <Head title="Modifier Terrain">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
  </Head>

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">Modification du Terrain</h2>
    </template>

    <div class="py-12">
      <div class="w-full max-w-8xl">
        <AppCodeAccee v-model="showModal" :loading="isLoading" @confirm="codeDaccees" />
      </div>

      <div class="flex justify-center">
        <div class="w-full max-w-8xl">
          <div v-if="formVisible" class="w-full">
            <div class="bg-white shadow-md rounded-xl">
              <div class="p-4 border-b bg-primary rounded-t-xl">
                <h1 class="text-2xl text-white font-semibold">Pages de Modification !</h1>
              </div>
              <div class="p-6">
                <template v-if="terrain">
                  <div class="flex justify-center">
                    <h1 class="text-lg text-primary-txt font-bold">
                      Modifier la parcelle de numéro dossier : {{ terrain.dossier.txt_num_dossier }}
                    </h1>
                  </div>

                  <form @submit.prevent="submit" class="space-y-4"> 
                    <div class="p-6">
                      <div class="sm:col-span-12">
                        <!-- Reference Dossier -->
                        <h5 class="text-lg text-primary-txt font-bold">Reference Dossier</h5><br />
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <div class="sm:col-span-2">
                            <BaseInput v-model="form.txt_num_dossier" id="txt_num_dossier" label="Numéro de dossier" :error="form.errors.txt_num_dossier" />
                          </div>
                          <BaseSelect v-model="form.slt_service_rendu" id="slt_service_rendu" label="Service Rendu" :error="form.errors.slt_service_rendu" :options="serviceRenduOptions" />
                          <BaseInput v-model="form.txt_etat_cession" id="txt_etat_cession" label="Etat Cession" :error="form.errors.txt_etat_cession" />
                          <BaseInput v-model="form.txt_cession_definitive" id="txt_cession_definitive" label="Cession Definitive" :error="form.errors.txt_cession_definitive" />
                          <BaseInput v-model="form.dt_date_creation" id="dt_date_creation" type="date" label="Date état de cession" :error="form.errors.dt_date_creation" />
                        </div>
                        <br />

                        <!-- Informations terrain -->
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <BaseSelect v-model="form.slt_region" id="slt_region" label="Région" :error="form.errors.slt_region" :options="regions.map(r => ({ value: r.id, label: r.slt_region }))" />
                          <BaseSelect v-model="form.slt_departement" id="slt_departement" label="Département" :error="form.errors.slt_departement" :options="departements.map(d => ({ value: d.id, label: d.slt_departement }))" />
                          <BaseSelect v-model="form.slt_arrondissement" id="slt_arrondissement" label="Arrondissement" :error="form.errors.slt_arrondissement" :options="arrondissements.map(a => ({ value: a.id, label: a.slt_arrondissement }))" />
                          <BaseSelect v-model="form.slt_commune" id="slt_commune" label="Commune" :error="form.errors.slt_commune" :options="communes.map(c => ({ value: c.id, label: c.slt_commune }))" /> 
                          <BaseInput v-model="form.txt_lotissement" id="txt_lotissement" label="Lotissement" :error="form.errors.txt_lotissement" /> 
                          <BaseInput v-model="form.txt_HorsLotissement" id="txt_HorsLotissement" label="Hors Lotissement" :error="form.errors.txt_HorsLotissement" />
                          <BaseInput v-model="form.txt_num_lotissement" id="txt_num_lotissement" label="N° Lot" :error="form.errors.txt_num_lotissement" />
                          <BaseInput v-model="form.nbr_surface" id="nbr_surface" label="Surface" :error="form.errors.nbr_surface" />
                          <BaseSelect v-model="form.slt_document_admin" id="slt_document_admin" label="Document admin" :error="form.errors.slt_document_admin" :options="documentAdminOptions" />
                          <BaseInput v-model="form.txt_num_deliberation" id="txt_num_deliberation" label="N° Déliberation" :error="form.errors.txt_num_deliberation" />
                          <BaseInput v-model="form.dt_date_deliberation" id="dt_date_deliberation" type="date" label="Date Déliberation" :error="form.errors.dt_date_deliberation" />
                          <BaseInput v-model="form.txt_num_section" id="txt_num_section" label="N° section" :error="errorMessageNumSection || form.errors.txt_num_section" maxlength="3" minlength="3" @input="validateInputNumSection" />
                          <BaseInput v-model="form.txt_num_parcelle" id="txt_num_parcelle" label="N° Parcelle" :error="errorMessageNumParcelle || form.errors.txt_num_parcelle" maxlength="5" minlength="5" @input="validateInputNumParcelle" />
                          <BaseInput v-model="form.txt_nicad" id="txt_nicad" name="txt_nicad" label="NICAD" readonly input-class="text-primary-txt font-bold italic" :error="form.errors.txt_nicad" />
                          <BaseSelect v-model="form.slt_statut_nicad" id="slt_statut_nicad" label="Statut NICAD" :error="form.errors.slt_statut_nicad" :options="statutNicadOptions" @change="handleSelectChangeAnnulationNICAD"/>
                          <BaseSelect v-if="showAnnulationNICAD" v-model="form.slt_motif_annulation_nicad" name="slt_motif_annulation_nicad" id="slt_motif_annulation_nicad" label="Motif Annulation NICAD" :error="form.errors.slt_motif_annulation_nicad" :options="annulationNICADOptions" @change="handleSelectChangeMotifAnnulationNICAD" />
                          <BaseSelect v-model="form.slt_signalement" id="slt_signalement" label="Signalement" :error="form.errors.slt_signalement" :options="signalementOptions" @change="handleSelectChangeDesignationSignalement" />
                          <BaseSelect v-if="showDesignationSignalement" v-model="form.slt_designationSignalement" id="slt_designationSignalement" label="Designation Signalement" :error="form.errors.slt_designationSignalement" 
                            :options="(designationsSignalement || []).map(designation => ({
                              value: designation,
                              label: designation
                            }))" 
                          />
                        </div>
                        <br/>

                        <!-- Reference Cadastrale -->
                        <h5 class="text-lg text-primary-txt font-bold">Reference Cadastrale</h5><br />
                        <div class="maz-py-4">
                          <MazRadio v-model="form.rd_immatriculation_terrain" class="text-primary-txt" value="Terrain Non Immatriculé" label="Terrain Non Immatriculé" />
                          <MazRadio v-model="form.rd_immatriculation_terrain" class="text-primary-txt" value="Terrain Immatriculé" label="Terrain Immatriculé" />
                          <div v-if="form.errors.rd_immatriculation_terrain" class="text-red-500">{{ form.errors.rd_immatriculation_terrain }}</div>
                        </div>

                        <!-- Non immatriculé -->
                        <div v-if="!isImmatricule" class="maz-py-4">
                          <br />
                          <div class="sm:col-span-12">
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                              <div class="sm:col-span-2">
                                <BaseSelect v-model="form.slt_dependant_domaine" id="slt_dependant_domaine" label="Dépendant du domaine" :options="dependantDomaineOptions" />
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Immatriculé -->
                        <div v-if="isImmatricule" class="maz-py-4">
                          <br />
                          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div class="sm:col-span-2">
                              <BaseSelect v-model="form.ussu_bornage" id="ussu_bornage" label="Ussu de bornage" :options="ussuBornageOptions" @change="handleSelectChange" />
                            </div>
                            <BaseInput v-model="form.txt_titre_mere" id="Titre_mere" label="Titre Mere" />
                            <BaseInput v-model="form.txt_num_titre" id="numTitre" label="N° Titre" />
                            <BaseSelect v-model="form.slt_lf" id="slt_lf" label="LF" :options="lfOptions" />
                            <BaseInput v-model="form.txt_num_requisition" id="Num_requisition" label="N° Requisition" />
                            <BaseInput v-model="form.txt_surface_bornage" id="Surface_bornage" label="Surface au bornage" />
                            <BaseInput v-model="form.dt_date_bornage" id="Date_bornage" type="date" label="Date Bornage" autocomplete="address-level2" />
                            <BaseInput v-if="showMorcellement" v-model="form.txt_appartement" id="txt_appartement" label="Appartement" :error="errorMessage" maxlength="3" minlength="3" @input="validateInputApartement" />
                            <BaseInput v-model="form.txt_nom_geometre" id="Nom_geometre" label="Nom Géomètre" autocomplete="address-level2" />
                          </div>
                        </div>
                        <br/>

                        <!-- Identité Titulaire -->
                        <h5 class="text-lg font-bold">Identité Titulaire de droit</h5><br />
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <div class="sm:col-span-2">
                            <BaseSelect v-model="form.slt_titulaire" id="slt_titulaire" label="Titulaire" :error="form.errors.slt_titulaire" :options="titulaireOptions" />
                          </div>

                          <!-- Personne Physique -->
                          <template v-if="showSectionPP">
                            <BaseInput v-model="form.txt_nationalite" id="txt_nationalite" label="Nationalite" :error="form.errors.txt_nationalite" />
                            <BaseSelect v-model="form.slt_civilite" id="slt_civilite" label="Civilite" :error="form.errors.slt_civilite" :options="civiliteOptions" />
                            <BaseInput v-model="form.txt_prenom" id="txt_prenom" label="Prénom" :error="form.errors.txt_prenom" />
                            <BaseInput v-model="form.txt_nom" id="txt_nom" label="Nom" :error="form.errors.txt_nom" />
                            <BaseSelect v-model="form.slt_piece" id="slt_piece" label="Pièce" :error="form.errors.slt_piece" :options="pieceOptions" />
                            <BaseInput v-model="form.txt_numPiece" id="txt_numPiece" label="N° Pièce" :error="form.errors.txt_numPiece" />
                            <BaseInput v-model="form.dt_date_delivrance" id="dt_date_delivrance" type="date" label="Date Délivrance" :error="form.errors.dt_date_delivrance" />
                            <BaseInput v-model="form.dt_date_naissance" id="dt_date_naissance" type="date" label="Date Naissance" :error="form.errors.dt_date_naissance" />
                            <BaseInput v-model="form.txt_lieu_naissance" id="txt_lieu_naissance" label="Lieu Naissance" :error="form.errors.txt_lieu_naissance" />
                            <BaseInput v-model="form.txt_adresse" id="txt_adresse" label="Adresse" :error="form.errors.txt_adresse" />
                            <BaseInput v-model="form.tel_telephone" id="tel_telephone" label="Telephone" :error="form.errors.tel_telephone" />
                            <BaseInput v-model="form.eml_email" id="eml_email" label="Email" :error="form.errors.eml_email" />
                            <BaseInput v-model="form.txt_representant" id="txt_representant" label="Representant" :error="form.errors.txt_representant" />
                            <BaseInput v-model="form.tel_telRepresentant" id="tel_telRepresentant" label="Tel Representant" :error="form.errors.tel_telRepresentant" />
                          </template>

                          <!-- Personne Morale -->
                          <template v-if="showSectionPM">
                            <BaseSelect v-model="form.slt_categoriePM" id="slt_categoriePM" label="Catégorie" :options="categoriePMOptions" />
                            <BaseInput v-model="form.txt_formJuridiquePM" id="txt_formJuridiquePM" label="Forme Juridique" />
                            <BaseInput v-model="form.txt_denominationPM" id="txt_denominationPM" label="Dénomination" />
                            <BaseInput v-model="form.txt_nineaPM" id="txt_nineaPM" label="Ninea" />
                            <BaseInput v-model="form.txt_perssonneRepresentantPM" id="txt_perssonneRepresentantPM" label="Nom Représentant" />
                            <BaseInput v-model="form.txt_fonctionPM" id="txt_fonctionPM" label="Fonction" />
                            <BaseInput v-model="form.txt_telephonePM" id="txt_telephonePM" label="Téléphone" />
                            <BaseInput v-model="form.txt_emailPM" id="txt_emailPM" type="email" label="Email" />
                            <BaseInput v-model="form.txt_adressePM" id="txt_adressePM" label="Adresse Siège" />
                          </template>

                          <!-- État -->
                          <template v-if="showSectionPA">
                            <BaseSelect v-model="form.slt_etablissementPA" id="slt_etablissementPA" label="Etablissement" :options="etablissementPAOptions" />
                            <BaseInput v-model="form.txt_personneResponsablePA" id="txt_personneResponsablePA" label="Personne Responsable" />
                            <BaseInput v-model="form.txt_fonctionResponsablePA" id="txt_fonctionResponsablePA" label="Fonction" />
                            <BaseInput v-model="form.txt_telephonePA" id="txt_telephonePA" label="Téléphone" />
                            <BaseInput v-model="form.txt_emailPA" id="txt_emailPA" type="email" label="Email" />
                            <BaseInput v-model="form.txt_ministereTutelePA" id="txt_ministereTutelePA" label="Ministère Tutelle" />
                          </template>
                        </div>
                        <br/> 
                         
                        <div class="flex gap-2 items-center">
                          <h5 class="text-lg font-bold">Identité Mutation</h5>
                          <MazBtn
                            size="large"
                            @click="showAllSectionsMutation = !showAllSectionsMutation"
                            :title="showAllSectionsMutation ? 'Masquer les sections supplémentaires' : 'Afficher toutes les sections'"
                            :aria-label="showAllSectionsMutation ? 'Masquer les sections supplémentaires' : 'Afficher toutes les sections'"
                            class="h-8 w-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                  hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300
                                  dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg
                                  dark:shadow-blue-800/80 font-medium rounded-lg text-sm p-0 flex items-center justify-center
                                  transition-transform duration-300"
                            :class="{ 'rotate-45': showAllSectionsMutation }"
                          >
                            +
                          </MazBtn>
                        </div>

                        <div v-if="showAllSectionsMutation" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <div class="sm:col-span-2">
                            <BaseSelect v-model="form.slt_titulaireMutation" id="slt_titulaireMutation" label="Titulaire" 
                              :error="form.errors.slt_titulaireMutation" :options="titulaireOptionsMutation"
                            /> 
                          </div> 

                          <!-- ✅ Personne Physique : affichée si sélectionnée OU si le bouton est activé -->
                          <template v-if="showSectionPPMutation">
                            <BaseInput v-model="form.txt_nationaliteMutation" id="txt_nationaliteMutation" label="Nationalite" :error="form.errors.txt_nationaliteMutation" />
                            <BaseSelect v-model="form.slt_civiliteMutation" id="slt_civiliteMutation" label="Civilite" :error="form.errors.slt_civiliteMutation" :options="civiliteOptionsMutation" />
                            <BaseInput v-model="form.txt_prenomMutation" id="txt_prenomMutation" label="Prénom" :error="form.errors.txt_prenomMutation" />
                            <BaseInput v-model="form.txt_nomMutation" id="txt_nomMutation" label="Nom" :error="form.errors.txt_nomMutation" />
                            <BaseSelect v-model="form.slt_pieceMutation" id="slt_pieceMutation" label="Pièce" :error="form.errors.slt_pieceMutation" :options="pieceOptionsMutation" />
                            <BaseInput v-model="form.txt_numPieceMutation" id="txt_numPieceMutation" label="N° Pièce" :error="form.errors.txt_numPieceMutation" />
                            <BaseInput v-model="form.dt_date_delivranceMutation" id="dt_date_delivranceMutation" type="date" label="Date Délivrance" :error="form.errors.dt_date_delivranceMutation" />
                            <BaseInput v-model="form.dt_date_naissanceMutation" id="dt_date_naissanceMutation" type="date" label="Date Naissance" :error="form.errors.dt_date_naissanceMutation" />
                            <BaseInput v-model="form.txt_lieu_naissanceMutation" id="txt_lieu_naissanceMutation" label="Lieu Naissance" :error="form.errors.txt_lieu_naissanceMutation" />
                            <BaseInput v-model="form.txt_adresseMutation" id="txt_adresseMutation" label="Adresse" :error="form.errors.txt_adresseMutation" />
                            <BaseInput v-model="form.tel_telephoneMutation" id="tel_telephoneMutation" label="Telephone" :error="form.errors.tel_telephoneMutation" />
                            <BaseInput v-model="form.eml_emailMutation" id="eml_emailMutation" label="Email" :error="form.errors.eml_emailMutation" />
                            <BaseInput v-model="form.txt_representantMutation" id="txt_representantMutation" label="Representant" :error="form.errors.txt_representantMutation" />
                            <BaseInput v-model="form.tel_telRepresentantMutation" id="tel_telRepresentantMutation" label="Tel Representant" :error="form.errors.tel_telRepresentantMutation" />
                          </template>

                          <!-- ✅ Personne Morale -->
                          <template v-if="showSectionPMMutation">
                            <BaseSelect v-model="form.slt_categoriePM_Mutation" id="slt_categoriePM" label="Catégorie" :options="categoriePMOptionsMutation" />
                            <BaseInput v-model="form.txt_formJuridiquePM_Mutation" id="txt_formJuridiquePM" label="Forme Juridique" :error="form.errors.txt_formJuridiquePM_Mutation" />
                            <BaseInput v-model="form.txt_denominationPM_Mutation" id="txt_denominationPM" label="Dénomination" :error="form.errors.txt_denominationPM_Mutation" />
                            <BaseInput v-model="form.txt_nineaPM_Mutation" id="txt_nineaPM" label="Ninea" :error="form.errors.txt_nineaPM_Mutation" />
                            <BaseInput v-model="form.txt_perssonneRepresentantPM_Mutation" id="txt_perssonneRepresentantPM" label="Nom Représentant" :error="form.errors.txt_perssonneRepresentantPM_Mutation" />
                            <BaseInput v-model="form.txt_fonctionPM_Mutation" id="txt_fonctionPM" label="Fonction" :error="form.errors.txt_fonctionPM_Mutation" />
                            <BaseInput v-model="form.txt_telephonePM_Mutation" id="txt_telephonePM" label="Téléphone" :error="form.errors.txt_telephonePM_Mutation" />
                            <BaseInput v-model="form.txt_emailPM_Mutation" id="txt_emailPM" type="email" label="Email" :error="form.errors.txt_emailPM_Mutation" />
                            <BaseInput v-model="form.txt_adressePM_Mutation" id="txt_adressePM" label="Adresse Siège" :error="form.errors.txt_adressePM_Mutation" />
                          </template>

                          <!-- ✅ État -->
                          <template v-if="showSectionPAMutation">
                            <BaseSelect v-model="form.slt_etablissementPA_Mutation" id="slt_etablissementPA" label="Etablissement" :options="etablissementPAOptionsMutation" />
                            <BaseInput v-model="form.txt_personneResponsablePA_Mutation" id="txt_personneResponsablePA" label="Personne Responsable" :error="form.errors.txt_personneResponsablePA_Mutation" />
                            <BaseInput v-model="form.txt_fonctionResponsablePA_Mutation" id="txt_fonctionResponsablePA" label="Fonction" :error="form.errors.txt_fonctionResponsablePA_Mutation" />
                            <BaseInput v-model="form.txt_telephonePA_Mutation" id="txt_telephonePA" label="Téléphone" :error="form.errors.txt_telephonePA_Mutation" />
                            <BaseInput v-model="form.txt_emailPA_Mutation" id="txt_emailPA" type="email" label="Email" :error="form.errors.txt_emailPA_Mutation" />
                            <BaseInput v-model="form.txt_ministereTutelePA_Mutation" id="txt_ministereTutelePA" label="Ministère Tutelle" :error="form.errors.txt_ministereTutelePA_Mutation" />
                          </template> 

                          <!-- Acte et autres champs -->
                          <template v-if="showAllSectionsMutation">
                            <BaseSelect v-model="form.txt_typeActeMutation" label="Actes" name="txt_typeActeMutation" :options="acteMutationOptions"/>
                            <BaseInput v-model="form.txt_nomNotaireMutation" v-if="showNomNotaire" label="Nom Notaire" name="txt_nomNotaireMutation" type="text"/>
                            <BaseSelect v-model="form.txt_natureMutation" label="Nature"name="txt_natureMutation" :options="natureMutationOptions"/>
                            <BaseInput v-model="form.dt_dateMutation" label="Date Acte" name="dt_dateMutation" type="date" />
                            <BaseInput v-model="form.txt_jugementMutation" label="Jugement" name="txt_jugementMutation" type="text" />
                            <BaseInput v-model="form.txt_observationMutation" label="Observation" name="txt_observationMutation" type="text" />
                            <BaseInput v-model="form.fichierPDFMutation" label="Fichier PDF" name="fichierPDFMutation" type="file" @change="handleFileChangePDFMutation" />
                          </template>
                        </div> 

                        <!-- Références usage -->
                        <h5 class="text-lg font-bold mt-8">Réferences Usage</h5><br />
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <BaseSelect v-model="form.slt_usage" id="slt_usage" label="Usage" :error="form.errors?.slt_usage" :options="usageOptions" />
                          <BaseSelect v-model="form.slt_residence" id="slt_residence" label="Type de résidence" :error="form.errors?.slt_residence" :options="residenceOptions" />
                        </div> 

                        <!-- Occupants -->
                        <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                          <TransitionGroup name="slide-fade" tag="div">
                            <div v-for="(occupant, i) in form.occupants" :key="occupant.id || i" class="sm:col-span-8">
                              <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_nomOccupantTG" :label="`Nom Occupant ${i+1}`" class="w-30" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_numAppartementTG" :id="`NunAppartement_${i}`" label="N° Appart" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_activiteTG" :id="`Activite_${i}`" label="Activite" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_nineaTG" :id="`Ninea_${i}`" label="Ninea" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.tel_telephoneTG" :id="`Telephone_${i}`" type="tel" label="Téléphone" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.nbr_montantLoyerTG" :id="`Montant_loyer_${i}`" type="number" label="Mnt Loyer" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_dateLieuNaissanceTG" :id="`DateNaissnace_${i}`" label="Date-lieu Nais" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.txt_cniPasseportTG" :id="`CNIPasseport_${i}`" label="CNI/Pasprt" class="w-28" />
                                </div>
                                <div class="sm:col-span-1">
                                  <BaseInput v-model="occupant.dt_dateDelivranceTG" :id="`DateDelivrance_${i}`" type="date" label="Date Déliv" class="w-28" />
                                </div>
                              </div>
                            </div><br>
                          </TransitionGroup>
                          <div class="flex gap-4 mt-6">
                            <button @click="addBlock" type="button" class="h-8 text-white bg-primary hover:bg-primary-dark font-medium rounded-lg text-sm px-5 py-2.5">Ajouter</button>
                            <button @click="removeBlock" type="button" class="h-8 text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2.5">Retirer</button>
                          </div>
                        </div> 

                        <!-- Montants -->
                        <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                          <div class="sm:col-span-2">
                            <BaseInput v-model="form.nbr_montantLoyerTotal" id="MontantLoyerTotal" label="Montant Loyer Total" readonly class="w-64" />
                          </div>
                          <div class="sm:col-span-2">
                            <BaseInput :model-value="form.nbr_TVATotal" id="TVATotal" label="TVA Totale" readonly class="w-64" />
                          </div>
                        </div>

                        <!-- Evaluation de l'immeuble -->
                        <h5 class="text-lg font-bold mt-8">Evaluation de l'immeuble</h5><br />
                        <div class="mb-6">
                          <div class="border-b">
                            <ul class="flex justify-center text-sm font-medium text-primary-txt divide-x divide-gray-200 dark:divide-gray-700">
                              <li class="w-1/5 text-center">
                                <button type="button" @click="setActiveTab('terrain')"
                                  :class="{ 'text-primary border-b-8 border border-primary': activeTab === 'terrain', 'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'terrain' }"
                                  class="inline-block w-full p-4">Terrain</button>
                              </li>
                              <li class="w-1/5 text-center">
                                <button type="button" @click="setActiveTab('bati')"
                                  :class="{ 'text-primary border-b-8 border border-primary': activeTab === 'bati', 'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'bati' }"
                                  class="inline-block w-full p-4">Bâti</button>
                              </li>
                              <li class="w-1/5 text-center">
                                <button type="button" @click="setActiveTab('Cours_amenagees')"
                                  :class="{ 'text-primary border-b-8 border border-primary': activeTab === 'Cours_amenagees', 'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'Cours_amenagees' }"
                                  class="inline-block w-full p-4">Cours aménagées</button>
                              </li>
                              <li class="w-1/5 text-center">
                                <button type="button" @click="setActiveTab('cloture')"
                                  :class="{ 'text-primary border-b-8 border border-primary': activeTab === 'cloture', 'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'cloture' }"
                                  class="inline-block w-full p-4">Clôtures</button>
                              </li>
                              <li class="w-1/5 text-center">
                                <button type="button" @click="setActiveTab('amenagement')"
                                  :class="{ 'text-primary border-b-8 border border-primary': activeTab === 'amenagement', 'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'amenagement' }"
                                  class="inline-block w-full p-4">Aménagements particuliers</button>
                              </li>
                            </ul>
                          </div>

                          <!-- Onglet Terrain -->
                          <div class="p-6">
                            <div v-show="activeTab === 'terrain'" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                              <div class="sm:col-span-2">
                                <BaseInput v-model="form.txt_superficie_totale" id="txt_superficie_totale" type="number" label="Superficie Totale Terrain" readonly />
                              </div>
                              <div class="sm:col-span-2">
                                <BaseInput v-model="form.txt_superficie_bati_sol" id="Superficie_bati_sol" type="number" label="Superficie Bâti au sol" min="0" :max="form.nbr_surface" />
                              </div>
                              <div class="sm:col-span-2">
                                <BaseInput v-model="form.txt_superficie_non_bati" id="Superficie_non_bati" type="number" label="Superficie Non Bâti" readonly />
                              </div>
                              <div class="sm:col-span-2">
                                <BaseSelect v-model="form.slt_secteur" id="slt_secteur" label="Secteur" :options="secteurOptions" />
                              </div>
                              <div class="sm:col-span-2">
                                <BaseInput v-model="form.nbr_prix_metre_carre" id="nbr_prix_metre_carre" type="number" label="Prix mètre Carré" readonly />
                              </div>
                              <div class="sm:col-span-2">
                                <BaseInput v-model="form.nbr_valeur_terrain" type="number" step="0.01" label="Valeur du Terrain" readonly />
                              </div>
                            </div>

                            <!-- Onglet Bâti -->
                            <div v-show="activeTab === 'bati'" class="space-y-4">
                              <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8 items-center">
                                <div class="sm:col-span-2">
                                  <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                    <MazRadio v-model="form.currentCat" class="block font-medium text-primary-txt" value="Maison individuelle" label="Maison individuelle" />
                                  </div>
                                </div>
                                <div class="sm:col-span-2">
                                  <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                    <MazRadio v-model="form.currentCat" class="block font-medium text-primary-txt" value="Immeuble collectif" label="Immeuble collectif" />
                                  </div>
                                </div>
                              </div>
                              <br />
                              <h6 class="text-lg text-primary-txt text-center font-bold">Bâtiment(s) Principal(aux)</h6>
                              <div class="w-full flex flex-wrap justify-between gap-4 mt-8">
                                <TransitionGroup>
                                  <div class="sm:col-span-8">
                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.txt_dependant_domainePR" label="Bâtiment" readonly class="w-36" />
                                      </div>
                                      <div class="sm:col-span-2">
                                        <BaseSelect
                                          v-if="form.currentCat === 'Maison individuelle'"
                                          v-model="form.slt_categoriePR" id="slt_categoriePR" label="CAT"
                                          :options="categorieMaisonOptions" class="w-20" />
                                        <BaseSelect
                                          v-else-if="form.currentCat === 'Immeuble collectif'"
                                          v-model="form.slt_categoriePR" id="slt_categoriePR" label="CAT"
                                          :options="categorieImmeubleOptions" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_prix_metre_carrePR" label="Prix m²" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_surface_bati_solPR" label="Surf B S" type="number" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_niveauPR" label="Niveau" type="number" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_surface_utilePR" label="Surf Utile" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="form.slt_coeffPR" id="slt_coeffPR" label="Coeff" :options="coefficientOptions" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_surface_corrigerPR" label="S.Corrigee" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="form.nbr_valeurPR" label="Valeur" type="number" step="0.01" readonly class="w-20" />
                                      </div>
                                    </div>
                                  </div>
                                </TransitionGroup>
                              </div>

                              <!-- Bâtiments secondaires -->
                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                <TransitionGroup name="slide-fade" tag="div">
                                  <div v-for="(occupant, i) in form.occupantsBP" :key="occupant.id || i" class="sm:col-span-8">
                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_dependant_domaineTG" id="slt_dependant_domaineTG" label="Bâtiment" :options="batimentSecondaireOptions" class="w-36" />
                                      </div>
                                      <div class="sm:col-span-2">
                                        <BaseSelect
                                          v-if="form.currentCat === 'Maison individuelle'"
                                          v-model="occupant.slt_categorieTG" id="slt_categorieTG" label="CAT"
                                          :options="categorieMaisonOptions" class="w-20" />
                                        <BaseSelect
                                          v-else-if="form.currentCat === 'Immeuble collectif'"
                                          v-model="occupant.slt_categorieTG" id="slt_categorieTG" label="CAT"
                                          :options="categorieImmeubleOptions" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_prix_metre_carreTG" label="Prix m²" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_surface_bati_solTG" label="Surf B S" type="number" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_niveauTG" label="Niveau" type="number" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_surface_utileTG" label="Surf Utile" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_coeffTG" id="slt_coeffTG" label="Coeff" :options="coefficientOptions" class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_surface_corrigerTG" label="S.Corrigee" type="number" readonly class="w-20" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_valeurTG" label="Valeur" type="number" step="0.01" readonly class="w-20"
                                          @blur="() => arrondirDeuxDecimales(occupant.nbr_valeurTG)" />
                                      </div>
                                    </div>
                                  </div>
                                </TransitionGroup>
                                <div class="flex mt-6 gap-2">
                                  <button @click="addBlockBP" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Ajouter</button>
                                  <button @click="removeBlockBP" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Retirer</button>
                                </div>
                              </div>
                              <div class="flex justify-center gap-6 mb-8">
                                <div>
                                  <BaseInput :model-value="txt_valeur_terrain_bati" id="Valeur_terrain_bati" type="number" label="Valeur(s) bâtiment (s)" readonly />
                                </div>
                              </div>
                            </div>

                            <!-- Onglet Cours aménagées -->
                            <div v-show="activeTab === 'Cours_amenagees'" class="space-y-4">
                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                <TransitionGroup name="slide-fade" tag="div">
                                  <div v-for="(occupant, i) in form.occupantsCA" :key="occupant.id || i" class="sm:col-span-8">
                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_cours_amenager_totale" id="slt_cours_amenager_totale" label="Cours Aménagées Totale" :options="coursAmenageesOptions" class="w-34" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_surface_ca_total" id="nbr_surface_ca_total" label="Surface" type="number" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_categorie_ca_total" id="slt_categorie_ca_total" label="Catégorie" :options="categorieCAOptions" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_prix_metre_carre_ca_total" id="nbr_prix_metre_carre_ca_total" label="P/m²" type="number" readonly class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.nbr_coefficient_ca_total" id="nbr_coefficient_ca_total" label="Coefficient" :options="coefficientOptions" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_valeur_ca_total" id="Valeur_ca_total" label="Valeur" type="number" step="0.01" readonly class="w-28" />
                                      </div>
                                    </div>
                                  </div>
                                </TransitionGroup>
                                <div class="flex gap-4 mt-6">
                                  <button @click="addBlockCA" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Ajouter</button>
                                  <button @click="removeBlockCA" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Retirer</button>
                                </div>
                              </div>
                              <div class="flex justify-center gap-6 mb-8">
                                <div>
                                  <BaseInput v-model="form.nbr_valeur_total_ca" id="nbr_valeur_total_ca" label="Valeur Total des Cours" type="number" readonly />
                                </div>
                              </div>
                            </div>

                            <!-- Onglet Clôtures -->
                            <div v-show="activeTab === 'cloture'" class="space-y-4">
                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                <TransitionGroup name="slide-fade" tag="div">
                                  <div v-for="(occupant, i) in form.occupantsCL" :key="occupant.id || i" class="sm:col-span-8">
                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_cours_amenager_clo" id="slt_cours_amenager_clo" label="Clôtures" :options="clotureOptions" class="w-34" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_longueur_avant_clo" id="nbr_longueur_avant_clo" label="Linéaire" type="number" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_categorie_clo" id="slt_categorie_clo" label="Catégorie" :options="categorieCLOptions" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_prix_metre_carre_clo" id="nbr_prix_metre_carre_clo" label="P/mL" type="number" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.nbr_coefficient_clo" id="nbr_coefficient_clo" label="Coefficient" :options="coefficientOptions" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_valeur_clo" id="nbr_valeur_clo" label="Valeur" type="number" step="0.01" class="w-28" />
                                      </div>
                                    </div>
                                  </div>
                                </TransitionGroup>
                                <div class="flex gap-4 mt-6">
                                  <button @click="addBlockCL" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Ajouter</button>
                                  <button @click="removeBlockCL" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Retirer</button>
                                </div>
                              </div>
                              <div class="flex justify-center gap-6 mb-8">
                                <div>
                                  <BaseInput v-model="form.nbr_valeur_total_clotur" id="Valeur_total_clotur" label="Valeurs Totale des Clôtures" type="number" step="0.01" readonly />
                                </div>
                              </div>
                            </div>

                            <!-- Onglet Aménagements particuliers -->
                            <div v-show="activeTab === 'amenagement'" class="space-y-4">
                              <h6 class="text-lg text-center text-primary-txt font-bold">VALEUR</h6>
                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                <TransitionGroup name="slide-fade" tag="div">
                                  <div v-for="(occupant, i) in form.occupantsAP" :key="occupant.id || i" class="sm:col-span-8">
                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.txt_designation_am" id="Designation_1" label="Designation" class="w-34" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_valeur_unitaire_am" id="Valeur_unitaire_1" label="Valeur Unitaire" type="number" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_quantile_am" id="Quantile_1" label="Quantité" type="number" class="w-34" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseSelect v-model="occupant.slt_coeficien_am" id="Coeficien_1" label="Coefficient" :options="coefficientAPOptions" class="w-28" />
                                      </div>
                                      <div class="sm:col-span-1">
                                        <BaseInput v-model="occupant.nbr_valeur_am" id="Valeur" label="Valeur" type="number" inputmode="decimal" step="0.01" class="w-34" />
                                      </div>
                                    </div>
                                  </div>
                                </TransitionGroup>
                                <div class="flex gap-4 mt-6">
                                  <button @click="addBlockAP" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Ajouter</button>
                                  <button @click="removeBlockAP" type="button" class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Retirer</button>
                                </div>
                              </div>
                              <div class="flex justify-center gap-6 mb-8">
                                <div>
                                  <BaseInput v-model="form.nbr_valeur_totale_ap" id="valeur_totale_ap" label="Valeur Totale des A.P" type="number" readonly />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Valeurs finales -->
                        <div class="mb-6">
                          <div class="border-b">
                            <div class="mb-4">
                              <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                <div class="grid gap-6 mb-6 md:grid-cols-1">
                                  <BaseInput v-model="form.nbr_valeurVenaleLimeuble" id="nbr_valeurVenaleLimeuble" type="number" step="0.01" label="Valeur Vénale de l'immeuble" readonly class="w-64" />
                                </div>
                                <div class="grid gap-6 mb-6 md:grid-cols-1">
                                  <BaseInput v-model="form.nbr_valeurLocative" id="nbr_valeurLocative" type="number" step="0.01" label="Valeur Locative" readonly class="w-64" />
                                </div>
                                <div class="grid gap-6 mb-6 md:grid-cols-1">
                                  <BaseInput v-model="form.dt_dateEvaluation" id="dt_dateEvaluation" type="date" label="Date d'évaluation" class="w-64" />
                                </div>
                                <div class="sm:col-span-2">
                                  <div class="sm:col-span-1">
                                    <label for="fichierPDF" class="block font-medium text-primary-txt">Importer le Fichier PDF</label>
                                    <input type="file" name="fichierPDF" accept=".pdf" id="fichierPDF" @change="handleFileChangePDF"
                                      class="block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-txt/50 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" />
                                    <div v-if="form.fichierPDF && typeof form.fichierPDF === 'string'" class="mt-2">
                                      <a :href="`/storage/${form.fichierPDF}`" target="_blank" class="text-primary underline">Voir le fichier</a>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Bouton de soumission -->
                        <div class="sm:col-span-6 flex justify-center">
                          <MazBtn type="submit"
                            class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2"
                            size="medium">
                            Enregistrer modification
                          </MazBtn>
                        </div>
                      </div>
                    </div>
                  </form>
                </template>
                <template v-else>
                  <p class="text-red-500">Aucun terrain à modifier n’a été trouvé.</p>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>