<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import MazBtn from "maz-ui/components/MazBtn";
import { defineProps } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {onMounted, ref, computed, watch, watchEffect } from 'vue';
import axios from "axios"; 
import { useToast } from "maz-ui";
import MazTabs from 'maz-ui/components/MazTabs';
import MazRadio from 'maz-ui/components/MazRadio';
import DefaultLayout from "@/Layouts/DefaultLayout.vue";  

 

defineOptions({ layout: DefaultLayout }); 
// const fichierPDF = ref(null);

const { terrain } = defineProps({
    terrain: Object,
    regions: Array,
    departements: Array,
    arrondissements: Array,
    communes: Array,
    references_usages: Array, 
    occupants: Array,
    occupantsBP: Array,
})
 
const toast = useToast()
const page = usePage() 
const code = ref('')
const formVisible = ref(false)
const isLoading = ref(false) 
const maxOccupants = 25;
const maxOccupantsBP = 4;
const maxOccupantsCA = 3;
const maxOccupantsCL = 3;
const maxOccupantsAP = 3; 
// const currentCat = ref('');  
const showSectionPP = ref(false); // Perssone Physique
const showSectionPM = ref(false); // Personne Morale
const showSectionPA = ref(false); // Etat
// const activeTabMazcheck = ref("");   
const txt_num_section = ref('');
const txt_num_parcelle = ref(''); 
  
// Traitement Categorie 
const categories = {
    'Maison individuelle': {
        '1': 205891, '2': 190883, '3': 180937, '4': 158411,
        '5': 119942, '6': 88201, '7': 58801, '8': 40663,
        '9': 11117, '10': 10000, '11': 8000,
    },
    'Immeuble collectif': {
        'A': 244257, 'B': 226543, 'C': 204069, 'D': 177171,
        'E': 142293, 'F': 123552, 'G': 93878, 'H': 61602,
        'I': 53793, 'J': 25000, 'K': 22000, 'L': 20000, 'M': 8000,
    }
}
  
let show = ref(true);
    show.value = show;

let showBati = ref(true);
    showBati.value = !showBati;
    
let showCAmenager = ref(true);
    showCAmenager.value = !showCAmenager;
    
let showCloture = ref(true);
    showCloture.value = !showCloture;
        
let showAmenagement = ref(true);
    showAmenagement.value = !showAmenagement;
 
const activeTab = ref('terrain');
const setActiveTab = (tab) => {
    activeTab.value = tab;
};
 
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
 
