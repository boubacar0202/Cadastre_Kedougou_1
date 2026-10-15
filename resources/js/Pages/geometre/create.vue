<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import BaseInput from '@/Components/BaseInput.vue'; 
import BaseSelect from '@/Components/BaseSelect.vue';
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/vue3';
import { reactive, ref, watch, watchEffect, computed } from 'vue';
import { baremeOptions, mapsFromBaremes } from '@/utils/baremes'; 
import DefaultLayout from "@/Layouts/DefaultLayout.vue"; 
import { toast } from "@/Components/AppToast";  
import axios from 'axios'
import { usePage } from '@inertiajs/vue3';
 
defineOptions({ layout: DefaultLayout });

const props = defineProps({
    txt_num_dossier: String,
    txt_nicad: String,
    nbr_surface: [String, Number],
    baremes: { type: Array, default: () => [] },
});

const { categories, prixParSecteur, coeffCAValues, coeffCLValues } = mapsFromBaremes(props.baremes);
 
const txt_num_dossier = ref('');  
const formVisible = ref(false); 
const maxOccupants = 25
const maxOccupantsBP = 4;
const maxOccupantsCA = 3;
const maxOccupantsCL = 3;
const maxOccupantsAP = 3;

const currentCat = ref(''); 
 
const form = useForm({
    //recupèration
    txt_num_dossier:"",
    txt_nicad:  "",
    nbr_surface:  "",
    // reference usage 
    slt_usage:'',
    slt_residence:'',

    occupants: [ // Liste des occupants
        {
            txt_nomOccupantTG: '',
            txt_numAppartementTG: '',
            txt_activiteTG: '',
            txt_nineaTG: '',
            tel_telephoneTG: '',
            nbr_montantLoyerTG: '',
            txt_dateLieuNaissanceTG: '',
            txt_cniPasseportTG: '',
            dt_dateDelivranceTG: '',
            
        }
    ],

    nbr_montantLoyerTotal:'',
    nbr_TVATotal:'',
    dt_dateEvaluation: '',  
    txt_superficie_bati_sol: '', 
    slt_secteur:'', 
    nbr_prix_metre_carre: '',
    nbr_valeur_terrain:'',

    txt_dependant_domainePR: '',
    slt_categoriePR:'',
    nbr_prix_metre_carrePR: null,
    nbr_surface_bati_solPR: '',
    nbr_niveauPR: '',
    nbr_surface_utilePR: '',
    slt_coeffPR: '',
    nbr_surface_corrigerPR: '',
    nbr_valeurPR: '',
    currentCat: '',
    occupantsBP: [   
        {
            slt_dependant_domaineTG: '',
            slt_categorieTG:'',
            nbr_prix_metre_carreTG: '',
            nbr_surface_bati_solTG: '',
            nbr_niveauTG: '',
            nbr_surface_utileTG: '',
            slt_coeffTG: '',
            nbr_surface_corrigerTG: '',
            nbr_valeurTG: '',
            tauxValeurLocative: 0,
        }
    ],
    txt_valeur_terrain_bati: '',

    occupantsCA:[
        {
            slt_cours_amenager_totale:'',
            nbr_surface_ca_total:'', 
            slt_categorie_ca_total:'', 
            nbr_prix_metre_carre_ca_total:'', 
            nbr_coefficient_ca_total:'',
            nbr_valeur_ca_total:'',
        }
    ],
    nbr_valeur_total_ca:'',

    occupantsCL:[
        {
            slt_cours_amenager_clo:'',  
            nbr_longueur_avant_clo:'', 
            slt_categorie_clo:'', 
            nbr_prix_metre_carre_clo:'', 
            nbr_coefficient_clo:'', 
            nbr_valeur_clo:'',
        }
    ],
    nbr_valeur_total_clotur:'',

    occupantsAP:[
        {
            txt_designation_am:'', 
            nbr_valeur_unitaire_am:'', 
            nbr_quantile_am:'', 
            slt_coeficien_am:'', 
            nbr_valeur_am:'',
        }
    ],
    nbr_valeur_totale_ap:'',   
    nbr_valeurVenaleLimeuble:'',
    nbr_valeurLocative:'',
    dt_dateEvaluation:'',

});
  
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

// Tab actif (par défaut : 'stats')
const activeTab = ref('terrain');
// Fonction pour changer de tab
const setActiveTab = (tab) => {
    activeTab.value = tab;
};


const nbr_valeurPR = ref('');
const nbr_valeurTG = ref(''); 
//arondire les résultat
function arrondirDeuxDecimales(valeur) {
    const num = parseFloat(valeur);
    if (isNaN(num)) return 0;
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

// Regference Usage
// bloc ajouter 
function addBlock() {
    if (form.occupants.length < maxOccupants) {
        form.occupants.push({
            id: Date.now() + Math.random(),
            txt_nomOccupantTG: '',
            txt_numAppartementTG: '',
            txt_activiteTG: '',
            txt_nineaTG: '',
            tel_telephoneTG: '',
            nbr_montantLoyerTG: '',
            txt_dateLieuNaissanceTG:'',
            txt_cniPasseportTG: '',
            dt_dateDelivranceTG: '',
        })
    }else {
        toast.error('Impossible d\'ajouter plus de 25 occupants.');
    }
}
// bloc retirer 
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

watch([currentCat, () => form.slt_categoriePR], ([type, cat]) => {
    if (type && cat && categories[type]?.[cat]) {
        form.nbr_prix_metre_carrePR = categories[type][cat]
    } else {
        form.nbr_prix_metre_carrePR = null
    } 
})
watchEffect(() => {
    form.occupantsBP.forEach((occupant) => {
        const type = currentCat.value
        const cat = occupant.slt_categorieTG

        if (categories[type] && categories[type][cat]) {
            occupant.nbr_prix_metre_carreTG = categories[type][cat]
        } else {
            occupant.nbr_prix_metre_carreTG = null
        }
    })
}) 

// Synchronisation automatique
watch(currentCat, (newVal) => {
  form.currentCat = newVal
})
    
const rechercherDossier = async () => {
    try {
        const { data } = await axios.post('/dossier/verificationEnquete', {
            txt_num_dossier: txt_num_dossier.value
        });

        if (data.exists) {
            const terrain = data.terrain;
            const nicad = terrain?.txt_nicad ?? "";

            // Vérif NICAD
            if (!nicad) {
                formVisible.value = false;
                toast.error("Ce dossier n'a pas de numéro NICAD.");
                return;
            }

            if (nicad.length <= 8) {
                formVisible.value = false;
                toast.error("Le NICAD est incomplet. Vérifiez !");
                return;
            }

            // Afficher le formulaire
            formVisible.value = true;
            form.txt_num_dossier = txt_num_dossier.value;
            form.txt_nicad = nicad;
            form.nbr_surface = terrain?.nbr_surface ?? 0;

            toast.success(data.success);
            console.log("Terrain chargé :", terrain);

        } else {
            formVisible.value = false;
            toast.error(data.error || "Dossier introuvable.");
        }
    } catch (err) {
        formVisible.value = false;
        console.error("❌ Erreur complète :", err);

        if (err.response?.status === 422 && err.response.data.errors) {
            Object.values(err.response.data.errors).forEach(msg => toast.error(msg));
        } else {
            toast.error("Une erreur est survenue lors de la vérification.");
        }
    }
};
 
//  Calculer Montant Total Loyer  
const nbr_montantLoyerTotal = computed(() => {
    return form.occupants.reduce((total, occupant) => {
        const montant = parseFloat(occupant.nbr_montantLoyerTG) || 0;
        return total + montant;
    }, 0);
});

// Calculer TVA Total 
const nbr_TVATotal = computed(() => {
    return (nbr_montantLoyerTotal.value * 0.18).toFixed(2);
});

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

// Calcule nbr_surface_bati_solPR 
watchEffect(() => {
  const secteur = Number(form.slt_secteur);
  form.nbr_prix_metre_carre = prixParSecteur[secteur] || 0;
}); 

// Calcule valeur_terrain
const nbr_valeur_terrain = computed(() => {
    const prix = parseFloat(form.nbr_prix_metre_carre) || 0;
    const bati = parseFloat(form.txt_superficie_bati_sol) || 0;
    const total = parseFloat(form.nbr_surface) || 0; 
    return ((prix / 2) * bati + prix * (total - bati)).toFixed(2);
}); 
watchEffect(() => {
    form.nbr_valeur_terrain = nbr_valeur_terrain.value;
});

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

// calculer nbr_valeur_ca_total     
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

// calculer nbr_valeur_totale_ap, 
watchEffect(() => {
    form.occupantsAP.forEach((occupant) => { 

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
    const terrain = parseFloat(nbr_valeur_terrain.value) || 0;
    const terrainBati = parseFloat(txt_valeur_terrain_bati.value) || 0;
    const ca = parseFloat(nbr_valeur_total_ca.value) || 0;
    const clotur = parseFloat(nbr_valeur_total_clotur.value) || 0;
    const ap = parseFloat(nbr_valeur_totale_ap.value) || 0;

    //return terrain + ca + clotur + ap;
    const resulval = terrain + terrainBati + ca + clotur + ap;
    return parseFloat(resulval.toFixed(2));
});
watchEffect(() => {
    form.nbr_valeurVenaleLimeuble = nbr_valeurVenaleLimeuble.value;
});
 
// Calculer tauxValeurLocative
const isPrincipalSelected = computed(() => form.txt_dependant_domainePR = 'Principale');
const tauxValeurLocative = computed(() => {
    if (!isPrincipalSelected.value) return 0;

    const type = currentCat.value || form.currentCat;
    const code = String(form.slt_categoriePR || "");

    if ((type === "Immeuble collectif" && code === "A") || (type === "Maison individuelle" && code === "1")) {
        return 0.1344;
    }
    if ((type === "Immeuble collectif" && ["B", "C"].includes(code)) || (type === "Maison individuelle" && ["2", "3"].includes(code))) {
        return 0.12;
    }
    return 0.10;
});

// valeur locative
const nbr_valeurLocative = computed(() => {
    if (!isPrincipalSelected.value) return 0; 
    const valeurVenale = parseFloat(form.nbr_valeurVenaleLimeuble) || 0;
    const taux = tauxValeurLocative.value;
   // return valeurVenale * taux;
   const result = valeurVenale * taux;
   return parseFloat(result.toFixed(2));
});
watchEffect(() => {
    form.nbr_valeurLocative = nbr_valeurLocative.value;
});


const usages = [
    { label: 'Habitation', value: 'Habitation' },
    { label: 'Commercial', value: 'Commercial' },
    { label: 'Agricole', value: 'Agricole' },
    { label: 'Industriel', value: 'Industriel' },
    { label: 'Professionnelle', value: 'Professionnelle' },
    { label: 'Culte', value: 'Culte' },
    { label: 'Administratif', value: 'Administratif' },
];
const residences = [
    { label: 'Résidence principale', value: 'Residence Principal' },
    { label: 'Résidence secondaire', value: 'Residence Secondaire' },
];
const secteurs = baremeOptions(props.baremes, 'secteur', '', { withPrice: true });
const categoriesMaison = baremeOptions(props.baremes, 'batiment', 'Maison individuelle');
const categoriesImmeuble = baremeOptions(props.baremes, 'batiment', 'Immeuble collectif');
const coefficients = ['0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9']
    .map((value) => ({ label: value, value }));
const coefficientsAvecUn = [...coefficients, { label: '1', value: '1' }];
const coefficientsCloture = coefficients.filter((item) => item.value !== '0.9');
const categoriesCours = baremeOptions(props.baremes, 'cours_amenagee');
const categoriesCloture = baremeOptions(props.baremes, 'cloture');
const batimentsAnnexes = [
    'Secondaire', 'Tertiaire', 'Dépendance', 'Partie Saillante',
    'Partie saillante verticale', 'Partie saillante horizontale', 'Balcon',
].map((value) => ({ label: value, value }));
const designationsCours = [
    'Cours Amenagées Totale', 'Cours Avant', 'Cours Arrière',
    'Cours Latérale Droite', 'Cours Latérale Gauche', 'Cours Restante',
].map((value) => ({ label: value, value }));
const designationsClotures = [
    'Longueur Totale', 'Longueur Avant', 'Longueur Arrière',
    'Longueur Latérale Droite', 'Longueur Latérale Gauche', 'Longueur Restante',
].map((value) => ({ label: value, value }));

const submitForm = async () => {
  try {
    const response = await axios.get(`/verifier-nicad/${form.txt_nicad}`)

    if (response.data.existe) {
      toast.error('Ce Terrain est déjà évaluer.  Vérifier le N° de terrain')
      return
    }

    form.post(route('geometre.store'), {
      onSuccess: (page) => {
        toast.success(page.props.flash?.success || '✅ Opération réussie !')
        Inertia.reload()
      },
      onError: (errors) => {
        Object.values(errors).forEach(e => toast.error(e))
      }
    })
  } catch (error) {
    toast.error('Erreur lors de la vérification du NICAD.')
    console.error(error)
  }
}

</script>

<template> 
    <Head title="Geometre">
        <link rel="icon" sizes="512x512" href="/logo-01.png">
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Enquête Cadastrale 
            </h2>
        </template> 
        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl"> 
                    <div class="bg-white border shadow-md rounded-xl">
                        <div class="p-4 border-r bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white text-center font-semibold">Veillez vérifiier le numéro de dossier !</h1>
                        </div>
                        <div class="py-6">
                            <!-- Wrapper flex pour centrer -->
                            <div class="flex justify-center">
                                <!-- Ici on limite la largeur et on centre automatiquement -->
                                <div class="geo-search">
                                    <BaseInput
                                        label="Numéro de dossier"
                                        v-model="txt_num_dossier"
                                        name="txt_num_dossier"
                                        placeholder="Entrez le numéro du dossier"
                                        @keyup.enter="rechercherDossier"
                                    />
                                    <MazBtn 
                                        type="button" 
                                        title="Rechercher"
                                        @click="rechercherDossier" 
                                        class="geo-btn"
                                            size="medium"
                                        >
                                        Suivant
                                    </MazBtn>
                                </div>
                            </div>
                        </div>
                    </div><br>
 
                    <div v-if="formVisible" class="bg-white border border-primary-only/20 shadow-md rounded-xl">
                        <div class="p-4 bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white text-center font-semibold">Formulaire de renseignement cadastral</h1>
                        </div>
                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="p-6">
                                <!--    References usages -->
                                <h5 class="geo-title">Références usage</h5>
                                <div class="geo-panel mb-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <BaseSelect
                                                            v-model="form.slt_usage"   
                                            label="Usage"
                                            name="slt_usage"
                                            :options="usages"
                                            placeholder="Choisir un usage"
                                                            required
                                        />
                                        <BaseSelect
                                                            v-model="form.slt_residence"  
                                            label="Type de résidence"
                                            name="slt_residence"
                                            :options="residences"
                                            placeholder="Choisir une résidence"
                                                            required
                                        />
                                    </div>
                                </div>

                                <!--    References usages -->
                                <h5 class="geo-title">Références occupant</h5>
                                <div class="mb-6">
                                    <div class="border-b">  
                                        <div class="mb-2"> 
                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater">
                                                    <div 
                                                        v-for="(occupant, i) in form.occupants" 
                                                        :key="occupant.id" 
                                                        class="geo-card"> 
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseInput :label="`Nom occupant ${i + 1}`" :name="`NomOccupant_${i}`" autocomplete="off" v-model="occupant.txt_nomOccupantTG" />
                                                            <BaseInput label="N° appartement" :name="`NumAppartement_${i}`" autocomplete="off" v-model="occupant.txt_numAppartementTG" />
                                                            <BaseInput label="Activité" :name="`Activite_${i}`" autocomplete="off" v-model="occupant.txt_activiteTG" />
                                                            <BaseInput label="NINEA" :name="`Ninea_${i}`" autocomplete="off" v-model="occupant.txt_nineaTG" />
                                                            <BaseInput label="Téléphone" type="tel" :name="`Telephone_${i}`" autocomplete="off" v-model="occupant.tel_telephoneTG" />
                                                            <BaseInput label="Montant loyer" type="number" :name="`Montant_loyer_${i}`" autocomplete="off" v-model="occupant.nbr_montantLoyerTG" />
                                                            <BaseInput label="Date, naissance" :name="`DateNaissance_${i}`" autocomplete="off" v-model="occupant.txt_dateLieuNaissanceTG" />
                                                            <BaseInput label="CNI / Passeport" :name="`CNIPasseport_${i}`" autocomplete="off" v-model="occupant.txt_cniPasseportTG" />
                                                            <BaseInput label="Date de délivrance" type="date" :name="`DateDelivrance_${i}`" v-model="occupant.dt_dateDelivranceTG" />
                                                        </div> 
                                                    </div>
                                                </TransitionGroup> 
                                                <div class="geo-occupant-actions">
                                                    <button  
                                                        @click="addBlock"
                                                        type="button"
                                                        class="geo-btn">
                                                        Ajouter
                                                    </button> 
                                                    <button 
                                                        @click="removeBlock"
                                                        type="button"
                                                        class="geo-btn">
                                                        Retirer
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="geo-grid mt-6">
                                                <BaseInput label="Montant loyer total" type="number" name="MontantLoyerTotal" step="0.01" readonly v-model="nbr_montantLoyerTotal" />
                                                <BaseInput label="TVA totale" type="number" name="TVATotal" readonly v-model="nbr_TVATotal" />
                                            </div>
                                        </div>  
                                    </div>
                                </div> 
                                
                                <!-- evaluation de limeuble-->
                                <h5 class="geo-title">Évaluation de l'immeuble</h5>
                                <div class="mb-6">
                                    <div class="geo-tabs-wrap">
                                        <ul class="geo-tabs">

                                            <li class="geo-tab-item">
                                                <button
                                                    type="button"
                                                    @click="setActiveTab('terrain')"
                                                    :class="{
                                                        'geo-tab-on': activeTab === 'terrain',
                                                        'geo-tab-off': activeTab !== 'terrain'
                                                    }"
                                                    class="geo-tab"
                                                >
                                                    Terrain
                                                </button>
                                            </li>

                                            <li class="geo-tab-item">
                                                <button
                                                    type="button"
                                                    @click="setActiveTab('bati')"
                                                    :class="{
                                                        'geo-tab-on': activeTab === 'bati',
                                                        'geo-tab-off': activeTab !== 'bati'
                                                    }"
                                                    class="geo-tab"
                                                >
                                                    Bâti
                                                </button>
                                            </li>

                                            <li class="geo-tab-item">
                                                <button
                                                    type="button"
                                                    @click="setActiveTab('Cours_amenagees')"
                                                    :class="{
                                                        'geo-tab-on': activeTab === 'Cours_amenagees',
                                                        'geo-tab-off': activeTab !== 'Cours_amenagees'
                                                    }"
                                                    class="geo-tab"
                                                >
                                                    Cours aménagées
                                                </button>
                                            </li>

                                            <li class="geo-tab-item">
                                                <button
                                                    type="button"
                                                    @click="setActiveTab('cloture')"
                                                    :class="{
                                                        'geo-tab-on': activeTab === 'cloture',
                                                        'geo-tab-off': activeTab !== 'cloture'
                                                    }"
                                                    class="geo-tab"
                                                >
                                                    Clôtures
                                                </button>
                                            </li>
                                            
                                            <li class="geo-tab-item">
                                                <button
                                                    type="button"
                                                    @click="setActiveTab('amenagement')"
                                                    :class="{
                                                        'geo-tab-on': activeTab === 'amenagement',
                                                        'geo-tab-off': activeTab !== 'amenagement'
                                                    }"
                                                    class="geo-tab"
                                                >
                                                    Aménagements particuliers
                                                </button>
                                            </li>

                                        </ul>
                                    </div>

                                    <!--     TERRAIN    --> 
                                    <div class="p-6">
                                        <div v-show="activeTab === 'terrain'" class="geo-grid">
                                            <BaseInput label="Superficie totale du terrain" type="number" name="nbr_surface" readonly v-model="form.nbr_surface" />
                                            <BaseInput label="Superficie bâti au sol" type="number" name="txt_superficie_bati_sol" required :min="0" :max="form.nbr_surface" v-model="form.txt_superficie_bati_sol" />
                                            <BaseInput label="Superficie non bâti" type="number" name="Superficie_non_bati" readonly :min="0" v-model="form.txt_superficie_non_bati" />
                                            <BaseSelect v-model="form.slt_secteur" label="Secteur" name="slt_secteur" :options="secteurs" placeholder="Choisir un secteur" required />
                                            <BaseInput label="Prix du mètre carré" type="number" name="Prix_metre_carre" readonly v-model="form.nbr_prix_metre_carre" />
                                            <BaseInput label="Valeur du terrain" type="number" name="Valeur_terrain" step="0.01" readonly v-model="nbr_valeur_terrain" />
                                        </div>

                                        <!--   BATI -->
                                        <div v-show="activeTab === 'bati'" class="space-y-4">  
                                            <div class="flex justify-center">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="w-72">
                                                        <div class="h-10 flex items-center ps-8 border border-primary-only rounded">
                                                            <MazRadio
                                                                v-model="currentCat"
                                                                value="Maison individuelle"
                                                                label="Maison individuelle"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div class="w-72">
                                                        <div class="h-10 flex items-center ps-8 border border-primary-only rounded">
                                                            <MazRadio
                                                                v-model="currentCat"
                                                                value="Immeuble collectif"
                                                                label="Immeuble collectif"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
  
                                            <!-- Batiment  Princil-->
                                            <h6 class="text-lg text-justify-center text-center text-primary-txt"><b>Bâtiment(s) Principal(aux)</b></h6>
                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater">
                                                    <div 
                                                        class="geo-card">
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseInput label="Bâtiment" name="txt_dependant_domainePR" readonly v-model="form.txt_dependant_domainePR" />
                                                            <BaseSelect
                                                                v-if="currentCat === 'Maison individuelle'"
                                                                        v-model="form.slt_categoriePR" 
                                                                label="Catégorie"
                                                                        name="slt_categoriePR" 
                                                                :options="categoriesMaison"
                                                                placeholder="CAT"
                                                            />
                                                            <BaseSelect
                                                                v-else-if="currentCat === 'Immeuble collectif'"
                                                                    v-model="form.slt_categoriePR"
                                                                label="Catégorie"
                                                                name="slt_categoriePR_immeuble"
                                                                :options="categoriesImmeuble"
                                                                placeholder="CAT"
                                                            />
                                                            <BaseInput label="Prix m²" type="number" name="nbr_prix_metre_carrePR" readonly v-model="form.nbr_prix_metre_carrePR" />
                                                            <BaseInput label="Surface bâti au sol" type="number" name="nbr_surface_bati_solPR" :min="0" :max="form.txt_superficie_bati_sol" v-model="form.nbr_surface_bati_solPR" />
                                                            <BaseInput label="Niveau" type="number" name="nbr_niveauPR" v-model="form.nbr_niveauPR" />
                                                            <BaseInput label="Surface utile" type="number" name="nbr_surface_utilePR" readonly v-model="form.nbr_surface_utilePR" />
                                                            <BaseSelect v-model="form.slt_coeffPR" label="Coefficient" name="slt_coeffPR" :options="coefficients" placeholder="Coeff" />
                                                            <BaseInput label="Surface corrigée" type="number" name="nbr_surface_corrigerPR" readonly v-model="form.nbr_surface_corrigerPR" />
                                                            <BaseInput label="Valeur" type="number" name="nbr_valeurPR" step="0.01" readonly v-model="form.nbr_valeurPR" />
                                                        </div>
                                                    </div>
                                                </TransitionGroup> 
                                            </div>  
                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater">
                                                    <div 
                                                        v-for="(occupant, i) in form.occupantsBP" 
                                                        :key="occupant.id" 
                                                        class="geo-card">
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseSelect
                                                                        v-model="occupant.slt_dependant_domaineTG" 
                                                                label="Bâtiment"
                                                                :name="`slt_dependant_domaine_${i}`"
                                                                :options="batimentsAnnexes"
                                                                placeholder="Choisir"
                                                            />
                                                            <BaseSelect
                                                                v-if="currentCat === 'Maison individuelle'"
                                                                        v-model="occupant.slt_categorieTG" 
                                                                label="Catégorie"
                                                                :name="`slt_categorieTG_${i}`"
                                                                :options="categoriesMaison"
                                                                placeholder="CAT"
                                                            />
                                                            <BaseSelect
                                                                v-else-if="currentCat === 'Immeuble collectif'"
                                                                    v-model="occupant.slt_categorieTG"
                                                                label="Catégorie"
                                                                :name="`slt_categorieTG_immeuble_${i}`"
                                                                :options="categoriesImmeuble"
                                                                placeholder="CAT"
                                                            />
                                                            <BaseInput label="Prix m²" type="number" :name="`prix_m2_tg_${i}`" readonly v-model="occupant.nbr_prix_metre_carreTG" />
                                                            <BaseInput label="Surface bâti au sol" type="number" :name="`surface_bs_tg_${i}`" :min="0" :max="form.txt_superficie_bati_sol" v-model="occupant.nbr_surface_bati_solTG" />
                                                            <BaseInput label="Niveau" type="number" :name="`niveau_tg_${i}`" v-model="occupant.nbr_niveauTG" />
                                                            <BaseInput label="Surface utile" type="number" :name="`surface_utile_tg_${i}`" readonly v-model="occupant.nbr_surface_utileTG" />
                                                            <BaseSelect v-model="occupant.slt_coeffTG" label="Coefficient" :name="`coeff_tg_${i}`" :options="coefficients" placeholder="Coeff" />
                                                            <BaseInput label="Surface corrigée" type="number" :name="`surface_corrigee_tg_${i}`" readonly v-model="occupant.nbr_surface_corrigerTG" />
                                                            <BaseInput label="Valeur" type="number" :name="`valeur_tg_${i}`" step="0.01" readonly v-model="occupant.nbr_valeurTG" />
                                                        </div>
                                                    </div>
                                                </TransitionGroup>
                                                <div class="geo-occupant-actions">
                                                    <div>
                                                        <button  
                                                            @click="addBlockBP"
                                                            type="button"
                                                            class="geo-btn">
                                                            Ajouter
                                                        </button>
                                                    </div>
                                                    <div>
                                                        <button 
                                                            @click="removeBlockBP"
                                                            type="button"
                                                            class="geo-btn">
                                                            Retirer
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>  
                                            <div class="geo-grid mt-6">
                                                <BaseInput label="Valeur des bâtiments" type="number" name="Valeur_terrain_bati" step="0.01" readonly :model-value="txt_valeur_terrain_bati" />
                                            </div>
                                        </div>

                                        <!-- Cours AMENAGEES-->
                                        <div v-show="activeTab === 'Cours_amenagees'" class="space-y-4">

                                            <!-- Cour Aménager Totale -->

                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater"> 
                                                    <div 
                                                        v-for="(occupant, i) in form.occupantsCA" 
                                                        :key="occupant.id" 
                                                        class="geo-card"> 
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseSelect v-model="occupant.slt_cours_amenager_totale" label="Désignation" :name="`cours_${i}`" :options="designationsCours" placeholder="Choisir" />
                                                            <BaseInput label="Surface" type="number" :name="`surface_ca_${i}`" :min="0" :max="form.nbr_surface" v-model="occupant.nbr_surface_ca_total" @input="verifierSurfaceOccupant(occupant)" />
                                                            <BaseSelect v-model="occupant.slt_categorie_ca_total" label="Catégorie" :name="`categorie_ca_${i}`" :options="categoriesCours" placeholder="CAT" />
                                                            <BaseInput label="Prix / m²" type="number" :name="`prix_ca_${i}`" readonly v-model="occupant.nbr_prix_metre_carre_ca_total" />
                                                            <BaseSelect v-model="occupant.nbr_coefficient_ca_total" label="Coefficient" :name="`coeff_ca_${i}`" :options="coefficients" placeholder="Coeff" />
                                                            <BaseInput label="Valeur" type="number" :name="`valeur_ca_${i}`" readonly v-model="occupant.nbr_valeur_ca_total" />
                                                        </div>
                                                    </div> 
                                                </TransitionGroup>  
                                                <div class="geo-occupant-actions">
                                                    <button  
                                                        @click="addBlockCA"
                                                        type="button"
                                                        class="geo-btn">
                                                        Ajouter
                                                    </button>
                                                    
                                                    <button 
                                                        @click="removeBlockCA"
                                                        type="button"
                                                        class="geo-btn">
                                                        Retirer
                                                    </button>
                                                </div>
                                            </div> 
                                            <div class="geo-grid mt-6">
                                                <BaseInput label="Valeur totale des cours" type="number" name="nbr_valeur_total_cours" step="0.01" readonly v-model="nbr_valeur_total_ca" />
                                            </div> 
                                        </div>

                                        <!-- CLOTURES -->

                                        <div v-show="activeTab === 'cloture'" class="space-y-4">
                                            <!-- <label for="Designation">Designation</label> -->

                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater"> 
                                                    <div 
                                                        v-for="(occupant, i) in form.occupantsCL" 
                                                        :key="occupant.id" 
                                                        class="geo-card"> 
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseSelect v-model="occupant.slt_cours_amenager_clo" label="Clôture" :name="`cloture_${i}`" :options="designationsClotures" placeholder="Choisir" />
                                                            <BaseInput label="Linéaire" type="number" :name="`lineaire_clo_${i}`" v-model="occupant.nbr_longueur_avant_clo" />
                                                            <BaseSelect v-model="occupant.slt_categorie_clo" label="Catégorie" :name="`categorie_clo_${i}`" :options="categoriesCloture" placeholder="CAT" />
                                                            <BaseInput label="Prix / mL" type="number" :name="`prix_clo_${i}`" v-model="occupant.nbr_prix_metre_carre_clo" />
                                                            <BaseSelect v-model="occupant.nbr_coefficient_clo" label="Coefficient" :name="`coeff_clo_${i}`" :options="coefficientsCloture" placeholder="Coeff" />
                                                            <BaseInput label="Valeur" type="number" :name="`valeur_clo_${i}`" v-model="occupant.nbr_valeur_clo" />
                                                        </div>
                                                    </div>
                                                    
                                                </TransitionGroup>
                                                <div class="geo-occupant-actions">
                                                    <button  
                                                        @click="addBlockCL"
                                                        type="button"
                                                        class="geo-btn">
                                                        Ajouter
                                                    </button>
                                                    
                                                    <button 
                                                        @click="removeBlockCL"
                                                        type="button"
                                                        class="geo-btn">
                                                        Retirer
                                                    </button>
                                                </div>  
                                            </div>
                                            <div class="geo-grid mt-6">
                                                <BaseInput label="Valeur totale des clôtures" type="number" name="nbr_valeur_total_clotur" step="0.01" readonly v-model="nbr_valeur_total_clotur" />
                                            </div>
                                        </div>

                                        <!--    AMENAGEMENTS PARTICULIERS   -->
                                        <div v-show="activeTab === 'amenagement'" class="space-y-4">
                                            <center><h6 class="text-lg font-bold text-primary-txt">VALEUR</h6></center>  
                                
                                            <div class="geo-occupant-line">
                                                <TransitionGroup name="slide-fade" tag="div" class="geo-repeater"> 
                                                    <div 
                                                        v-for="(occupant, i) in form.occupantsAP" 
                                                        :key="occupant.id" 
                                                        class="geo-card"> 
                                                        <div class="geo-grid geo-fields-compact">
                                                            <BaseInput label="Désignation" :name="`designation_am_${i}`" v-model="occupant.txt_designation_am" />
                                                            <BaseInput label="Valeur unitaire" type="number" :name="`valeur_unitaire_am_${i}`" v-model="occupant.nbr_valeur_unitaire_am" />
                                                            <BaseInput label="Quantité" type="number" :name="`quantite_am_${i}`" v-model="occupant.nbr_quantile_am" />
                                                            <BaseSelect v-model="occupant.slt_coeficien_am" label="Coefficient" :name="`coeff_am_${i}`" :options="coefficientsAvecUn" placeholder="Coeff" />
                                                            <BaseInput label="Valeur" type="number" :name="`valeur_am_${i}`" v-model="occupant.nbr_valeur_am" />
                                                        </div>
                                                    </div>
                                                </TransitionGroup>
                                                <div class="geo-occupant-actions">
                                                    <button  
                                                        @click="addBlockAP"
                                                        type="button"
                                                        class="geo-btn">
                                                        Ajouter
                                                    </button>
                                                    
                                                    <button 
                                                        @click="removeBlockAP"
                                                        type="button"
                                                        class="geo-btn">
                                                        Retirer
                                                    </button>
                                                </div>  

                                            </div>
                                            <div class="geo-grid mt-6">
                                                <BaseInput label="Valeur totale des aménagements" type="number" name="nbr_valeur_totale_ap" step="0.01" readonly v-model="nbr_valeur_totale_ap" />
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="geo-panel mt-8">
                                    <h5 class="geo-title">Synthèse de l'évaluation</h5>
                                    <div class="geo-grid">
                                        <BaseInput label="Valeur vénale de l'immeuble" type="number" name="nbr_valeurVenaleLimeuble" step="0.01" readonly v-model="nbr_valeurVenaleLimeuble" />
                                        <BaseInput label="Valeur locative" type="number" name="nbr_valeurLocative" step="0.01" readonly v-model="nbr_valeurLocative" />
                                        <BaseInput label="Date d'évaluation" type="date" name="dt_dateEvaluation" required v-model="form.dt_dateEvaluation" />
                                    </div>
                                </div>

                                <!-- Bouton de soumission -->
                                
                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn @click.prevent="submitForm" class="geo-btn geo-btn-wide" size="medium">
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
.geo-title {
    margin: 1.5rem 0 0.75rem;
    font-size: 1.125rem;
    font-weight: 700;
    color: #643407;
}