const nbr_valeurPR = ref('');
const nbr_valeurTG = ref(''); 
//arondire les résultat
function arrondirDeuxDecimales(valeur) {
    const num = parseFloat(valeur);
    if (isNaN(num)) return 0;
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

// Reference usage 
function formatOccupants(references_usages) {
  if (Array.isArray(references_usages)) {
    return references_usages.map(o => formatOccupant(o));
  }

  if (references_usages) {
    return [formatOccupant(references_usages)];
  }

  return [formatOccupant()];
} 
 
// Evaluation Terrain
function formatOccupant(o = {}) {
  return {
    txt_nomOccupantTG: o.txt_nomOccupantTG || '',
    txt_numAppartementTG: o.txt_numAppartementTG || '',
    txt_activiteTG: o.txt_activiteTG || '',
    txt_nineaTG: o.txt_nineaTG || '',
    tel_telephoneTG: o.tel_telephoneTG || '',
    nbr_montantLoyerTG: o.nbr_montantLoyerTG || 0,
    txt_dateLieuNaissanceTG: o.txt_dateLieuNaissanceTG || '',
    txt_cniPasseportTG: o.txt_cniPasseportTG || '',
    dt_dateDelivranceTG: o.dt_dateDelivranceTG || ''
  };
}

// Evaluation Bati 
function formatOccupantsBP(evaluations_batis) {
  if (Array.isArray(evaluations_batis)) {
    return evaluations_batis.map(o => formatOccupantBP(o));
  }

  if (evaluations_batis) {
    return [formatOccupantBP(evaluations_batis)];
  }

  return [formatOccupantBP()];
}

function formatOccupantBP(o = {}) { 
  console.log('occupant brut reçu:', o); 
  return { 
    slt_dependant_domaineTG:  o.slt_dependant_domaineTG  || '',
    slt_categorieTG:  o.slt_categorieTG ||  '',
    nbr_prix_metre_carreTG: o.nbr_prix_metre_carreTG  || '',
    nbr_surface_bati_solTG: o.nbr_surface_bati_solTG  || '',
    nbr_niveauTG: o.nbr_niveauTG  || '',
    nbr_surface_utileTG:  o.nbr_surface_utileTG  || '',
    slt_coeffTG:  o.slt_coeffTG  || '',
    nbr_surface_corrigerTG: o.nbr_surface_corrigerTG  || '',
    nbr_valeurTG: o.nbr_valeurTG  || '', 
  };
}

// Evaluation Cours Amenagees 
function formatOccupantsCA(evaluations_cours_amenagees) {
  if (Array.isArray(evaluations_cours_amenagees)) {
    return evaluations_cours_amenagees.map(o => formatOccupantCA(o));
  }

  if (evaluations_cours_amenagees) {
    return [formatOccupantCA(evaluations_cours_amenagees)];
  }

  return [formatOccupantCA()];
}

function formatOccupantCA(o = {}) {  
  return {    
    slt_cours_amenager_totale: o.slt_cours_amenager_totale || '',
    nbr_surface_ca_total: o.nbr_surface_ca_total || '',
    slt_categorie_ca_total: o.slt_categorie_ca_total || '',
    nbr_prix_metre_carre_ca_total: o.nbr_prix_metre_carre_ca_total || '',
    nbr_coefficient_ca_total: o.nbr_coefficient_ca_total || '',
    nbr_valeur_ca_total: o.nbr_valeur_ca_total || '',
  };
}

// Evaluation Cloture 
function formatOccupantsCL(evaluations_clotures) {
  if (Array.isArray(evaluations_clotures)) {
    return evaluations_clotures.map(o => formatOccupantCL(o));
  }

  if (evaluations_clotures) {
    return [formatOccupantCL(evaluations_clotures)];
  }

  return [formatOccupantCL()];
}

function formatOccupantCL(o = {}) {  
  return {    
    slt_cours_amenager_clo: o.slt_cours_amenager_clo,
    nbr_longueur_avant_clo: o.nbr_longueur_avant_clo,
    slt_categorie_clo: o.slt_categorie_clo,
    nbr_prix_metre_carre_clo: o.nbr_prix_metre_carre_clo,
    nbr_coefficient_clo: o.nbr_coefficient_clo,
    nbr_valeur_clo: o.nbr_valeur_clo, 
  };
}

// Evaluation Amenagement 
function formatOccupantsAP(evaluations_amenagements) {
  if (Array.isArray(evaluations_amenagements)) {
    return evaluations_amenagements.map(o => formatOccupantAP(o));
  }

  if (evaluations_amenagements) {
    return [formatOccupantAP(evaluations_amenagements)];
  }

  return [formatOccupantAP()];
}

function formatOccupantAP(o = {}) {  
  return {     
    txt_designation_am: o.txt_designation_am,
    nbr_valeur_unitaire_am: o.nbr_valeur_unitaire_am,
    nbr_quantile_am: o.nbr_quantile_am,
    slt_coeficien_am: o.slt_coeficien_am,
    nbr_valeur_am: o.nbr_valeur_am,
  };
}

// Formater la date en français (jj/mm/aaaa)
const formatDate = (dateString) => {
    if (!dateString) return '-'; 
    const date = new Date(dateString); 
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // +1 car janvier = 0
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};
 
const form = useForm({
    txt_nicad: terrain?.txt_nicad || '', 
    // Table Dossier
    txt_num_dossier: terrain?.dossier?.txt_num_dossier  ?? '' ,
    txt_num_dordre: terrain?.dossier?.txt_num_dordre || '',
    slt_service_rendu: terrain?.dossier?.slt_service_rendu || '',
    txt_etat_cession: terrain?.dossier?.txt_etat_cession || '',
    txt_cession_definitive: terrain?.dossier?.txt_cession_definitive || '',
    dt_date_creation: formatDate(terrain?.dossier?.dt_date_creation) || '',
    // Table Terrain
    slt_region: terrain?.region?.id || '',
    slt_departement: terrain?.departement?.id || '',
    slt_commune: terrain?.commune?.id || '',
    slt_arrondissement: terrain?.arrondissement?.id || '',
    txt_lotissement: terrain?.txt_lotissement || '',
    txt_HorsLotissement: terrain?.txt_HorsLotissement || '',
    txt_num_lotissement: terrain?.txt_num_lotissement || '',
    txt_num_section:  terrain?.txt_num_section || '',
    txt_num_parcelle: terrain?.txt_num_parcelle || '', 
    nbr_surface: terrain?.nbr_surface || '',
    slt_document_admin: terrain?.slt_document_admin || '',
    txt_num_deliberation: terrain?.txt_num_deliberation || '',
    dt_date_deliberation: terrain?.dt_date_deliberation || '',
    // Table ReferenceCadastrale
    rd_immatriculation_terrain: terrain?.references_cadastrales?.rd_immatriculation_terrain || '',
    slt_dependant_domaine: terrain?.references_cadastrales?.slt_dependant_domaine || '', 
    ussu_bornage: terrain?.references_cadastrales?.ussu_bornage || '',
    txt_num_titre:  terrain?.references_cadastrales?.txt_num_titre || '', 
    txt_titre_mere: terrain?.references_cadastrales?.txt_titre_mere || '', 
    txt_appartement: terrain?.references_cadastrales?.txt_appartement || '', 
    slt_lf: terrain?.references_cadastrales?.slt_lf || '',
    txt_num_requisition:  terrain?.references_cadastrales?.txt_num_requisition || '',
    txt_surface_bornage:  terrain?.references_cadastrales?.txt_surface_bornage || '',
    dt_date_bornage:  terrain?.references_cadastrales?.dt_date_bornage || '',
    txt_nom_geometre: terrain?.references_cadastrales?.txt_nom_geometre || '',
    // Table Titulaire
    slt_titulaire:  terrain?.titulaire?.slt_titulaire || '',
    txt_nationalite:  terrain?.titulaire?.txt_nationalite || '',
    slt_civilite: terrain?.titulaire?.slt_civilite || '',
    txt_prenom: terrain?.titulaire?.txt_prenom || '',
    txt_nom:  terrain?.titulaire?.txt_nom || '',
    slt_piece:  terrain?.titulaire?.slt_piece || '',
    txt_numPiece: terrain?.titulaire?.txt_numPiece || '',
    dt_date_delivrance: terrain?.titulaire?.dt_date_delivrance || '',
    dt_date_naissance:  terrain?.titulaire?.dt_date_naissance || '',
    txt_lieu_naissance: terrain?.titulaire?.txt_lieu_naissance || '',
    txt_adresse:  terrain?.titulaire?.txt_adresse || '',
    tel_telephone:  terrain?.titulaire?.tel_telephone || '',
    txt_ninea:  terrain?.titulaire?.txt_ninea || '',
    eml_email:  terrain?.titulaire?.eml_email || '',
    txt_representant: terrain?.titulaire?.txt_representant || '',
    tel_telRepresentant:  terrain?.titulaire?.tel_telRepresentant || '',
        // Personne physique
    slt_categoriePM: terrain?.titulaire?.slt_categoriePM || '',
    txt_formJuridiquePM: terrain?.titulaire?.txt_formJuridiquePM || '', 
    txt_denominationPM: terrain?.titulaire?.txt_denominationPM || '', 
    txt_nineaPM: terrain?.titulaire?.txt_nineaPM || '', 
    txt_perssonneRepresentantPM: terrain?.titulaire?.txt_perssonneRepresentantPM || '', 
    txt_fonctionPM: terrain?.titulaire?.txt_fonctionPM || '', 
    txt_telephonePM: terrain?.titulaire?.txt_telephonePM || '', 
    txt_emailPM: terrain?.titulaire?.txt_emailPM || '', 
    txt_adressePM: terrain?.titulaire?.txt_adressePM || '', 
        // Personne morale
    slt_etablissementPA: terrain?.titulaire?.slt_etablissementPA || '',
    txt_personneResponsablePA: terrain?.titulaire?.txt_personneResponsablePA || '',
    txt_fonctionResponsablePA: terrain?.titulaire?.txt_fonctionResponsablePA || '',
    txt_telephonePA: terrain?.titulaire?.txt_telephonePA || '',
    txt_emailPA: terrain?.titulaire?.txt_emailPA || '',
    txt_ministèreTutelePA: terrain?.titulaire?.txt_ministèreTutelePA || '',
    fichierPDF: terrain.titulaire?.fichierPDF || null,
    // References Usage   
    slt_usage: terrain?.references_usages?.[0]?.slt_usage || '',
    slt_residence: terrain?.references_usages?.[0]?.slt_residence || '',
    terrain_id: terrain?.terrain?.id || null,
    occupants: formatOccupants(terrain?.references_usages), 
    nbr_montantLoyerTotal: terrain?.references_usages?.nbr_montantLoyerTotal  ||  '',
    nbr_TVATotal: terrain?.references_usages?.nbr_TVATotal  ||  '', 
    // Evaluation Terrain  
    txt_superficie_totale: terrain?.evaluations_terrains?.txt_superficie_totale || '',
    txt_superficie_bati_sol: terrain?.evaluations_terrains?.txt_superficie_bati_sol || '',
    slt_secteur: terrain?.evaluations_terrains?.slt_secteur || '',
    nbr_prix_metre_carre: terrain?.evaluations_terrains?.nbr_prix_metre_carre || '',
    nbr_valeur_terrain: terrain?.evaluations_terrains?.nbr_valeur_terrain || '',
    nbr_valeurVenaleLimeuble: terrain?.evaluations_terrains?.nbr_valeurVenaleLimeuble ??  0, 
    nbr_valeurLocative: terrain?.evaluations_terrains?.nbr_valeurLocative ||  0,
    dt_dateEvaluation:  terrain?.evaluations_terrains?.dt_dateEvaluation  ||  '', 
    //  Evaluation Bati 
    txt_dependant_domainePR: terrain?.evaluations_batis?.txt_dependant_domainePR  || '',
    slt_categoriePR:  terrain?.evaluations_batis?.slt_categoriePR ||  '',
    nbr_prix_metre_carrePR: terrain?.evaluations_batis?.nbr_prix_metre_carrePR  || '',
    nbr_surface_bati_solPR: terrain?.evaluations_batis?.nbr_surface_bati_solPR  ||  '',
    nbr_niveauPR: terrain?.evaluations_batis?.nbr_niveauPR  ||'',
    nbr_surface_utilePR:  terrain?.evaluations_batis?.nbr_surface_utilePR || '',
    slt_coeffPR:  terrain?.evaluations_batis?.slt_coeffPR || '',
    nbr_surface_corrigerPR: terrain?.evaluations_batis?.nbr_surface_corrigerPR  || '',
    nbr_valeurPR: terrain?.evaluations_batis?.nbr_valeurPR  || '',
    currentCat: terrain?.evaluations_batis?.currentCat  ||  '',
    occupantsBP: formatOccupantsBP(terrain?.evaluations_batis), 
    txt_valeur_terrain_bati:  terrain?.evaluations_batis?.txt_valeur_terrain_bati ||  '',
    // Evaluation Cours Aménagé 
    occupantsCA: formatOccupantsCA(terrain?.evaluations_cours_amenagees), 
    nbr_valeur_total_ca: terrain?.evaluations_cours_amenagees?.nbr_valeur_total_ca || '',
    // Clôtures
    occupantsCL: formatOccupantsCL(terrain?.evaluations_clotures), 
    nbr_valeur_total_clotur: terrain?.evaluations_clotures?.nbr_valeur_total_clotur || '',
    // Aménagements particuliers
    occupantsAP: formatOccupantsAP(terrain?.evaluations_amenagements), 
    nbr_valeur_totale_ap: terrain?.evaluations_amenagements?.nbr_valeur_totale_ap || '',
})

// fichierPDF
// ✅ Méthode pour gérer le fichier
function handleFileChangePDF(event) {
    const file = event.target.files[0];
    console.log('Fichier sélectionné:', file);
    
    if (file && file.type === "application/pdf") {
        form.fichierPDF = file; // ✅ Assigner directement le File object
    } else {
        form.fichierPDF = null;
        toast.error("❌ Veuillez sélectionner un fichier PDF valide.");

    }
}

// MazTabs
// pour afficher la section Terrain Immatriculé
const showTerrainImmatricule = ref(false);
const showNonImmatricule = ref(false);

const handleCheckboxChange = () => {
    showTerrainImmatricule.value = activeTab.value.includes("Terrain Immatriculé");
    showNonImmatricule.value = activeTab.value.includes("Terrain Non Immatriculé"); 
}; 
// Réagir automatiquement quand la valeur change
watch(activeTab, () => {
    handleCheckboxChange();
}, { deep: true }); 
// ⚡ Initialiser  
onMounted(() => {
    handleCheckboxChange();
});

// Ussu Bornage
const showMorcellement = ref(false);
const handleSelectChange = () => {
    showMorcellement.value = form.ussu_bornage === "Morcellement de Copropriété";
};
// Réagir automatiquement quand l’utilisateur change
watch(() => form.ussu_bornage, () => {
    handleSelectChange();
});
// ⚡ Initialiser dès le chargement (update)
onMounted(() => {
    handleSelectChange();
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
watch(() => form.slt_titulaire, () => { 
    handleSelectChangeTitulaire();  
});
// ⚡ Initialiser dès le chargement de la page (update)
onMounted(() => {
    handleSelectChangeTitulaire();
});
 
// Occupants
function addBlock() {
  if (form.occupants.length < maxOccupants) {
    form.occupants.push({
      id: Date.now() + Math.random(),
      txt_nomOccupantTG: '',
      txt_numAppartementTG: '',
      txt_activiteTG: '',
      txt_nineaTG: '',
      tel_telephoneTG: '',
      nbr_montantLoyerTG: 0,
      txt_dateLieuNaissanceTG: '',
      txt_cniPasseportTG: '',
      dt_dateDelivranceTG: ''
    })
  }
}
function removeBlock(i) {
  if (form.occupants.length > 1) {
    form.occupants.pop()
  }
}

// Batiment Principal
// bloc ajouter 
function addBlockBP() {
    if (form.occupantsBP.length < maxOccupantsBP) {
        form.occupantsBP.push({
            id: Date.now() + Math.random(), 
            slt_dependant_domaineTG: '',
            slt_categorieTG: '',
            nbr_prix_metre_carreTG: '',
            nbr_surface_bati_solTG: '',
            nbr_niveauTG: '',
            nbr_surface_utileTG: '',
            slt_coeffTG: '',
            nbr_surface_corrigerTG: '',
            nbr_valeurTG: '', 
        })                
    }else {
        toast.error('Impossible d\'ajouter plus de 5 occupants.');
    }
}
// bloc retirer 
function removeBlockBP(i) {
    if (form.occupantsBP.length > 1) {
        form.occupantsBP.pop()
    }
}

// Cours Aménager
// bloc ajouter 
function addBlockCA() {
    if (form.occupantsCA.length < maxOccupantsCA) {
        form.occupantsCA.push({
            id: Date.now() + Math.random(),
            slt_cours_amenager_totale:'',
            nbr_surface_ca_total:'', 
            slt_categorie_ca_total:'', 
            nbr_prix_metre_carre_ca_total:'', 
            nbr_coefficient_ca_total:'',
            nbr_valeur_ca_total:'',
        })                
    }else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}
// bloc retirer 
function removeBlockCA(i) {
    if (form.occupantsCA.length > 1) {
        form.occupantsCA.pop()
    }
}

// Clôture
// bloc ajouter 
function addBlockCL() {
    if (form.occupantsCL.length < maxOccupantsCL) {
        form.occupantsCL.push({
            id: Date.now() + Math.random(),
            slt_cours_amenager_clo:'',  
            nbr_longueur_avant_clo:'', 
            slt_categorie_clo:'', 
            nbr_prix_metre_carre_clo:'', 
            nbr_coefficient_clo:'', 
            nbr_valeur_clo:'',
        })                
    }else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}
// bloc retirer 
function removeBlockCL(i) {
    if (form.occupantsCL.length > 1) {
        form.occupantsCL.pop()
    }
}

// Cours Aménager
// bloc ajouter 
function addBlockAP() {
    if (form.occupantsAP.length < maxOccupantsAP) {
        form.occupantsAP.push({
            id: Date.now() + Math.random(),
            txt_designation_am:'', 
            nbr_valeur_unitaire_am:'', 
            nbr_quantile_am:'', 
            slt_coeficien_am:'', 
            nbr_valeur_am:'',
        })                
    }else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}
// Converire la récupération du choix de categorie  
function removeBlockAP(i) {
    if (form.occupantsAP.length > 1) {
        form.occupantsAP.pop()
    }
}

// Calcule Montan Loayer 
const montantLoyerTotal = computed(() => {
  const totalExistants = (terrain.references_usages || []).reduce((acc, usage) => {
    return acc + (Number(usage.nbr_montantLoyerTG) || 0)
  }, 0)

  const totalOccupants = (form.occupants || []).reduce((acc, occupant) => {
    return acc + (Number(occupant.nbr_montantLoyerTG) || 0)
  }, 0)

  return totalExistants + totalOccupants
}) 
watchEffect(() => {
  form.nbr_montantLoyerTotal = montantLoyerTotal.value
})
 
// Calcule du TVA
const nbr_TVATotal = computed(() => {
  return (Number(form.nbr_montantLoyerTotal) || 0) * 0.18
}) 
watchEffect(() => {
  form.nbr_TVATotal = nbr_TVATotal.value.toFixed(2) // 2 décimales
})
 
// Verification saisis txt_superficie_bati_sol 
watchEffect(() => {
    const bati = Number(form.txt_superficie_bati_sol);
    const surfacebatiSolPR = Number(form.nbr_surface_bati_solPR);
 
    const surface = Number(form.nbr_surface);
    form.txt_superficie_non_bati = (surface - bati).toFixed(2);

    if (bati > surface) {
        toast.error("Surface bâtie dépasse la superficie terrain.");
        form.txt_superficie_bati_sol = surface; 
    }if (surfacebatiSolPR > bati) {
        toast.error("Surface bâtiment Principal dépasse la Superficie Bati.");
        form.nbr_surface_bati_solPR = bati;       
    }

});

// Verification saisis nbr_surface_bati_solTG 
function verifierSurfaceOccupant(occupant) {
    const bati = Number(form.txt_superficie_bati_sol);
    const surfacebatisolTG = Number(occupant.nbr_surface_bati_solTG);
    const surfaceCA = Number(occupant.nbr_surface_ca_total);
        
    const srfNonBati = Number(form.txt_superficie_non_bati);

    if (surfacebatisolTG > bati) {
        toast.error("Surface bâtiment dépasse la Superficie Bati.");
        occupant.nbr_surface_bati_solTG = bati; 
    } if (surfaceCA > srfNonBati) {
        toast.error("Surface Cours de l'occupant dépasser la superficie non Bati.");
        occupant.nbr_surface_ca_total = srfNonBati; 
    }
}

// Calculer Valeur Terrain  
const prixParSecteur = {
    1: 3000,
    2: 2000,
    3: 1000,
} 
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

// 🔁 Mettre à jour le prix du mètre carré selon la catégorie principale
watch(() => [form.currentCat, form.slt_categoriePR], ([type, cat]) => {
    if (type && cat && categories[type]?.[cat]) {
        form.nbr_prix_metre_carrePR = categories[type][cat] 
    } else {
        form.nbr_prix_metre_carrePR = null
    }
}) 

// 🔁 Mettre à jour les prix pour tous les occupants selon leur catégorie
watchEffect(() => {
    const type = form.currentCat
    form.occupantsBP.forEach((occupant) => {
        const cat = occupant.slt_categorieTG
        occupant.nbr_prix_metre_carreTG = (categories[type] && categories[type][cat]) || null
    })
})  
// Calcule Batiment : Princile  
// Prix m² PR
watchEffect(()  => {
    form.txt_dependant_domainePR  = "Primaire";
}),
// Surface Utile PR
watchEffect(() => {
    const surfaceBS = parseFloat(form.nbr_surface_bati_solPR) || 0;
    const nbrNiveau = parseFloat(form.nbr_niveauPR) || 0;
    form.nbr_surface_utilePR = (surfaceBS * nbrNiveau * 0.78).toFixed(2);
});
// Surface Corrigée PR
watchEffect(() => { 
    const utile = parseFloat(form.nbr_surface_utilePR) || 0;
    const coeff = parseFloat(form.slt_coeffPR) || 0;
    form.nbr_surface_corrigerPR = (utile * coeff).toFixed(2); 
});
// Valeur Bâtie PR
watchEffect(() => {
    const prixmetrecarre = parseFloat(form.nbr_prix_metre_carrePR) || 0; 
    const surfaceCorrige = parseFloat(form.nbr_surface_corrigerPR) || 0;
    form.nbr_valeurPR = (prixmetrecarre * surfaceCorrige).toFixed(2); 
}); 

// Calcule Surface Utule
watchEffect(() => {
    form.occupantsBP.forEach((occupant) => {
        const surfaceBS = parseFloat(occupant.nbr_surface_bati_solTG) || 0;
        const nbrNiveau = parseFloat(occupant.nbr_niveauTG) || 0;
        occupant.nbr_surface_utileTG = (surfaceBS * nbrNiveau * 0.78).toFixed(2);
    });
});
// Calcule Surface corrige 
watchEffect(() => {
    form.occupantsBP.forEach((occupant) => {
        const utile = parseFloat(occupant.nbr_surface_utileTG) || 0;
        const coeff = parseFloat(occupant.slt_coeffTG) || 0;
        occupant.nbr_surface_corrigerTG = (utile * coeff).toFixed(2);
    });
});
//  Calcule valeur TG : nbr_valeurTG
watchEffect(() => {
    form.occupantsBP.forEach((occupant) => {
        const prixmetrecarre = parseFloat(occupant.nbr_prix_metre_carreTG) || 0; 
        const surfaceCorrige = parseFloat(occupant.nbr_surface_corrigerTG) || 0;
        occupant.nbr_valeurTG = ( prixmetrecarre * surfaceCorrige).toFixed(2);
    });
});
//  Calculer montant valeur batiment  
const txt_valeur_terrain_bati = computed(() => {
    const valeurPR = parseFloat(form.nbr_valeurPR)  || 0;
    return form.occupantsBP.reduce((total, occupant) => {
        const montant = parseFloat(occupant.nbr_valeurTG) || 0;
        return total + montant + valeurPR;
    }, 0);
});
watchEffect(() => {
    form.txt_valeur_terrain_bati = txt_valeur_terrain_bati.value;
});

// calculer nbr_valeur_ca_total     slt_categorie
const coeffCAValues = {
    1: 17500,
    2: 13500,
    3: 10500,
    4: 5000
}; 
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
// Sommes Cours Amenager nbr_valeur_total_cours
const nbr_valeur_total_ca = computed(() => {nbr_valeur_total_ca
    return form.occupantsCA.reduce((total, occupant) => {
        const montant = parseFloat(occupant.nbr_valeur_ca_total) || 0;
        return total + montant;
    }, 0);
});
watchEffect(() => {
    form.nbr_valeur_total_ca = nbr_valeur_total_ca.value;
});

 
// calculer nbr_valeur_clo 
const coeffCLValues = { 
    1: 48269,
    2: 35105,
    3: 28084,
    4: 25744,
    5: 11848,
    6: 4209,
    7: 853
}; 
watchEffect(() => {
    form.occupantsCL.forEach((occupant) => {
        const categorieCL = Number(occupant.slt_categorie_clo) || 0;
        const prixmetrecarreCL = coeffCLValues[categorieCL] || 0; 

        const lineaire = parseFloat(occupant.nbr_longueur_avant_clo) || 0;
        const coeffCL = parseFloat(occupant.nbr_coefficient_clo) || 0;
        occupant.nbr_prix_metre_carre_clo = prixmetrecarreCL;

        occupant.nbr_valeur_clo = ( lineaire * prixmetrecarreCL * coeffCL).toFixed(2);
    });
}); 
watchEffect(() => {
  form.occupantsCL.forEach((occupant) => {
    const prixmetrecarreCL = parseFloat(occupant.nbr_prix_metre_carre_clo) || 0; 
    const lineaire = parseFloat(occupant.nbr_longueur_avant_clo) || 0;
    const coeffCL = parseFloat(occupant.nbr_coefficient_clo) || 0;
    occupant.nbr_valeur_clo = ( lineaire * prixmetrecarreCL * coeffCL).toFixed(2);
  });
});
// Sommes Cours Amenager nbr_valeur_total_clotur
const nbr_valeur_total_clotur = computed(() => {nbr_valeur_total_clotur
    return form.occupantsCL.reduce((total, occupant) => {
        const montant = parseFloat(occupant.nbr_valeur_clo) || 0;
        return total + montant;
    }, 0);
});
watchEffect(() => {
    form.nbr_valeur_total_clotur = nbr_valeur_total_clotur.value;
});

// calculer nbr_valeur_totale_AP, 
watchEffect(() => {
  form.occupantsAP.forEach((occupant) => {
    // nbr_valeur_unitaire_am, nbr_quantile_am, slt_coeficien_am
    const valeurUnitaire = parseFloat(occupant.nbr_valeur_unitaire_am) || 0; 
    const quantite = parseFloat(occupant.nbr_quantile_am) || 0;
    const coeffCA = parseFloat(occupant.slt_coeficien_am) || 0;
    occupant.nbr_valeur_am = ( valeurUnitaire * quantite * coeffCA).toFixed(2);
  });
}); 
// Sommes Amenagement particulier nbr_valeur_totale_ap
const nbr_valeur_totale_ap = computed(() => {nbr_valeur_totale_ap
    return form.occupantsAP.reduce((total, occupant) => {
        const montant = parseFloat(occupant.nbr_valeur_am) || 0;
        return total + montant;
    }, 0);
});
watchEffect(() => {
    form.nbr_valeur_totale_ap = nbr_valeur_totale_ap.value;
});


// Calcule nbr_valeurVenaleLimeuble ,nbr_valeurLocative
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


const isPrincipalSelected = computed(() => form.txt_dependant_domainePR === 'Principale');

const tauxValeurLocative = computed(() => {
    if (!isPrincipalSelected.value) return 0;

    const cat = Number(form.nbr_prix_metre_carrePR) || 0;

    if ([244257, 205891].includes(cat)) {
        return 0.1344;
    } else if ([226543, 204069, 180937, 190883].includes(cat)) {
        return 0.12;
    } else if (
        [
        177171, 142293, 123552, 93878, 61602, 53793,
        25000, 22000, 20000, 8000, 158411, 119942,
        88201, 58801, 40663, 11117, 10000
        ].includes(cat)
    ) {
        return 0.10;
    } else {
        return 0.10;
    }
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
 
// rechercher
const rechercherDossier = async () => {
    if (!code.value) {
        toast.error("Veuillez entrer un code d'accès.")
        return
    }

    isLoading.value = true

    try {
        const { data } = await axios.post(route('code.verify'), {
            code: code.value
        })

        if (data.exists) {
            formVisible.value = true
            toast.success("Modification autorisée !")
            code.value = ''
        } else {
            toast.error("Vérifie ton code d'accès !")
            formVisible.value = false
            code.value = ''
        }

    } catch (err) {
        formVisible.value = false

        if (err.response?.status === 422 && err.response.data.errors) {
                Object.values(err.response.data.errors).forEach(msg => toast.error(msg))
        } else {
                toast.error("Une erreur est survenue lors de la vérification.")
        }

    } finally {
        isLoading.value = false
    }
}
 
// Récuperation des fichier PDF 
   
// function submit() {
//     console.log("📤 Données envoyées :", form);
//     form.put(route('secretariat.update', terrain.id), form.data(), {
//         forceFormData: true,   // needed for files
//         preserveScroll: true, 
//         onSuccess: () => toast.success("✅ Modification réussie !"),
//         onError: (errors) => console.error(errors),
//     }); 
    

// }

async function submit() {
    try {
        // 1️⃣ Envoyer tout le formulaire (JSON si pas de fichier)
        await  form.put(route('secretariat.update', terrain.id), form.data(),  {
            forceFormData: true,  
            preserveScroll: true,
            onSuccess: () => console.log("✅ Update réussi !"),
            onError: (errors) => console.error(errors),
        });

        // 2️⃣ Envoyer le fichier PDF si présent
        if (form.fichierPDF instanceof File) {
            const pdfForm = new FormData();
            pdfForm.append('fichierPDF', form.fichierPDF);

            await axios.post(route('secretariat.uploadPDF', terrain.id), pdfForm, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

         
    } catch (error) {
        console.error("❌ Erreur submit :", error);
        toast.error("❌ Erreur lors de la modification");
    }
}
 
</script>

<template>
  <Head title="Modifier Terrain" > 
      <meta name="csrf-token" content="{{ csrf_token() }}">
  </Head>

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-primary-txt">
        Modification du Terrain
      </h2>
    </template>

    <div class="py-12">
      <div class="flex justify-center">  
        <div class="w-full max-w-7xl">  
            <div   v-if="!formVisible" class="w-full max-w-7xl">
              <div class="bg-white shadow-md rounded-lg">
                <div class="p-4 border-b bg-primary">
                    <h1 class="text-2xl text-center text-white font-semibold">Veillez entrer votre code d'accès !</h1>
                </div>
                <div class="py-6">  
                    <div class="flex justify-center"> 
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-2xl">
                            <div class="sm:col-span-4 px-5">
                                <input 
                                    autocomplete="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    v-model="code" 
                                    type="text"
                                    name="code"
                                    aria-label="Code d'accès"
                                    class="h-10 block w-full rounded-md bg-white px-2 py-1.5 text-base text-primary-txt 
                                            outline outline-1 outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                            focus:outline-2 focus:outline-primary sm:text-sm/6"
                                    placeholder="Entrez votre code d'accès"
                                    required
                                />
                            </div>

                            <MazBtn 
                                :loading="isLoading"
                                type="button"
                                title="Confirmer"
                                @click="rechercherDossier"
                                class="w-36 h-10 text-white bg-gradient-to-r from-primary via-primary-dark 
                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                py-2.5 text-center me-2 mb-2"
                                size="large"
                            >
                                Confirmer
                            </MazBtn>
                        </div> 
                    </div>  
                </div> 
              </div>
            </div> 
        </div> 
      </div>
      <br>
      <div class="flex justify-center ">  
          <div class="w-full max-w-7xl"> 
              <div   v-if="formVisible" class="w-full ">
                  <div class="bg-white shadow-md rounded-lg">
                      <div class="p-4 border-b bg-primary">
                          <h1 class="text-2xl text-white font-semibold">Pages de Modifiation !</h1>
                      </div>
                      <div class="p-6">  
                          <template v-if="terrain">

                            <div class="flex justify-center"> 
                              <h1 class="text-lg text-primary-txt font-bold">
                                Modifier la parcelle de numéro dossier : {{ terrain.dossier.txt_num_dossier }}
                              </h1> 
                            </div>

                            <!-- Ici tu pourras mettre ton formulaire de modification -->
                
                            <form  @submit.prevent="submit"   class="space-y-6"> 
                                <div class="p-6"> 
                                  <div class="sm:col-span-12">

                                    <h5 class="text-lg text-primary-txt font-bold">
                                      Reference Dossier
                                    </h5><br>

                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                      <div class="sm:col-span-2">
                                        <label for="txt_num_dossier" class="block font-medium text-primary-txt">Numéro de dossier</label>
                                        <input v-model="form.txt_num_dossier" id="txt_num_dossier" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/>
                                        <div v-if="form.errors.txt_num_dossier" class="text-red-500">{{ form.errors.txt_num_dossier }}</div>
                                      </div>

                                      <div>
                                        <label for="slt_service_rendu" class="block font-medium text-primary-txt">Service Rendu</label>
                                        <input v-model="form.slt_service_rendu" id="slt_service_rendu" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.slt_service_rendu" class="text-red-500">{{ form.errors.slt_service_rendu }}</div>
                                      </div>

                                      <div>
                                        <label for="txt_etat_cession" class="block font-medium text-primary-txt">Etat Cession</label>
                                        <input v-model="form.txt_etat_cession" id="txt_etat_cession" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_etat_cession" class="text-red-500">{{ form.errors.txt_etat_cession }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="txt_cession_definitive" class="block font-medium text-primary-txt">Cession Definitive</label>
                                        <input v-model="form.txt_cession_definitive" id="txt_cession_definitive" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_cession_definitive" class="text-red-500">{{ form.errors.txt_cession_definitive }}</div>
                                      </div> 
                                                                      
                                      <div>
                                        <label for="dt_date_creation" class="block font-medium text-primary-txt">Date creation</label> 
                                        <input v-model="form.dt_date_creation" id="dt_date_creation" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.dt_date_creation" class="text-red-500">{{ form.errors.dt_date_creation }}</div>
                                      </div>
                                    </div><br>
                            
                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                      <div>
                                        <label for="slt_region" class="block font-medium text-primary-txt">Région</label>
                                        <select v-model="form.slt_region"  id="slt_region"
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          <option v-for="region in regions" :value="region.id" :key="region.id">
                                            {{ region.slt_region }}
                                          </option>
                                        </select>
                                        <div v-if="form.errors.slt_region" class="text-red-500">{{ form.errors.slt_region }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="slt_departement" class="block font-medium text-primary-txt">Département</label>
                                        <select v-model="form.slt_departement"  id="slt_departement"
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          <option v-for="departement in departements" :value="departement.id" :key="departement.id">
                                            {{ departement.slt_departement }}
                                          </option>
                                        </select>
                                        <div v-if="form.errors.slt_departement" class="text-red-500">{{ form.errors.slt_departement }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="slt_arrondissement" class="block font-medium text-primary-txt">Arrondissement</label>
                                        <select v-model="form.slt_arrondissement"  id="slt_arrondissement"
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          <option v-for="arrondissement in arrondissements" :value="arrondissement.id" :key="arrondissement.id">
                                            {{ arrondissement.slt_arrondissement }}
                                          </option>
                                        </select>
                                        <div v-if="form.errors.slt_arrondissement" class="text-red-500">{{ form.errors.slt_arrondissement }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="slt_commune" class="block font-medium text-primary-txt">Commune</label>
                                        <select v-model="form.slt_commune"  id="slt_commune"
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          <option v-for="commune in communes" :value="commune.id" :key="commune.id">
                                            {{ commune.slt_commune }}
                                          </option>
                                        </select>
                                        <div v-if="form.errors.slt_commune" class="text-red-500">{{ form.errors.slt_commune }}</div>
                                      </div>  

                                      <div>
                                        <label for="txt_lotissement" class="block font-medium text-primary-txt">Lotissement</label>
                                        <input v-model="form.txt_lotissement" id="txt_lotissement" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_lotissement" class="text-red-500">{{ form.errors.txt_lotissement }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="txt_HorsLotissement" class="block font-medium text-primary-txt">Hors Lotissement</label>
                                        <input v-model="form.txt_HorsLotissement" id="txt_HorsLotissement" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_HorsLotissement" class="text-red-500">{{ form.errors.txt_HorsLotissement }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="txt_num_lotissement" class="block font-medium text-primary-txt">N° Lot</label>
                                        <input v-model="form.txt_num_lotissement" id="txt_num_lotissement" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_num_lotissement" class="text-red-500">{{ form.errors.txt_num_lotissement }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="nbr_surface" class="block font-medium text-primary-txt">Surface</label>
                                        <input v-model="form.nbr_surface" id="nbr_surface" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.nbr_surface" class="text-red-500">{{ form.errors.nbr_surface }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="slt_document_admin" class="block font-medium text-primary-txt">Document admin</label>
                                        <select
                                            name="slt_document_admin"
                                            v-model="form.slt_document_admin"
                                            id="slt_document_admin" 
                                            class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                            <option selected desabled></option>
                                            <option value="Lettre D'atribution">Lettre D'atribution</option>
                                            <option value="Attestation Foncière">Attestation Foncière</option>
                                            <option value="Notification">Notification</option>
                                            <option value="Bail">Bail</option>
                                            <option value="Acte adminitratif">Acte adminitratif</option>
                                            <option value="Permis d'occupation">Permis d'occupation</option>
                                            <option value="Déliberation">Déliberation</option>
                                            <option value="Sans Papier">Sans Papier</option>
                                        </select>
                                        <div v-if="form.errors.slt_document_admin" class="text-red-500">{{ form.errors.slt_document_admin }}</div>
                                      </div>

                                      <div>
                                        <label for="txt_num_deliberation" class="block font-medium text-primary-txt">N° Déliberation </label>
                                        <input v-model="form.txt_num_deliberation" id="txt_num_deliberation" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_num_deliberation" class="text-red-500">{{ form.errors.txt_num_deliberation }}</div>
                                      </div>
                            
                                      <div>
                                        <label for="dt_date_deliberation" class="block font-medium text-primary-txt">Date Déliberation</label>
                                        <input v-model="form.dt_date_deliberation" id="dt_date_deliberation" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.dt_date_deliberation" class="text-red-500">{{ form.errors.dt_date_deliberation }}</div>
                                      </div>
        
                                      <div>
                                        <label for="txt_num_section" class="block font-medium text-primary-txt">N° section</label>
                                        <input v-model="form.txt_num_section" id="txt_num_section" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                          maxlength="3"
                                          minlength="3"/> 
                                        <div v-if="form.errors.txt_num_section" class="text-red-500">{{ form.errors.txt_num_section }}</div>
                                      </div>
                                      
                                      <div>
                                        <label for="txt_num_parcelle" class="block font-medium text-primary-txt">N° Parcelle</label>
                                        <input v-model="form.txt_num_parcelle" id="txt_num_parcelle" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                          maxlength="5"
                                          minlength="5"/> 
                                        <div v-if="form.errors.txt_num_parcelle" class="text-red-500">{{ form.errors.txt_num_parcelle }}</div>
                                      </div>  
                                      
                                      <div class="sm:col-span-2">
                                        <div class="sm:col-span-1">  
                                          <label for="txt_nicad" class="block font-medium text-primary-txt">NICAD</label>
                                          <input v-model="form.txt_nicad" id="txt_nicad" type="text" 
                                            class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                          <div v-if="form.errors.txt_nicad" class="text-red-500">{{ form.errors.txt_nicad }}</div>
                                        </div>
                                      </div> 

                                    </div><br>

                                    <h5 class="text-lg text-primary-txt font-bold">
                                      Reference Cadastrale
                                    </h5><br>
                                      
                                    <div> 
                                        <MazRadio
                                            v-model="form.rd_immatriculation_terrain" 
                                            class="text-primary-txt"
                                            value="Terrain Non Immatriculé"
                                            label="Terrain Non Immatriculé"
                                        />
                                        <MazRadio
                                            v-model="form.rd_immatriculation_terrain" 
                                            class="text-primary-txt"
                                            value="Terrain Immatriculé"
                                            label="Terrain Immatriculé"
                                        />
                                        <div v-if="form.errors.rd_immatriculation_terrain" class="text-red-500">{{ form.errors.rd_immatriculation_terrain }}</div>
                                       
                                    </div>
                                      
                                    <!-- Contenu du Tab 1 ici -->
                                    <div v-if="form.rd_immatriculation_terrain === 'Terrain Non Immatriculé'" class="maz-py-4">
                                        <div>
                                      <br/> 
                                      <div class="sm:col-span-12">
                                          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                              <div class="sm:col-span-2">
                                                  <label for="slt_dependant_domaine" class="block font-medium text-primary-txt" >
                                                      Dépendant du domaine
                                                  </label> 
                                                  <select 
                                                    name="slt_dependant_domaine"
                                                    v-model="form.slt_dependant_domaine" 
                                                    id="slt_dependant_domaine"
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-pripary-txt 
                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                  >
                                                    <option disabled value=""></option>
                                                    <option value="Domaine National">Domaine National</option>
                                                    <option value="Domaine Public">Domaine Public</option>
                                                    <option value="Domaine Fluvial">Domaine Fluvial</option>
                                                    <option value="Domaine Maritime">Domaine Maritime</option>
                                                  </select> 
                                              </div> 
                                          </div>
                                      </div>
                                    </div>
                                   
                                    <!-- Contenu du Tab 2 ici -->
                                    <div v-if="form.rd_immatriculation_terrain === 'Terrain Immatriculé'" class="maz-py-4">
                                        <!-- Contenu du Tab 2 ici -->
                                        <br>
                                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                  
                                                <label for="ussu_bornage" class="block font-medium text-primary-txt">Ussu de bornage</label> 
                                                <select
                                                    v-model="form.ussu_bornage" 
                                                    id="ussu_bornage"
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
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
                                       
                                            <div>
                                                <label for="Titre_mere"  class="block font-medium text-primary-txt">Titre Mere</label> 
                                                <input
                                                    v-model="form.txt_titre_mere"
                                                    type="text" 
                                                    id="Titre_mere" 
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>

                                            <div>
                                                <label
                                                    for="numTitre"
                                                    class="block font-medium text-primary-txt"
                                                    >N° Titre</label
                                                > 
                                                <input
                                                    type="text" 
                                                    v-model="form.txt_num_titre"
                                                    id="numTitre" 
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>
                                            
                                            <div>
                                                <label for="slt_lf" class="block font-medium text-primary-txt">LF</label> 
                                                <select
                                                    v-model="form.slt_lf" 
                                                    id="slt_lf" 
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
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

                                            <div>
                                                <label for="Num_requisition" class="block font-medium text-primary-txt">N° Requisition</label> 
                                                <input
                                                    v-model="form.txt_num_requisition"
                                                    type="text" 
                                                    id="Num_requisition" 
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>

                                            <div>
                                                <label for="Surface_bornage" class="block font-medium text-primary-txt">Surfacce au bornage</label> 
                                                <input
                                                    v-model="form.txt_surface_bornage"
                                                    type="text" 
                                                    id="Surface_bornage" 
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>

                                            <div>
                                                <label for="Date_bornage" class="block font-medium text-primary-txt">Date Bornage</label> 
                                                <input
                                                    v-model="form.dt_date_bornage"
                                                    type="date" 
                                                    id="Date_bornage"
                                                    autocomplete="address-level2"
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>
                                            
                                            <div v-if="showMorcellement">
                                                <label for="txt_appartement" class="block  font-medium font-medium text-primary-txt">
                                                    Appartement
                                                </label> 
                                                <input
                                                    type="text" 
                                                    v-model="txt_appartement"
                                                    id="txt_appartement"
                                                    autocomplete="off"
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    @input="validateInput"
                                                    maxlength="3"
                                                    minlength="3"
                                                />
                                                <p v-if="errorMessage" class="text-red-500 text-sm mt-1 font-medium text-gray-900">{{ errorMessage }}</p>
                                            </div>

                                            <div>
                                                <label for="Nom_geometre" class="block font-medium text-primary-txt">Nom Geometre</label> 
                                                <input
                                                    v-model="form.txt_nom_geometre"
                                                    type="text" 
                                                    id="Nom_geometre"
                                                    autocomplete="address-level2"
                                                    class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                /> 
                                            </div>
    
                                        </div>
                                    </div>
                                    
                                    <br>
                                  
                                    <h5 class="text-lg font-bold">
                                        Identité Titulaire de droit
                                    </h5><br>

                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">  
                                      <div class="sm:col-span-2">
                                        <label for="slt_titulaire" class="block font-medium text-primary-txt">Titulaire </label> 
                                        <select
                                            v-model="form.slt_titulaire" 
                                            id="slt_titulaire" 
                                            class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                            @change="handleSelectChangeTitulaire"
                                        >
                                            <option selected desabled></option>
                                            <option value="Personne Physique">Personne Physique</option>
                                            <option value="Personne Morale">Personne Morale</option>
                                            <option value="Etat">Etat</option>  
                                        </select> 
                                        <div v-if="form.errors.slt_titulaire" class="text-red-500">{{ form.errors.slt_titulaire }}</div>
                                      </div>
                                      
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_nationalite" class="block font-medium text-primary-txt">Nationalite</label>
                                        <input v-model="form.txt_nationalite" id="txt_nationalite" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_nationalite" class="text-red-500">{{ form.errors.txt_nationalite }}</div>
                                      </div>
                                      
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="slt_civilite" class="block font-medium text-peimary-txt">Civilite</label>
                                        <input v-model="form.slt_civilite" id="slt_civilite" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.slt_civilite" class="text-red-500">{{ form.errors.slt_civilite }}</div>
                                      </div>
                                      
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_prenom" class="block font-medium text-primary-txt">Prénom</label>
                                        <input v-model="form.txt_prenom" id="txt_prenom" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_prenom" class="text-red-500">{{ form.errors.txt_prenom }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_nom" class="block font-medium text-primary-txt">Nom</label>
                                        <input v-model="form.txt_nom" id="txt_nom" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_nom" class="text-red-500">{{ form.errors.txt_nom }}</div>
                                      </div> 
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="slt_piece" class="block font-medium text-primary-txt">Pièce</label> 
                                        <select
                                            v-model="form.slt_piece" 
                                            id="slt_piece" 
                                            class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                        >
                                            <option selected desabled></option>
                                            <option value="CNI">CNI</option>
                                            <option value="Passeport">Passeport</option>
                                            <option value="Etat">Etat</option> 
                                        </select> 
                                        <div v-if="form.errors.slt_piece" class="text-red-500">{{ form.errors.slt_piece }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_numPiece" class="block font-medium text-primary-txt">N° Pièce</label>
                                        <input v-model="form.txt_numPiece" id="txt_numPiece" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_numPiece" class="text-red-500">{{ form.errors.txt_numPiece }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="dt_date_delivrance" class="block font-medium text-primary-txt">Date Délivrance</label>
                                        <input v-model="form.dt_date_delivrance" id="dt_date_delivrance" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.dt_date_delivrance" class="text-red-500">{{ form.errors.dt_date_delivrance }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="dt_date_naissance" class="block font-medium text-primary-txt">Date Naissance</label>
                                        <input v-model="form.dt_date_naissance" id="dt_date_naissance" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.dt_date_naissance" class="text-red-500">{{ form.errors.dt_date_naissance }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_lieu_naissance" class="block font-medium text-primary-txt">Lieu Naissance</label>
                                        <input v-model="form.txt_lieu_naissance" id="txt_lieu_naissance" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_lieu_naissance" class="text-red-500">{{ form.errors.txt_lieu_naissance }}</div>
                                      </div>
                                                                    
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_adresse" class="block font-medium text-primary-txt">Adresse</label>
                                        <input v-model="form.txt_adresse" id="txt_adresse" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_adresse" class="text-red-500">{{ form.errors.txt_adresse }}</div>
                                      </div>
                                                                                                  
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="tel_telephone" class="block font-medium text-primary-txt">Telephone</label>
                                        <input v-model="form.tel_telephone" id="tel_telephone" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.tel_telephone" class="text-red-500">{{ form.errors.tel_telephone }}</div>
                                      </div> 
                                                                                                  
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="eml_email" class="block font-medium text-primary-txt">Email</label>
                                        <input v-model="form.eml_email" id="eml_email" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.eml_email" class="text-red-500">{{ form.errors.eml_email }}</div>
                                      </div>
                                                                                                  
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="txt_representant" class="block font-medium text-primary-txt">Representant</label>
                                        <input v-model="form.txt_representant" id="txt_representant" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.txt_representant" class="text-red-500">{{ form.errors.txt_representant }}</div>
                                      </div>
                                                                                                  
                                      <div v-if="showSectionPP" class="sm:col-span-1">
                                        <label for="tel_telRepresentant" class="block font-medium text-primary-txt">Tel Representant</label>
                                        <input v-model="form.tel_telRepresentant" id="tel_telRepresentant" type="text" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"/> 
                                        <div v-if="form.errors.tel_telRepresentant" class="text-red-500">{{ form.errors.tel_telRepresentant }}</div>
                                      </div>
                                    
                                      <!-- Personne Morale -->
                                            <div v-if="showSectionPM">
                                              <label for="slt_categoriePM" class="block font-medium text-primary-txt" >Catégorie</label>
                                              <select name="slt_categoriePM" v-model="form.slt_categoriePM" id="selectePiece"
                                                class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              >
                                                <option  value=""> </option>
                                                <option value="Station">Station</option>
                                                <option value="Industrue">Industrue</option>
                                                <option value="Restaurant">Restaurant</option>
                                                <option value="Hotel">Hotel</option>
                                                <option value="Hoberge">Hoberge</option>
                                                <option value="Elevage">Elevage</option>
                                                <option value="Agrobusness">Agrobusness</option>
                                                <option value="Epicerie">Epicerie</option>
                                                <option value="Alimentation Générale">Alimentation Générale</option>
                                                <option value="Qincaillerie">Qincaillerie</option>
                                                <option value="Transport">Transport</option>
                                                <option value="Coopérative Habitat">Coopérative Habitat</option>
                                                <option value="BTP">BTP</option>
                                                <option value="Organisation Religieux">Organisation Religieux</option>
                                              </select>
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_formJuridiquePM" class="block text-sm/6 font-medium text-primary-txt">Forme Juridique</label> 
                                              <input type="text" name="txt_formJuridiquePM"
                                                  v-model="form.txt_formJuridiquePM" id="txt_formJuridiquePM" autocomplete="address-level2" 
                                                  class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_denominationPM" class="block text-sm/6 font-medium text-primary-txt">Dénomination</label>
                                              <input type="text" name="txt_denominationPM" v-model="form.txt_denominationPM"id="txt_denominationPM"
                                                  autocomplete="address-level2" class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_nineaPM" class="block text-sm/6 font-medium text-primary-txt" >Ninea</label>
                                              <input type="text" name="txt_nineaPM"
                                                  v-model="form.txt_nineaPM" id="txt_nineaPM" autocomplete="address-level2"
                                                  class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_perssonneRepresentantPM" class="block text-sm/6 font-medium text-primary-txt">Nom.Personne Responsable</label> 
                                              <input type="text" name="txt_perssonneRepresentantPM" v-model="form.txt_perssonneRepresentantPM "
                                                  id="txt_perssonneRepresentantPM" autocomplete="address-level2"
                                                  class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_fonctionPM" class="block text-sm/6 font-medium text-primary-txt">Fonction Responsable</label> 
                                              <input type="text" name="txt_fonctionPM" v-model=" form.txt_fonctionPM"
                                                  id="txt_fonctionPM" autocomplete="address-level2"
                                                  class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_telephonePM" class="block text-sm/6 font-medium text-primary-txt">Téléphone Responsable</label> 
                                              <input type="text" name="txt_telephonePM"
                                                  v-model="form.txt_telephonePM" id="txt_telephonePM" autocomplete="address-level2"
                                                  class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                              /> 
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                                <label for="txt_emailPM" class="block text-sm/6 font-medium text-primary-txt">Email</label>
                                                <div class="mt-2">
                                                    <input type="email" name="txt_emailPM" v-model="form.txt_emailPM" id="txt_emailPM"
                                                        autocomplete="address-level2" class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                    />
                                                </div>
                                          </div>
                                          <div v-if="showSectionPM" class="sm:col-span-1">
                                              <label for="txt_adressePM" class="block text-sm/6 font-medium text-primary-txt" >Adresse Sciège </label>
                                              <div class="mt-2">
                                                  <input type="text" name="txt_adressePM" v-model="form.txt_adressePM" id="txt_adressePM" 
                                                  autocomplete="address-level2" class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                  />
                                              </div>
                                          </div> 

                                          <!-- Etat -->
                                          <div v-if="showSectionPA" class="sm:col-span-1">
                                                <label for="slt_etablissementPA"
                                                  class="block text-sm/6 font-medium text-primary-txt">Etablissement</label> 
                                                <select name="slt_etablissementPA" v-model="form.slt_etablissementPA" 
                                                    id="selectePiece" class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                  >Ministère Tutelle</label
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
                                        </div>

                                      <!--    References usages -->
                                      
                                    <h5 class="text-lg font-bold mt-8">
                                        Réferences Usage
                                    </h5><br>

                                    <div  class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"> 
                                      
                                      <div>
                                        <label for="slt_usage" class="block font-medium text-primary-txt">Usage</label>
                                        <select 
                                  
                                          v-model="form.slt_usage"  
                                          id="slt_usage" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline-2 focus:outline-primary sm:text-sm/6"> 
                                            <option value="" disabled></option>
                                            <option value="Habitation">Habitation</option>
                                            <option value="Commercial">Commercial</option>
                                            <option value="Agricole">Agricole</option>
                                            <option value="Industriel">Industriel</option>
                                            <option value="Professionnelle">Professionnelle</option>
                                            <option value="Culte">Culte</option>
                                            <option value="Administratif">Administratif</option>
                                          </select>
                                        <div v-if="form.errors?.slt_usage" class="text-red-500">{{ form.errors.slt_usage }}</div>
                                      </div>

                                      <div>
                                        <label for="slt_residence" class="block font-medium text-primary-txt">Type de résidence</label>
                                        <select 
                                          v-model="form.slt_residence" 
                                          id="slt_residence" 
                                          class="h-8 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                          outline outline-1 outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                          focus:outline-2 focus:outline-primary sm:text-sm/6">

                                          <option selected disabled></option>
                                          <option value="Residence Principal">Residence Principal</option>
                                          <option value="Residence Secondaire">Residence Secondaire</option>
                                        </select>
                                        <div v-if="form.errors?.slt_residence" class="text-red-500">{{ form.errors.slt_residence }}</div>
                                      </div>
                                    </div><br>
        
                                    <div  class="w-full flex flex-wrap justify-between gap-4 mt-4"> 
                                      <TransitionGroup  name="slide-fade" v-if="form.occupants.length" tag="div">
                                        
                                        <div 
                                          v-for="(occupant, i) in form.occupants" :key="i" class="sm:col-span-8"> 
                                            <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                          
                                                <div class="sm:col-span-1">
                                                    <label class="block  font-medium text-primary-txt">Nom Occupant {{ i }}</label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_nomOccupantTG"
                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'NunAppartement_${i}'" class="block font-medium text-primary-txt">N° Appart</label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_numAppartementTG" 
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'Activite_${i}'" class="block font-medium text-primary-txt">Activite</label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_activiteTG"
                                                    :id="'Activite_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'Ninea_${i}'" class="blockfont-medium text-primary-txt">Ninea </label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_nineaTG"
                                                    :id="'Ninea_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'Telephone_${i}'" class="block font-medium text-primary-txt">Téléphone </label>
                                                    <input type="tel" 
                                                    v-model="occupant.tel_telephoneTG"
                                                    :id="'Telephone_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'Montant_loyer_${i}'" class="block font-medium text-primary-txt">Mnt Loyer</label>
                                                    <input type="number" 
                                                    v-model="occupant.nbr_montantLoyerTG"
                                                    :id="'Montant_loyer_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'DateNaissnace_${i}'" class="block font-medium text-primary-txt">Date-lieu Nais</label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_dateLieuNaissanceTG"
                                                    :id="'DateNaissnace_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'CNIPasseport_${i}'" class="block font-medium text-primary-txt">CNI/Pasprt</label>
                                                    <input type="text" 
                                                    v-model="occupant.txt_cniPasseportTG"
                                                    :id="'CNIPasseport_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                                <div class="sm:col-span-1">
                                                    <label :for="'DateDelivrance_${i}'" class="block font-medium text-primary-txt">Date Déliv</label>
                                                    <input type="date" 
                                                    v-model="occupant.dt_dateDelivranceTG"
                                                    :id="'DateDelivrance_${i}'"  
                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                </div>

                                              </div>
                                              
                                        </div>

                                      </TransitionGroup> 

                                        <!-- Boutons -->
                                      <div class="flex gap-4 mt-6">
                                        <button @click="addBlock" type="button"
                                          class="h-8 text-white bg-primary hover:bg-primary-dark font-medium rounded-lg text-sm px-5 py-2.5">
                                          Ajouter
                                        </button>

                                        <button @click="removeBlock" type="button"
                                          class="h-8 text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2.5">
                                          Retirer
                                        </button>
                                      </div>
                                        
                                    </div><br><br>
                                    <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                      <div class="sm:col-span-2">
                                          <label for="MontantLoyerTotal" class="block font-medium text-primary-txt">Montant Loyer Total</label>
                                          <div>
                                              <input type="number" 
                                              v-model="form.nbr_montantLoyerTotal" 
                                              readonly
                                              class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          </div>
                                      </div>

                                      <div class="sm:col-span-2">
                                          <label for="TVATotal" class="block font-medium text-primary-txt">TVA Totale</label>
                                          <div>
                                              <input type="number" 
                                              :value="form.nbr_TVATotal"
                                              readonly
                                              class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                          </div>
                                      </div>
                                    </div>
          
                                  </div>

                                  <h5 class="text-lg font-bold mt-8">Evaluation de l'immeuble</h5><br>

                                  <div class="mb-6">
                                      <div class="border-b">
                                          <ul class="flex justify-center text-sm font-medium text-gray-500 divide-x divide-gray-200 dark:divide-gray-700">

                                              <li class="w-1/3 text-center">
                                                  <button
                                                      type="button"
                                                      @click="setActiveTab('terrain')"
                                                      :class="{
                                                          'text-primary border-b-8 border border-primary': activeTab === 'terrain',
                                                          'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'terrain'
                                                      }"
                                                      class="inline-block w-full p-4"
                                                  >
                                                      Terrain
                                                  </button>
                                              </li>

                                              <li class="w-1/3 text-center">
                                                  <button
                                                      type="button"
                                                      @click="setActiveTab('bati')"
                                                      :class="{
                                                          'text-primary border-b-8 border border-primary': activeTab === 'bati',
                                                          'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'bati'
                                                      }"
                                                      class="inline-block w-full p-4"
                                                  >
                                                      Bâti
                                                  </button>
                                              </li>

                                              <li class="w-1/3 text-center">
                                                  <button
                                                      type="button"
                                                      @click="setActiveTab('Cours_amenagees')"
                                                      :class="{
                                                          'text-primary border-b-8 border border-primary': activeTab === 'Cours_amenagees',
                                                          'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'Cours_amenagees'
                                                      }"
                                                      class="inline-block w-full p-4"
                                                  >
                                                      Cours aménagées
                                                  </button>
                                              </li>

                                              <li class="w-1/3 text-center">
                                                  <button
                                                      type="button"
                                                      @click="setActiveTab('cloture')"
                                                      :class="{
                                                          'text-primary border-b-8 border border-primary': activeTab === 'cloture',
                                                          'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'cloture'
                                                      }"
                                                      class="inline-block w-full p-4"
                                                  >
                                                      Clôtures
                                                  </button>
                                              </li>
                                              
                                              <li class="w-1/3 text-center">
                                                  <button
                                                      type="button"
                                                      @click="setActiveTab('amenagement')"
                                                      :class="{
                                                          'text-primary border-b-8 border border-primary': activeTab === 'amenagement',
                                                          'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'amenagement'
                                                      }"
                                                      class="inline-block w-full p-4"
                                                  >
                                                      Aménagements particuliers
                                                  </button>
                                              </li>

                                          </ul>
                                      </div>


                                      <!--     TERRAIN    --> 
                                      <div class="p-6">
                                          <div v-show="activeTab === 'terrain'" class="sgrid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                              <div class="mb-4">
                                                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                                      <div class="sm:col-span-2">
                                                          <div class="mt-6">   
                                                              <label for="Superficie_totale" class="block font-medium text-primary-txt">Superficie Totale Terrain</label>
                                                              <div>
                                                                  <input 
                                                                      type="number"  
                                                                      readonly
                                                                      v-model="form.txt_superficie_totale"  
                                                                      id="txt_superficie_totale" 
                                                                      class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                              </div>
                                                          </div>
                                                      </div>
                                                  
                                                      <div class="sm:col-span-2">
                                                          <div class="mt-6">
                                                              <label for="Superficie_bati_sol" class="block font-medium text-primary-txt">Superficie Bâti au sol</label>
                                                              <div>
                                                                  <input 
                                                                  type="number" 
                                                                  v-model="form.txt_superficie_bati_sol"   
                                                                  id="Superficie_bati_sol" 
                                                                  min="0"
                                                                  :max="form.nbr_surface" 
                                                                  class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                              </div>
                                                          </div>
                                                      </div>

                                                      <div class="sm:col-span-2">
                                                        <div class="mt-6">
                                                            <label for="Superficie_non_bati" class="block text-sm/6 font-medium text-primary-txt">Superficie Non Bâti</label>
                                                            <div>
                                                                <input 
                                                                type="number" 
                                                                v-model="form.txt_superficie_non_bati"  
                                                                readonly
                                                                name="Superficie_non_bati"
                                                                id="Superficie_non_bati" 
                                                                :min="0" 
                                                                class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                            </div>
                                                        </div>
                                                    </div>
                                              
                                                      <div class="sm:col-span-2">
                                                          <div class="mt-6">
                                                              <label for="Secteur" class="block font-medium text-primary-txt">Secteur</label>
                                                              <div> 
                                                                  <select type="select" 
                                                                  v-model="form.slt_secteur" 
                                                                  id="slt_secteur"  
                                                                  class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                      <option selected desabled></option>
                                                                      <option :value="1">1</option>
                                                                      <option :value="2">2</option>
                                                                      <option :value="3">3</option>
                                                                  </select>
                                                              </div>
                                                          </div>
                                                      </div>  
                                                  
                                                      <div class="sm:col-span-2">
                                                          <div class="mt-6">
                                                              <label for="Prix_metre_carre" class="block font-medium text-primary-txt">Prix mètre Carré</label>
                                                              <div>
                                                                  <input 
                                                                  type="number" 
                                                                  v-model="form.nbr_prix_metre_carre"  
                                                                  id="nbr_prix_metre_carre"
                                                                  readonly 
                                                                  class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                              </div>
                                                          </div>
                                                      </div>   
                                                      <div class="sm:col-span-2">
                                                          <div class="mt-6"> 
                                                              <label for="Valeur_terrain" class="block font-medium text-primary-txt">Valeur du Terrain</label>
                                                              <div>
                                                                  <input type="number"
                                                                  v-model="form.nbr_valeur_terrain"  
                                                                  step="0.01"
                                                                  @blur="arrondirDeuxDecimales(nbr_valeur_terrain)" 
                                                                  readonly 
                                                                  class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                              </div>
                                                          </div>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>

                                          <!--   BATI -->
                                          <div v-show="activeTab === 'bati'" class="space-y-4">
                                              <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8 items-center">
                                                  <!-- Boutons radio -->
                                                  <div class="sm:col-span-2">
                                                      <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                                          <MazRadio v-model="form.currentCat" 
                                                            class="block font-medium text-primary-txt"
                                                            value="Maison individuelle" 
                                                            label="Maison individuelle" />
                                                      </div>
                                                  </div>
                                                  <div class="sm:col-span-2">
                                                      <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                                          <MazRadio v-model="form.currentCat" 
                                                            class="block font-medium text-primary-txt"
                                                            value="Immeuble collectif" 
                                                            label="Immeuble collectif" />
                                                      </div>
                                                  </div>
                                                  <br/>    
                                              </div><br>
                                              <!-- Batiment  Princil-->
                                              <h6 class="text-lg text-primary-txt text-center font-bold">Bâtiment(s) Principal(aux)</h6>

                                              <div class="w-full flex flex-wrap justify-between gap-4 mt-8">

                                                  <TransitionGroup >
                                                      <div 
                                                          class="sm:col-span-8">
                                                          <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                                              <div class="sm:col-span-1">
                                                                  <label for="Dependant_domaine" class="block font-medium text-primary-txt">Bâtiment </label>
                                                                      <div>   
                                                                          <input 
                                                                          v-model="form.txt_dependant_domainePR" 
                                                                          name="txt_dependant_domainePR" 
                                                                          readonly 
                                                                          class="h-8 block w-36 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"> 
                                                                      </input>
                                                                  </div>
                                                              </div>

                                                              <div  class="sm:col-span-2">
                                                                  <div v-if="form.currentCat === 'Maison individuelle'" class="sm:col-span-1">
                                                                      <!-- Vous avez sélectionné une catégorie de type Maison individuelle. -->
                                                                      <label for="slt_categoriePR" class="block font-medium text-primary-txt">CAT</label>
                                                                      <div>   
                                                                          <select 
                                                                          v-model="form.slt_categoriePR"  
                                                                          id="slt_categoriePR"
                                                                          class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected desablaled></option> 
                                                                              <option value="1">1</option>
                                                                              <option value="2">2</option>    
                                                                              <option value="3">3</option>
                                                                              <option value="4">4</option>
                                                                              <option value="5">5</option>
                                                                              <option value="6">6</option>
                                                                              <option value="7">7</option>
                                                                              <option value="8">8</option>
                                                                              <option value="9">9</option>
                                                                              <option value="10">10</option>
                                                                              <option value="11">11</option>  
                                                                          </select>
                                                                      </div>
                                                                  </div>
                                                                  <div v-else-if="form.currentCat === 'Immeuble collectif'" class="block text-sm/6 font-medium text-gray-900">
                                                                      <!-- Vous avez sélectionné une autre catégorie. -->
                                                                      <label for="slt_categoriePR" class="block font-medium text-primary-txt">CAT</label>
                                                                      <select 
                                                                      v-model="form.slt_categoriePR" 
                                                                      id="slt_categoriePR" 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                          <option selected desablaled></option>  
                                                                          <option value="A">A</option>   
                                                                          <option value="B">B</option>  
                                                                          <option value="C">C</option>
                                                                          <option value="D">D</option>
                                                                          <option value="E">E</option>
                                                                          <option value="F">F</option>
                                                                          <option value="G">G</option>
                                                                          <option value="H">H</option>
                                                                          <option value="I">I</option>
                                                                          <option value="J">J</option>
                                                                          <option value="K">K</option>
                                                                          <option value="L">L</option>
                                                                          <option value="M">M</option> 
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                            
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="Prix_mettre_carre" class="block font-medium text-primary-txt">Prix m²</label>
                                                                  <div>  
                                                                      <input 
                                                                      v-model="form.nbr_prix_metre_carrePR"  
                                                                      readonly
                                                                      type="number"  
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>  
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_bati_sol" class="block font-medium text-primary-txt">Surf B S</label>
                                                                  <div>
                                                                      <input  
                                                                      v-model="form.nbr_surface_bati_solPR" 
                                                                      type="number"  
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1">  
                                                                  <label for="nbr_niveauPR" class="block font-medium text-primary-txt">Niveau</label>
                                                                  <div>
                                                                      <input  
                                                                      v-model="form.nbr_niveauPR" 
                                                                      type="number"  
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_utile" class="block font-medium text-primary-txt">Surf Utile</label>
                                                                  <div>   
                                                                      <input  
                                                                      v-model="form.nbr_surface_utilePR"  
                                                                      type="number"  
                                                                      readonly
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="slt_coeffPR" class="block font-medium text-primary-txt">Coeff</label>
                                                                  <div>  
                                                                      <select  
                                                                        v-model="form.slt_coeffPR"
                                                                        type="number"
                                                                        id="slt_coeffPR"
                                                                        class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                                      >
                                                                        <option  selected desabled></option>
                                                                        <option value="0.10">0.1</option>
                                                                        <option value="0.20">0.2</option>
                                                                        <option value="0.30">0.3</option>
                                                                        <option value="0.40">0.4</option>
                                                                        <option value="0.50">0.5</option>
                                                                        <option value="0.60">0.6</option>
                                                                        <option value="0.70">0.7</option>
                                                                        <option value="0.80">0.8</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_corriger" class="block font-medium text-primary-txt">S.Corrigee</label>
                                                                  <div> 
                                                                      <input                                               
                                                                      v-model="form.nbr_surface_corrigerPR" 
                                                                      type="number"  
                                                                      readonly
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="Valeur" class="block font-medium text-primary-txt">Valeur</label>
                                                                  <div>
                                                                      <input 
                                                                      type="number" 
                                                                      v-model="form.nbr_valeurPR" 
                                                                      step="0.01"
                                                                      @blur="() => arrondirDeuxDecimales(nbr_valeurPR)"
                                                                      readonly 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                          </div>
                                                      </div>
                                                  </TransitionGroup> 
                                              </div>  
                                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                  <TransitionGroup  name="slide-fade" tag="div">
                                                      <div 
                                                          v-for="(occupant, i) in form.occupantsBP" 
                                                          :key="occupant.id" 
                                                          class="sm:col-span-8">
                                                          <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                                              <div class="sm:col-span-1">
                                                                  <label for="slt_dependant_domaineTG" class="block font-medium text-primary-txt">Bâtiment </label>
                                                                      <div>   
                                                                          <select 
                                                                          v-model="occupant.slt_dependant_domaineTG"  
                                                                          id="slt_dependant_domaineTG"
                                                                          type="select"
                                                                          class="h-8 block w-36 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"> 
                                                                              <option value="Secondaire">Secondaire</option>
                                                                              <option value="Tertiaire">Tertiaire</option>
                                                                              <option value="Dépendance">Dépendance</option>
                                                                              <option value="Partie Saillante">Partie Saillante</option>
                                                                          </select>
                                                                      </div>
                                                                </div>
                                                              <div  class="sm:col-span-2">
                                                                  <div v-if="form.currentCat === 'Maison individuelle'" class="sm:col-span-1">
                                                                      <!-- Vous avez sélectionné une catégorie de type Maison individuelle. -->
                                                                      <label for="slt_categorieTG" class="block font-medium text-primary-txt">CAT</label>
                                                                      <div>   
                                                                          <select 
                                                                          v-model="occupant.slt_categorieTG" 
                                                                          type="select"
                                                                          id="slt_categorieTG"
                                                                          class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                                  outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                                  focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected desable></option>
                                                                              <option value="1">1</option>
                                                                              <option value="2">2</option>    
                                                                              <option value="3">3</option>
                                                                              <option value="4">4</option>
                                                                              <option value="5">5</option>
                                                                              <option value="6">6</option>
                                                                              <option value="7">7</option>
                                                                              <option value="8">8</option>
                                                                              <option value="9">9</option>
                                                                              <option value="10">10</option>
                                                                              <option value="11">11</option> 
                                                                          </select>
                                                                      </div>
                                                                  </div>
                                                                  <div v-else-if="form.currentCat === 'Immeuble collectif'" class="block font-medium text-gray-900">
                                                                      <!-- Vous avez sélectionné une autre catégorie. -->
                                                                      <label for="slt_categorieTG" class="block font-medium text-primary-txt">CAT</label>
                                                                      <select 
                                                                      v-model="occupant.slt_categorieTG"
                                                                      type="select"
                                                                      id="slt_categorieTG"
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                          <option selected desable></option>
                                                                          <option value="A">A</option>   
                                                                          <option value="B">B</option>  
                                                                          <option value="C">C</option>
                                                                          <option value="D">D</option>
                                                                          <option value="E">E</option>
                                                                          <option value="F">F</option>
                                                                          <option value="G">G</option>
                                                                          <option value="H">H</option>
                                                                          <option value="I">I</option>
                                                                          <option value="J">J</option>
                                                                          <option value="K">K</option>
                                                                          <option value="L">L</option>
                                                                          <option value="M">M</option> 
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="Prix_mettre_carre" class="block font-medium text-primary-txt">Prix m²</label>
                                                                  <div>  
                                                                      <input 
                                                                      v-model="occupant.nbr_prix_metre_carreTG" 
                                                                      readonly
                                                                      type="number" 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>  
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_bati_sol" class="block font-medium text-primary-txt">Surf B S</label>
                                                                  <div>
                                                                      <input  
                                                                      v-model="occupant.nbr_surface_bati_solTG"
                                                                      type="number" 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1">  
                                                                  <label for="Niveau" class="block font-medium text-primary-txt">Niveau</label>
                                                                  <div>
                                                                      <input  
                                                                      v-model="occupant.nbr_niveauTG"
                                                                      type="number" 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_utile" class="block font-medium text-primary-txt">Surf Utile</label>
                                                                  <div>   
                                                                      <input  
                                                                      v-model="occupant.nbr_surface_utileTG"
                                                                      type="number" 
                                                                      readonly
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="Coeff" class="block font-medium text-primary-txt">Coeff</label>
                                                                  <div>  
                                                                      <select  
                                                                      v-model="occupant.slt_coeffTG" 
                                                                      type="select"
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                          <option disabled value=""></option>
                                                                          <option value="0.10">0.1</option>
                                                                          <option value="0.20">0.2</option>
                                                                          <option value="0.30">0.3</option>
                                                                          <option value="0.40">0.4</option>
                                                                          <option value="0.50">0.5</option>
                                                                          <option value="0.60">0.6</option>
                                                                          <option value="0.70">0.7</option>
                                                                          <option value="0.80">0.8</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1"> 
                                                                  <label for="Surface_corriger" class="block font-medium text-primary-txt">S.Corrigee</label>
                                                                  <div> 
                                                                      <input                                               
                                                                      v-model="occupant.nbr_surface_corrigerTG" 
                                                                      type="number"  
                                                                      readonly
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                  <label for="Valeur" class="block font-medium text-primary-txt">Valeur</label>
                                                                  <div>
                                                                      <input 
                                                                      type="number" 
                                                                      v-model="occupant.nbr_valeurTG" 
                                                                      step="0.01"
                                                                      @blur="arrondirOccupantValeur(occupant)"
                                                                      readonly 
                                                                      class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                          outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                          focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                          </div>
                                                      </div>
                                                  </TransitionGroup>
                                                  <div class="flex mt-6 gap-2">
                                                      <div>
                                                          <button  
                                                              @click="addBlockBP"
                                                              type="button"
                                                              class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                              to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                              focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                              dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                              py-2.5 text-center">
                                                              Ajouter
                                                          </button>
                                                      </div>
                                                      <div>
                                                          <button 
                                                              @click="removeBlockBP"
                                                              type="button"
                                                              class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                              to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                              focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                              dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                              py-2.5 text-center">
                                                              Retirer
                                                          </button>
                                                      </div>
                                                  </div>
                                              </div>  
                                              <div  class="flex justify-center gap-6 mb-8">
                                                  <div> 
                                                      <label for="Valeur_terrain_bati" class="block font-medium text-primary-txt">Valeur(s) bâtiment (s)</label>
                                                      <input 
                                                          type="number" 
                                                          :value="txt_valeur_terrain_bati"
                                                          step="0.01"
                                                          @blur="() => arrondirDeuxDecimales(txt_valeur_terrain_bati)"
                                                          readonly
                                                          id="Valeur_terrain_bati"
                                                          class="block w-full rounded-md bg-white 
                                                              px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                              outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                              focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                  </div>
                                              </div>
                                          </div>

                                          <!-- Cours AMENAGEES-->
                                          <div v-show="activeTab === 'Cours_amenagees'" class="space-y-4">

                                              <!-- Cour Aménager Totale -->

                                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4"> 
                                                  <TransitionGroup  name="slide-fade" tag="div"> 
                                                      <div 
                                                          v-for="(occupant, i) in form.occupantsCA" 
                                                          :key="occupant.id" 
                                                          class="sm:col-span-8"> 
                                                          <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"> 
                                                              <div class="sm:col-span-1">
                                                                      <label for="slt_cours_amenager_totale" class="block font-medium text-primary-txt">Cours Aménagées Totale</label>
                                                                  <div>   
                                                                      <select 
                                                                      v-model="occupant.slt_cours_amenager_totale"  
                                                                      id="slt_cours_amenager_totale" 
                                                                      class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                          <option selected desable></option>
                                                                          <option value="Cours Amenagées Totale">Cours Amenagées Totale</option>
                                                                          <option value="Cours Avant">Cours Avant</option> 
                                                                          <option value="Cours Arrière">Cours Arrière</option>
                                                                          <option value="Cours Latérale Droite">Cours Latérale Droite</option>
                                                                          <option value="Cours Latérale Gauche">Cours Latérale Gauche</option>
                                                                          <option value="Cours Restante">Cours Restante</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1">
                                                                      <label for="nbr_surface_ca_total" class="block font-medium text-primary-txt">Surface</label>
                                                                  <div> 
                                                                      <input type="number"
                                                                      v-model="occupant.nbr_surface_ca_total" 
                                                                      id="nbr_surface_ca_total"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                  </div>
                                                              </div>  
                                                              <div class="sm:col-span-1">
                                                                      <label for="slt_categorie_ca_total" class="block font-medium text-primary-txt">Catégorie</label>
                                                                  <div> 
                                                                      <select type="select" 
                                                                      v-model="occupant.slt_categorie_ca_total"  
                                                                      id="slt_categorie_ca_total"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected sesabled></option>
                                                                              <option :value="1">1</option>
                                                                              <option :value="2">2</option>
                                                                              <option :value="3">3</option>
                                                                              <option :value="4" >4</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1">
                                                                      <label for="nbr_prix_metre_carre_ca_total" class="block font-medium text-primary-txt">P/m²</label>
                                                                  <div> 
                                                                      <input type="number" 
                                                                      v-model="occupant.nbr_prix_metre_carre_ca_total"  
                                                                      readonly 
                                                                      id="nbr_prix_metre_carre_ca_total"
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1">
                                                                      <label for="nbr_coefficient_ca_total" class="block font-medium text-primary-txt">Coefficient</label>
                                                                  <div> 
                                                                      <select type="select" 
                                                                      v-model="occupant.nbr_coefficient_ca_total"  
                                                                      id="nbr_coefficient_ca_total"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected desabled></option>
                                                                              <option value="0.10">0.1</option>
                                                                              <option value="0.20">0.2</option>
                                                                              <option value="0.30">0.3</option>
                                                                              <option value="0.40">0.4</option>
                                                                              <option value="0.50">0.5</option>
                                                                              <option value="0.60">0.6</option>
                                                                              <option value="0.70">0.7</option>
                                                                              <option value="0.80">0.8</option>
                                                                      </select>
                                                                  </div>
                                                              </div> 
                                                              <div class="sm:col-span-1">
                                                                      <label for="Valeur_ca_total" class="block font-medium text-primary-txt">Valeur</label>
                                                                  <div> 
                                                                      <input type="number" 
                                                                      v-model="occupant.nbr_valeur_ca_total" 
                                                                      step="0.01"
                                                                      @blur="() => arrondirDeuxDecimales(occupant)"
                                                                      readonly 
                                                                      id="Valeur_ca_total"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                          </div>
                                                      </div> 
                                                  </TransitionGroup>  
                                                  <div class="flex gap-4 mt-6">
                                                      <button  
                                                          @click="addBlockCA"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Ajouter
                                                      </button>
                                                      
                                                      <button 
                                                          @click="removeBlockCA"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Retirer
                                                      </button>
                                                  </div>
                                              </div> 
                                              <div  class="flex justify-center gap-6 mb-8">
                                                  <div> 
                                                      <label for="nbr_valeur_total_ca" class="block font-medium text-primary-txt">Valeur Total des Cours</label>
                                                      <input type="number" 
                                                      v-model="form.nbr_valeur_total_ca"
                                                      step="0.01" 
                                                      @blur="() => arrondirDeuxDecimales(nbr_valeur_total_ca)"
                                                      readonly
                                                      id="nbr_valeur_total_ca"  class="block w-full rounded-md bg-white 
                                                          px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                          outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                          focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                  </div>
                                              </div> 
                                          </div>

                                          <!-- CLOTURES -->

                                          <div v-show="activeTab === 'cloture'" class="space-y-4">
                                              <!-- <label for="Designation">Designation</label> -->

                                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                  <TransitionGroup  name="slide-fade" tag="div"> 
                                                      <div 
                                                          v-for="(occupant, i) in form.occupantsCL" 
                                                          :key="occupant.id" 
                                                          class="sm:col-span-8"> 
                                                          <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                              <div class="sm:col-span-1">
                                                                      <label for="slt_cours_amenager_clo" class="block font-medium text-primary-txt">Clôtures</label>
                                                                  <div>   
                                                                      <select 
                                                                      v-model="occupant.slt_cours_amenager_clo"  
                                                                      id="slt_cours_amenager_clo" 
                                                                      class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                          <option selected desable></option>
                                                                          <option value="Longueur Totale">Longueur Totale</option>
                                                                          <option value="Longueur Avant">Longueur Avant</option> 
                                                                          <option value="Longueur Arrière">Longueur Arrière</option>
                                                                          <option value="Longueur Latérale Droite">Longueur Latérale Droite</option>
                                                                          <option value="Longueur Latérale Gauche">Longueur Latérale Gauche</option>
                                                                          <option value="Longueur Restante">Longueur Restante</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1">
                                                                      <label for="nbr_longueur_avant_clo" class="block font-medium text-primary-txt">Linéaire</label>
                                                                  <div>  
                                                                      <input type="number" 
                                                                      v-model="occupant.nbr_longueur_avant_clo"  
                                                                      id="nbr_longueur_avant_clo"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                  </div>
                                                              </div>  
                                                              <div  class="sm:col-span-1">
                                                                      <label for="slt_categorie_clo" class="block font-medium text-primary-txt">Catégorie</label>
                                                                  <div>  
                                                                      <select type="select" 
                                                                      v-model="occupant.slt_categorie_clo"  
                                                                      id="slt_categorie_clo"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected sesabled></option>
                                                                              <option :value="1">1</option>
                                                                              <option :value="2">2</option>
                                                                              <option :value="3">3</option>
                                                                              <option :value="4">4</option>
                                                                              <option :value="5">5</option>
                                                                              <option :value="6">6</option>
                                                                              <option :value="7">7</option>
                                                                      </select>
                                                                  </div>
                                                              </div> 
                                                              <div  class="sm:col-span-1"> 
                                                                      <label for="nbr_prix_metre_carre_clo" class="block text-sm/6 font-medium text-primary-txt">P/mL</label>
                                                                  <div> 
                                                                      <input type="number" 
                                                                      v-model="occupant.nbr_prix_metre_carre_clo"  
                                                                      id="nbr_prix_metre_carre_clo"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                      <label for="nbr_coefficient_clo" class="block font-medium text-primary-txt">Coefficient</label>
                                                                  <div> 
                                                                      <select type="select"  
                                                                      v-model="occupant.nbr_coefficient_clo"  
                                                                      id="nbr_coefficient_clo"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected desabled></option>
                                                                              <option value="0.10">0.1</option>
                                                                              <option value="0.20">0.2</option>
                                                                              <option value="0.30">0.3</option>
                                                                              <option value="0.40">0.4</option>
                                                                              <option value="0.50">0.5</option>
                                                                              <option value="0.60">0.6</option>
                                                                              <option value="0.70">0.7</option>
                                                                              <option value="0.80">0.8</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div class="sm:col-span-1"> 
                                                                      <label for="nbr_valeur_clo" class="block font-medium text-primary-txt">Valeur</label>
                                                                  <div> 
                                                                      <input type="number"  
                                                                      step="0.01" 
                                                                      v-model="occupant.nbr_valeur_clo"  
                                                                      id="nbr_valeur_clo"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                          </div>
                                                      </div>
                                                      
                                                  </TransitionGroup>
                                                  <div class="flex gap-4 mt-6">
                                                      <button  
                                                          @click="addBlockCL"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Ajouter
                                                      </button>
                                                      
                                                      <button 
                                                          @click="removeBlockCL"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Retirer
                                                      </button>
                                                  </div>  
                                              </div>
                                              <div class="flex justify-center gap-6 mb-8">
                                                  <div> 
                                                      <label for="Valeur_total_clotur" class="block font-medium text-primary-txt">Valeurs Totale des Clôtures</label>
                                                      <input type="number"  
                                                          step="0.01"       
                                                          v-model="form.nbr_valeur_total_clotur" 
                                                          :step="0.01"  
                                                          @blur="() => arrondirDeuxDecimales(form.nbr_valeur_total_clotur)"
                                                          readonly
                                                          name="nbr_valeur_total_clotur" 
                                                          id="Valeur_total_clotur"  
                                                          class="block w-full rounded-md bg-white 
                                                          px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                          outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                          focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                  </div>
                                              </div>
                                          </div>

                                          <!--    AMENAGEMENTS PARTICULIERS   -->
                                          <div v-show="activeTab === 'amenagement'" class="space-y-4">
                                              <h6 class="text-lg text-center text-primary-txt font-bold">VALEUR</h6>
                                  
                                              <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                  <TransitionGroup  name="slide-fade" tag="div"> 
                                                      <div 
                                                          v-for="(occupant, i) in form.occupantsAP" 
                                                          :key="occupant.id" 
                                                          class="sm:col-span-8"> 
                                                          <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">  

                                                              <div  class="sm:col-span-1">
                                                                  <div>
                                                                      <label for="Designation_1" class="ms-2 font-medium text-primary-txt dark:text-gray-300">Designeation</label>
                                                                      <input id="Designation_1"          
                                                                      v-model="occupant.txt_designation_am" 
                                                                      type="text" 
                                                                      class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6" />
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1"> 
                                                                  <div>
                                                                      <label for="Valeur_unitaire_1" class="ms-2 font-medium text-primary-txt dark:text-gray-300">Valeur Unitaire</label>
                                                                      <input type="number"           
                                                                      v-model="occupant.nbr_valeur_unitaire_am"
                                                                      id="Valeur_unitaire_1"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                  </div>
                                                              </div>  
                                                              <div  class="sm:col-span-1">
                                                                  <div> 
                                                                      <label for="Quantile_1" class="ms-2 font-medium text-primary-txt dark:text-gray-300">Quantité</label>
                                                                      <input type="number"            
                                                                      v-model="occupant.nbr_quantile_am" 
                                                                      id="Quantile_1" 
                                                                      class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div> 
                                                              <div  class="sm:col-span-1">
                                                                  <div> 
                                                                      <label for="Coeficien_1" class="ms-2 font-medium text-primary-txt dark:text-gray-300">Coeficien</label>
                                                                      <select type="select"             
                                                                      v-model="occupant.slt_coeficien_am" 
                                                                      id="Coeficien_1"  
                                                                      class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                              <option selected desabled></option>
                                                                              <option value="0.10">0.1</option>
                                                                              <option value="0.20">0.2</option>
                                                                              <option value="0.30">0.3</option>
                                                                              <option value="0.40">0.4</option>
                                                                              <option value="0.50">0.5</option>
                                                                              <option value="0.60">0.6</option>
                                                                              <option value="0.70">0.7</option>
                                                                              <option value="0.80">0.8</option>
                                                                              <option value="0.90">0.9</option>
                                                                              <option value="1.00">1</option>
                                                                      </select>
                                                                  </div>
                                                              </div>
                                                              <div  class="sm:col-span-1">
                                                                  <div> 
                                                                      <label for="Valeur" class="ms-2 font-medium text-primary-txt dark:text-gray-300">Valeur</label>
                                                                      <input type="number"     
                                                                      inputmode="decimal"
                                                                      step="0.01"         
                                                                      v-model="occupant.nbr_valeur_am" 
                                                                      id="Valeur"  
                                                                      class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                      outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                  </div>
                                                              </div>
                                                          </div>
                                                      </div>
                                                  </TransitionGroup>
                                                  <div class="flex gap-4 mt-6">
                                                      <button  
                                                          @click="addBlockAP"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Ajouter
                                                      </button>
                                                      
                                                      <button 
                                                          @click="removeBlockAP"
                                                          type="button"
                                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                          py-2.5 text-center">
                                                          Retirer
                                                      </button>
                                                  </div>  

                                              </div>
                                              <div  class="flex justify-center gap-6 mb-8">
                                                  <div> 
                                                      <label for="valeur_totale_ap" class="block font-medium text-primary-txt">Valeur Totale des A.P</label>
                                                      <input type="number"                    
                                                          v-model="form.nbr_valeur_totale_ap" 
                                                          readonly
                                                          name="nbr_valeur_totale_ap" 
                                                          step="0.01"  
                                                          @blur="() => arrondirDeuxDecimales(form.nbr_valeur_totale_ap)"
                                                          id="valeur_totale_ap"  
                                                          class="block w-full rounded-md bg-white 
                                                          px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                          outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                          focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                  </div>
                                              </div>
                                          </div>

                                      </div>

                                  </div>
                                  <div class="mb-6">
                                      <div class="border-b">  
                                          <div class="mb-4">
                                              <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                                  <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                      <div> 
                                                          <label for="nbr_valeurVenaleLimeuble" class="block font-medium text-primary-txt">Valeur Vénale de l'immeuble</label>
                                                          <input type="number"        
                                                              v-model="form.nbr_valeurVenaleLimeuble" 
                                                              step="0.01"  
                                                              @blur="() => arrondirDeuxDecimales(form.nbr_valeurVenaleLimeuble)"
                                                              readonly 
                                                              id="nbr_valeurVenaleLimeuble"  
                                                              class="block w-64 rounded-md bg-white 
                                                              px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                              outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                              focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                      </div>
                                                  </div>
                                                  <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                      <div> 
                                                          <label for="nbr_valeurLocative" class="block font-medium text-primary-txt">Valeur Locative</label>
                                                          <input type="number"        
                                                              v-model="form.nbr_valeurLocative" 
                                                              step="0.01"
                                                               @blur="() => arrondirDeuxDecimales(form.nbr_valeurLocative)"
                                                              readonly 
                                                              id="nbr_valeurLocative"  
                                                              class="block w-64 rounded-md bg-white 
                                                              px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                              outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                              focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                      </div> 
                                                  </div>
                                                  <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                      <div> 
                                                          <label for="dt_dateEvaluation" class="block font-medium text-primary-txt">Date d'évaluation</label>
                                                          <input 
                                                              type="text" 
                                                              v-model="form.dt_dateEvaluation"   
                                                              id="dt_dateEvaluation" 
                                                              class="block w-64 rounded-md bg-white 
                                                                  px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                                  outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                                  focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                      </div>
                                                  </div>
                                                  <div class="sm:col-span-2">
                                                      <div class="sm:col-span-1">
                                                          <label for="fichierPDF" class="block font-medium text-primary-txt">Importer le Fichier PDF</label> 
                                                          <input
                                                              type="file"
                                                              name="fichierPDF"
                                                              accept=".pdf"  
                                                              id="fichierPDF" 
                                                              @change="handleFileChangePDF"
                                                              class="block w-64 rounded-md bg-white 
                                                                  px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 
                                                                  outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                                  focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                                                          /> 
                                                          <!-- ✅ Affichage le fichier enregistré -->
                                                          <div v-if="form.fichierPDF && typeof form.fichierPDF === 'string'" class="mt-2"> 
                                                              <a
                                                                  :href="`/storage/${form.fichierPDF}`"
                                                                  target="_blank"
                                                                  class="text-blue-600 underline"
                                                              >
                                                                  Voir le fichier
                                                              </a>
                                                          </div> 
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>

                                  <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn 
                                        type="submit"   
                                          class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                          to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                          focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                          dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                          py-2.5 text-center me-2 mb-2"
                                    >
                                      Enregistrer modification
                                    </MazBtn>
                              
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