.geo-panel,
.geo-card {
    width: 100%;
    margin-bottom: 1rem;
    padding: 1rem 1.1rem;
    background: #faefe6;
    border: 1px solid #E6C5A2;
    border-radius: 0.85rem;
    box-shadow: 0 1px 2px rgba(97, 49, 4, 0.06);
}

.geo-repeater {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
}

.geo-occupant-line {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-end;
    gap: 0.75rem;
    width: 100%;
    margin-top: 1rem;
}

.geo-occupant-line > .geo-repeater {
    flex: 1 1 auto;
    min-width: 0;
    width: auto;
}

.geo-occupant-actions {
    display: flex;
    flex: 0 0 auto;
    gap: 0.5rem;
    padding-bottom: 0.15rem;
}

.geo-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    align-items: start;
}

.geo-grid.geo-fields-compact {
    grid-template-columns: repeat(auto-fill, minmax(7rem, 9.5rem));
}

.geo-grid > :deep(*),
.geo-search > :deep(*:first-child) {
    width: 100%;
    min-width: 0;
}

.geo-search {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    width: 100%;
    max-width: 42rem;
    margin: 0 auto;
    padding: 0 1.5rem;
    align-items: end;
}

.geo-tabs-wrap {
    margin-bottom: 1rem;
    padding: 0.35rem;
    background: #faefe6;
    border: 1px solid #E6C5A2;
    border-radius: 0.85rem;
}

.geo-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.geo-tab-item {
    flex: 1 1 8rem;
}

.geo-tab {
    width: 100%;
    padding: 0.7rem 0.6rem;
    border-radius: 0.65rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #643407;
    background: transparent;
    transition: background 0.2s ease, color 0.2s ease;
}

.geo-tab-on {
    color: #fff;
    background: #613104;
}

.geo-tab-off:hover {
    background: #ffe5c5;
}

.geo-btn {
    height: 2.5rem;
    padding: 0 1.15rem;
    border-radius: 0.65rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(90deg, #613104, #A35200);
    box-shadow: 0 6px 16px rgba(97, 49, 4, 0.18);
}

.geo-btn:hover {
    filter: brightness(1.08);
}

.geo-btn-wide {
    width: 16rem;
}

.slide-fade-enter-active,
    .slide-fade-leave-active {
    transition: opacity 0.35s ease, transform 0.35s ease;
    }

    .slide-fade-enter-from,
    .slide-fade-leave-to {
    opacity: 0;
    transform: translateY(12px);
}

@media (min-width: 768px) {
    .geo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .geo-search {
        grid-template-columns: minmax(0, 1fr) auto;
    }
}

@media (min-width: 1024px) {
    .geo-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
</style>










