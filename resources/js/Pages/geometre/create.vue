<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/vue3';
import { reactive, ref, watch, watchEffect, computed } from 'vue'; 
import DefaultLayout from "@/Layouts/DefaultLayout.vue"; 
import { useToast } from 'maz-ui'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3';
 
defineOptions({ layout: DefaultLayout });

const toast = useToast();
const txt_num_dossier = ref(''); 
const txt_nicad = ref('')
const nbr_surface = ref(0)
const formVisible = ref(false); 
const maxOccupants = 25
const maxOccupantsBP = 4;
const maxOccupantsCA = 3;
const maxOccupantsCL = 3;
const maxOccupantsAP = 3;

const currentCat = ref('');
const page = usePage();


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
 
// const rechercherDossier = async () => {
//     try {
//         const { data } = await axios.post('/dossier/verify', {
//             txt_num_dossier: txt_num_dossier.value
//         });

//         if (data.exists) {
//         // ✅ Afficher le formulaire
//         formVisible.value = true;

//         // ✅ Affecter les données récupérées du dossier
//         form.txt_num_dossier = txt_num_dossier.value;

//         // ✅ Affecter les données du terrain s'il existe
//         const terrain = data.terrain;

//         form.txt_nicad = terrain?.txt_nicad ?? '';
//         form.nbr_surface = terrain?.nbr_surface ?? 0;

//         // ✅ Message de confirmation
//         toast.success(data.success);

//         console.log("✅ Terrain chargé :", terrain);
//         } else {
//             formVisible.value = false;
//             toast.error(date.error || "Dossier introuvable.");
//         }
//     } catch (err) {
//         formVisible.value = false;
//         console.error("❌ Erreur complète :", err);

//         if (err.response?.status === 422 && err.response.data.errors) {
//             Object.values(err.response.data.errors).forEach(msg => toast.error(msg));
//         } else {
//             toast.error("Une erreur est survenue lors de la vérification.");
//         }
//     }
// };

const rechercherDossier = async () => {
    try {
        const { data } = await axios.post('/dossier/verify', {
            txt_num_dossier: txt_num_dossier.value
        });

        if (data.exists) {
            const terrain = data.terrain;
            const nicad = terrain?.txt_nicad ?? "";

            // ✅ Vérification du NICAD
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

            // ✅ Afficher le formulaire si tout est correct
            formVisible.value = true;
            form.txt_num_dossier = txt_num_dossier.value;
            form.txt_nicad = nicad;
            form.nbr_surface = terrain?.nbr_surface ?? 0;

            toast.success(data.success);
            console.log("✅ Terrain chargé :", terrain);

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
const coeffCAValues = {
    1: 17500,
    2: 13500,
    3: 10500,
    4: 5000,
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
    const terrain = parseFloat(txt_valeur_terrain_bati.value) || 0;
    const ca = parseFloat(nbr_valeur_total_ca.value) || 0;
    const clotur = parseFloat(nbr_valeur_total_clotur.value) || 0;
    const ap = parseFloat(nbr_valeur_totale_ap.value) || 0;

    //return terrain + ca + clotur + ap;
    const resulval = terrain + ca + clotur + ap;
    return parseFloat(resulval.toFixed(2));
});
watchEffect(() => {
    form.nbr_valeurVenaleLimeuble = nbr_valeurVenaleLimeuble.value;
});
 
const isPrincipalSelected = computed(() => form.txt_dependant_domainePR = 'Principale');

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
   // return valeurVenale * taux;
   const result = valeurVenale * taux;
   return parseFloat(result.toFixed(2));
});
watchEffect(() => {
    form.nbr_valeurLocative = nbr_valeurLocative.value;
});


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
                <div class="w-full max-w-7xl">

                    <div class="bg-white border shadow-md rounded-lg">
                        <div class="p-4 border-r bg-primary">
                            <h1 class="text-lg text-white font-semibold">Veillez vérifiier le numéro de dossier !</h1>
                        </div>
                        <div class="py-6">
                            <!-- Wrapper flex pour centrer -->
                            <div class="flex justify-center">
                                <!-- Ici on limite la largeur et on centre automatiquement -->
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 w-full max-w-2xl">
                                    <div class="sm:col-span-4">
                                        <input 
                                            v-model="txt_num_dossier" 
                                            required
                                            type="text"
                                            name="txt_num_dossier" 
                                            aria-label="Rechercher"
                                            class="h-10 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt outline outline-1 outline-offset-1 
                                                outline-primary-only placeholder:text-gray-400 focus:outline-2 focus:outline-primary sm:text-sm/6"
                                            placeholder="Entrez le numéro du dossier" 
                                        />
                                    </div>

                                    <MazBtn 
                                        type="button" 
                                        title="Rechercher"
                                        @click="rechercherDossier" 
                                        class="h-8 w-28 text-white bg-gradient-to-r from-primary via-primary-dark 
                                        to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                        focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                        dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                        py-2.5 text-center me-2 mb-2"
                                    >
                                        Suivant
                                    </MazBtn>
                                </div>
                            </div>
                        </div>
                    </div><br>
 
                    <div v-if="formVisible" class="bg-white border shadow-md rounded-lg">
                        <!-- En-tête du formulaire -->
                        <div class="p-4 border border-b bg-primary-menu">
                            <h1 class="text-lg text-white font-semibold">Formulaire de renseignement Cadastrale</h1>
                        </div>
                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="p-6">
                                <!--    References usages -->
                                <h5 class="text-lg text-primary-txt font-bold">Réferences Usage</h5>
                                <div class="mb-6">
                                    <div class="border-b">  
                                        <div class="mb-4">
                                            <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                <div class="sm:col-span-2">
                                                    <div >
                                                        <label for="Occupant" class="block text-sm/6 font-medium text-primary-txt">Usage</label>
                                                        <select 
                                                            v-model="form.slt_usage"   
                                                            required
                                                            name = "slt_usage"
                                                            id="Occupant"
                                                            class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                            @change="handleSelectChange"
                                                        >
                                                            <option 
                                                                selected disabled
                                                            >
                                                                Choisi ici
                                                            </option>
                                                            <option value="Habitation" >
                                                                Habitation
                                                            </option>
                                                            <option 
                                                                value="Commercial" 
                                                            >
                                                                Commercial
                                                            </option>
                                                            <option 
                                                                value="Agricole" 
                                                            >
                                                                Agricole
                                                            </option>
                                                            <option 
                                                                value="Industriel" 
                                                            >
                                                                Industriel
                                                            </option>
                                                            <option 
                                                                value="Professionnelle" 
                                                            >
                                                                Professionnelle
                                                            </option>
                                                            <option 
                                                                value="Culte" 
                                                            >
                                                                Culte
                                                            </option>
                                                            <option 
                                                                value="Administratif" 
                                                            >
                                                                Administratif
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-4">
                                                    <div >
                                                        <label for="Residence" class="block text-sm/6 font-medium text-primary-txt">Type de residence</label>
                                                        <select type="select" 
                                                            v-model="form.slt_residence"  
                                                            required
                                                            name="slt_residence" id="Residence" 
                                                            class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
                                                        >
                                                            <option selected disabled></option>
                                                            <option value="Residence Principal">Residence Principal</option>
                                                            <option value="Residence Secondaire">Residence secondaire</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!--    References usages -->
                                <h5 class="text-lg text-primary-txt font-bold mt-8">Réferences Occupant</h5>
                                <div class="mb-6">
                                    <div class="border-b">  
                                        <div class="mb-4"> 
                                            <div  class="w-full flex flex-wrap justify-between gap-4 mt-4"> 
                                                <TransitionGroup  name="slide-fade" tag="div">
                                                    <div 
                                                        v-for="(occupant, i) in form.occupants" 
                                                        :key="occupant.id" 
                                                        class="sm:col-span-8">

                                                        <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                            <!-- OCCUPANT -->
                                                            <div class="sm:col-span-1">
                                                                <label :for="'NomOccupant_${i}'" class="block text-sm/6 font-medium text-primary-txt">Nom Occupant {{ i }}</label>
                                                                <div>  
                                                                    <input 
                                                                    autocomplete="off"
                                                                    autocorrect="off"
                                                                    type="text" 
                                                                    v-model="occupant.txt_nomOccupantTG"
                                                                    :id="'NomOccupant_${i}'"  
                                                                    class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>   
                                                            </div> 
                                                            <div class="sm:col-span-1">
                                                                <label :for="'NunAppartement_${i}'" class="block text-sm/6 font-medium text-primary-txt">N° Appart</label>
                                                                <div>
                                                                    <input 
                                                                    autocomplete="off"
                                                                    autocorrect="off"
                                                                    type="text" 
                                                                    v-model="occupant.txt_numAppartementTG"
                                                                    :id="'NunAppartement_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <!-- ACTIVITE -->
                                                            <div class="sm:col-span-1">
                                                                <label :for="'Activite_${i}'" class="block text-sm/6 font-medium text-primary-txt">Activite</label>
                                                                <div>
                                                                    <input type="text"
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.txt_activiteTG"
                                                                    :id="'Activite_${i}'"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <!-- NINEA -->
                                                            <div class="sm:col-span-1">
                                                                <label :for="'Ninea_${i}'" class="block text-sm/6 font-medium text-primary-txt">Ninea </label>
                                                                <div>
                                                                    <input type="text" 
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.txt_nineaTG"
                                                                    :id="'Ninea_${i}'"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <!-- TELEPHONE -->
                                                            <div class="sm:col-span-1">
                                                                <label :for="'Telephone_${i}'" class="block text-sm/6 font-medium text-primary-txt">Téléphone </label>
                                                                <div>
                                                                    <input type="tel" 
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.tel_telephoneTG"
                                                                    :id="'Telephone_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                <label :for="'Montant_loyer_${i}'" class="block text-sm/6 font-medium text-primary-txt">Mnt Loyer</label>
                                                                <div>
                                                                    <input type="number"  
                                                                    autocomplete="off"
                                                                    autocorrect="off"  
                                                                    v-model="occupant.nbr_montantLoyerTG"
                                                                    :id="'Montant_loyer_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                <label :for="'DateNaissnace_${i}'" class="block text-sm/6 font-medium text-primary-txt">Date-lieu Nais</label>
                                                                <div>
                                                                    <input type="text" 
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.txt_dateLieuNaissanceTG"
                                                                    :id="'DateNaissnace_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                <label :for="'CNIPasseport_${i}'" class="block text-sm/6 font-medium text-primary-txt">CNI/Pasprt</label>
                                                                <div>
                                                                    <input type="text" 
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.txt_cniPasseportTG"
                                                                    :id="'CNIPasseport_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                <label :for="'DateDelivrance_${i}'" class="block text-sm/6 font-medium text-primary-txt">Date Déliv</label>
                                                                <div>
                                                                    <input type="date" 
                                                                    autocomplete="off"
                                                                    autocorrect="off" 
                                                                    v-model="occupant.dt_dateDelivranceTG"
                                                                    :id="'DateDelivrance_${i}'"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                        
                                                        </div>
                                                    
                                                    </div>
                                                </TransitionGroup>
                                                <div class="flex gap-4 mt-6">
                                                    <button  
                                                        @click="addBlock"
                                                        type="button"
                                                        class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                        to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                        focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                        dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                        py-2.5 text-center">
                                                        Ajouter
                                                    </button>
                                                    
                                                    <button 
                                                        @click="removeBlock"
                                                        type="button"
                                                        class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                        to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                        focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                        dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                        py-2.5 text-center">
                                                        Retirer
                                                    </button>
                                                </div>

                                                <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8">
                                                    <div class="sm:col-span-2">
                                                        <label for="MontantLoyerTotal" class="block text-sm/6 font-medium text-primary-txt">Montant Loyer Total</label>
                                                        <div>
                                                            <input type="number" 
                                                            v-model="nbr_montantLoyerTotal"
                                                            readonly
                                                            id="MontantLoyerTotal" 
                                                            step="0.01"
                                                            @blur="arrondirDeuxDecimales(nbr_montantLoyerTotal)"  
                                                            class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-form 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                        </div>
                                                    </div>
                                                    <div class="sm:col-span-2">
                                                        <label for="TVATotal" class="block text-sm/6 font-medium text-primary-txt">TVA Totale</label>
                                                        <div>
                                                            <input type="number" 
                                                            v-model="nbr_TVATotal"
                                                            readonly
                                                            id="TVATotal"  
                                                            class="h-8 block w-64 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-form 
                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>  
                                    </div>
                                </div> 
                                
                                <!-- evaluation de limeuble-->
                                <h5 class="text-lg text-primary-txt font-bold mt-8">Evaluation de l'immeuble</h5><br>
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
                                                            <label for="Superficie_totale" class="block text-sm/6 font-medium text-primary-txt">Superficie Totale Terrain</label>
                                                            <div>
                                                                <input  
                                                                    type="number"   
                                                                    name="nbr_surface"
                                                                    v-model="form.nbr_surface" 
                                                                    readonly
                                                                    id="nbr_surface" 
                                                                    class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                            </div>
 
                                                        </div>
                                                    </div>
                                                
                                                    <div class="sm:col-span-2">
                                                        <div class="mt-6">
                                                            <label for="Superficie_bati_sol" class="block text-sm/6 font-medium text-primary-txt">Superficie Bâti au sol</label>
                                                            <div>
                                                                <input 
                                                                type="number" 
                                                                v-model="form.txt_superficie_bati_sol"  
                                                                required
                                                                name="txt_superficie_bati_sol"
                                                                id="Superficie_bati_sol" 
                                                                :min="0"
                                                                :max="form.nbr_surface" 
                                                                class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                            <label for="Secteur" class="block text-sm/6 font-medium text-primary-txt">Secteur</label>
                                                            <div> 
                                                                <select type="select" 
                                                                v-model="form.slt_secteur" 
                                                                required
                                                                name="slt_secteur" 
                                                                id="Secteur" 
                                                                class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                    <option selected desabled></option>
                                                                    <option :value="1">1 (3000 FCFA/m²)</option>
                                                                    <option :value="2">2 (2000 FCFA/m²)</option>
                                                                    <option :value="3">3 (1000 FCFA/m²)</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>  
                                                
                                                    <div class="sm:col-span-2">
                                                        <div class="mt-6">
                                                            <label for="Prix_metre_carre" class="block text-sm/6 font-medium text-primary-txt">Prix mètre Carré</label>
                                                            <div>
                                                                <input 
                                                                type="number" 
                                                                v-model="form.nbr_prix_metre_carre"  
                                                                readonly
                                                                id="Prix_metre_carre"  
                                                                class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                            </div>
                                                        </div>
                                                    </div>   
                                                    <div class="sm:col-span-2">
                                                        <div class="mt-6"> 
                                                            <label for="Valeur_terrain" class="block text-sm/6 font-medium text-primary-txt">Valeur du Terrain</label>
                                                            <div>
                                                                <input type="number"
                                                                v-model="nbr_valeur_terrain"  
                                                                step="0.01"
                                                                @blur="arrondirDeuxDecimales(nbr_valeur_terrain)" 
                                                                readonly
                                                                id="Valeur_terrain"  
                                                                class="h-9 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                <!-- Boutons radio -->
                                                <div class="sm:col-span-2">
                                                    <div class="h-10 flex items-center ps-8 border border-primary-only rounded dark:border-primary">
                                                        <MazRadio v-model="currentCat" 
                                                        value="Maison individuelle" 
                                                        label="Maison individuelle" />
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <div class="h-10 flex items-center ps-8 border border-primary-only rounded dark:border-primary">
                                                        <MazRadio v-model="currentCat" 
                                                        value="Immeuble collectif" 
                                                        label="Immeuble collectif" />
                                                    </div>
                                                </div>
                                                <br/>    
                                            </div>
                                            <!-- Batiment  Princil-->
                                            <center><h6 class="text-lg"><b>Bâtiment(s) Principal(aux)</b></h6></center>
                                            <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                <TransitionGroup>
                                                    <div 
                                                        class="sm:col-span-8">
                                                        <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                                            <div class="sm:col-span-1">
                                                                <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">Bâtiment </label>
                                                                    <div>   
                                                                        <input 
                                                                        v-model="form.txt_dependant_domainePR" 
                                                                        name="txt_dependant_domainePR" 
                                                                        readonly
                                                                        id="Dependant_domaine" 
                                                                        class="h-8 block w-36 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-dark 
                                                                                focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"> 
                                                                    </input>
                                                                    </div>
                                                             </div>
                                                            <div  class="sm:col-span-2">
                                                                <div v-if="currentCat === 'Maison individuelle'" class="sm:col-span-1">
                                                                    <!-- Vous avez sélectionné une catégorie de type Maison individuelle. -->
                                                                    <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">CAT</label>
                                                                    <div>   
                                                                        <select 
                                                                        v-model="form.slt_categoriePR" 
                                                                        name="slt_categoriePR" 
                                                                        id="Dependant_domaine" 
                                                                        class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                                outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-dark 
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
                                                                <div v-else-if="currentCat === 'Immeuble collectif'" class="block text-sm/6 font-medium text-gray-900">
                                                                    <!-- Vous avez sélectionné une autre catégorie. -->
                                                                    <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">CAT</label>
                                                                    <select 
                                                                    v-model="form.slt_categoriePR"
                                                                    name="slt_categoriePR" 
                                                                    id="Dependant_domaine" 
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-dark 
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
                                                                <label for="Prix_mettre_carre" class="block text-sm/6 font-medium text-primary-txt">Prix m²</label>
                                                                <div>  
                                                                    <input 
                                                                    v-model="form.nbr_prix_metre_carrePR"  
                                                                    readonly
                                                                    type="number" 
                                                                    id="Prix_mettre_carre"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-dark 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>  
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_bati_sol" class="block text-sm/6 font-medium text-primary-txt">Surf B S</label>
                                                                <div>
                                                                    <input  
                                                                    v-model="form.nbr_surface_bati_solPR"
                                                                    name="nbr_surface_bati_solPR"
                                                                    :min="0"
                                                                    :max="form.txt_superficie_bati_sol"
                                                                    type="number" 
                                                                    id="Surface_bati_sol"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-primary-dark 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">  
                                                                <label for="nbr_niveauPR" class="block text-sm/6 font-medium text-primary-txt">Niveau</label>
                                                                <div>
                                                                    <input  
                                                                    v-model="form.nbr_niveauPR"
                                                                    name="nbr_niveauPR"
                                                                    type="number" 
                                                                    id="nbr_niveauPR" 
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-primary-dark 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_utile" class="block text-sm/6 font-medium text-primary-txt">Surf Utile</label>
                                                                <div>   
                                                                    <input  
                                                                    v-model="form.nbr_surface_utilePR"
                                                                    name="nbr_surface_utilePR"
                                                                    type="number" 
                                                                    id="Surface_utile" 
                                                                    readonly
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                <label for="Coeff" class="block text-sm/6 font-medium text-primary-txt">Coeff</label>
                                                                <div>  
                                                                    <select  
                                                                    v-model="form.slt_coeffPR"
                                                                    name="slt_coeffPR"
                                                                    id="Coeff"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-graprimary-dark                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                        <option selected desabled></option>
                                                                        <option value="0.1">0.1</option>
                                                                        <option value="0.2">0.2</option>
                                                                        <option value="0.3">0.3</option>
                                                                        <option value="0.4">0.4</option>
                                                                        <option value="0.5">0.5</option>
                                                                        <option value="0.6">0.6</option>
                                                                        <option value="0.7">0.7</option>
                                                                        <option value="0.8">0.8</option>
                                                                        <option value="0.9">0.9</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_corriger" class="block text-sm/6 font-medium text-primary-txt">S.Corrigee</label>
                                                                <div> 
                                                                    <input                                               
                                                                    v-model="form.nbr_surface_corrigerPR"
                                                                    name="nbr_surface_corrigerPR"
                                                                    type="number" 
                                                                    id="Surface_corriger" 
                                                                    readonly
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-graprimary-dark                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                <label for="Valeur" class="block text-sm/6 font-medium text-primary-txt">Valeur</label>
                                                                <div>
                                                                    <input 
                                                                    type="number" 
                                                                    v-model="form.nbr_valeurPR"
                                                                    name="nbr_valeurPR"
                                                                    step="0.01"
                                                                    @blur="() => arrondirDeuxDecimales(nbr_valeurPR)"
                                                                    readonly
                                                                    id="Valeur"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-graprimary-dark                                                                      focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
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
                                                                <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">Bâtiment </label>
                                                                    <div>   
                                                                        <select 
                                                                        v-model="occupant.slt_dependant_domaineTG" 
                                                                        name="slt_dependant_domaine" 
                                                                        id="Dependant_domaine" 
                                                                        class="h-8 block w-36 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                <div v-if="currentCat === 'Maison individuelle'" class="sm:col-span-1">
                                                                    <!-- Vous avez sélectionné une catégorie de type Maison individuelle. -->
                                                                    <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">CAT</label>
                                                                    <div>   
                                                                        <select 
                                                                        v-model="occupant.slt_categorieTG" 
                                                                        name="slt_categorieTG" 
                                                                        id="Dependant_domaine" 
                                                                        class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                <div v-else-if="currentCat === 'Immeuble collectif'" class="block text-sm/6 font-medium text-primary-txt">
                                                                    <!-- Vous avez sélectionné une autre catégorie. -->
                                                                    <label for="Dependant_domaine" class="block text-sm/6 font-medium text-primary-txt">CAT</label>
                                                                    <select 
                                                                    v-model="occupant.slt_categorieTG"
                                                                    name="slt_categorieTG" 
                                                                    id="Dependant_domaine" 
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                <label for="Prix_mettre_carre" class="block text-sm/6 font-medium text-primary-txt">Prix m²</label>
                                                                <div>  
                                                                    <input 
                                                                    v-model="occupant.nbr_prix_metre_carreTG" 
                                                                    readonly
                                                                    type="number" 
                                                                    id="Prix_mettre_carre"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>  
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_bati_sol" class="block text-sm/6 font-medium text-primary-txt">Surf B S</label>
                                                                <div>
                                                                    <input  
                                                                    v-model="occupant.nbr_surface_bati_solTG"
                                                                    :min="0"
                                                                    :max="form.txt_superficie_bati_sol"
                                                                    @blur="arrondirOccupantValeur(occupant)"
                                                                    type="number" 
                                                                    id="Surface_bati_sol"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-gray-900 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">  
                                                                <label for="Niveau" class="block text-sm/6 font-medium text-primary-txt">Niveau</label>
                                                                <div>
                                                                    <input  
                                                                    v-model="occupant.nbr_niveauTG"
                                                                    name="nbr_niveauTG" 
                                                                    type="number" 
                                                                    id="Niveau" 
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_utile" class="block text-sm/6 font-medium text-primary-txt">Surf Utile</label>
                                                                <div>   
                                                                    <input  
                                                                    v-model="occupant.nbr_surface_utileTG"
                                                                    name="nbr_surface_utileTG"
                                                                    type="number" 
                                                                    id="Surface_utile" 
                                                                    readonly
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                            outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                <label for="Coeff" class="block text-sm/6 font-medium text-primary-txt">Coeff</label>
                                                                <div>  
                                                                    <select  
                                                                    v-model="occupant.slt_coeffTG"
                                                                    name="slt_coeffTG"
                                                                    id="Coeff"  
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                        <option selected desabled></option>
                                                                        <option value="0.1">0.1</option>
                                                                        <option value="0.2">0.2</option>
                                                                        <option value="0.3">0.3</option>
                                                                        <option value="0.4">0.4</option>
                                                                        <option value="0.5">0.5</option>
                                                                        <option value="0.6">0.6</option>
                                                                        <option value="0.7">0.7</option>
                                                                        <option value="0.8">0.8</option>
                                                                        <option value="0.9">0.9</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1"> 
                                                                <label for="Surface_corriger" class="block text-sm/6 font-medium text-primary-txt">S.Corrigee</label>
                                                                <div> 
                                                                    <input                                               
                                                                    v-model="occupant.nbr_surface_corrigerTG"
                                                                    name="nbr_surface_corrigerTG"
                                                                    type="number" 
                                                                    id="Surface_corriger" 
                                                                    readonly
                                                                    class="h-8 block w-20 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                        outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                <label for="Valeur" class="block text-sm/6 font-medium text-primary-txt">Valeur</label>
                                                                <div>
                                                                    <input 
                                                                    type="number" 
                                                                    v-model="occupant.nbr_valeurTG"
                                                                    step="0.01"
                                                                    @blur="arrondirOccupantValeur(occupant)"
                                                                    name="nbr_valeurTG"
                                                                    readonly
                                                                    id="Valeur"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                    <label for="Valeur_terrain_bati" class="block text-sm/6 font-medium text-primary-txt">Valeur(s) bâtiment (s)</label>
                                                    <input 
                                                        type="number" 
                                                        :value="txt_valeur_terrain_bati"
                                                        step="0.01"  
                                                        @blur="txt_valeur_terrain_bati = arrondirDeuxDecimales(txt_valeur_terrain_bati)" 
                                                        readonly
                                                        id="Valeur_terrain_bati"
                                                        class="block w-full rounded-md bg-white 
                                                            px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
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
                                                                    <label for="CoursAmenagerTotale" class="block text-sm/6 font-medium text-primary-txt">Désignation</label>
                                                                <div>   
                                                                    <select 
                                                                    v-model="occupant.slt_cours_amenager_totale" 
                                                                    name="slt_dependant_domaine" 
                                                                    id="Dependant_domaine" 
                                                                    class="h-8 block w-38 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                    <label for="SurfaceCATotal" class="block text-sm/6 font-medium text-primary-txt">Surface</label>
                                                                <div> 
                                                                    <input type="number"
                                                                    v-model="occupant.nbr_surface_ca_total" 
                                                                    :min="0"
                                                                    :max="form.nbr_surface"
                                                                    @input="verifierSurfaceOccupant(occupant)"
                                                                    name="nbr_surface_ca_total" id="Surface_ca_total"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                </div>
                                                            </div>  
                                                            <div class="sm:col-span-1">
                                                                    <label for="Categorie_ca_total" class="block text-sm/6 font-medium text-primary-txt">Catégorie</label>
                                                                <div> 
                                                                    <select type="select" 
                                                                    v-model="occupant.slt_categorie_ca_total" 
                                                                    name="slt_categorie_ca_total"  
                                                                    id="Categorie_ca_total"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                            <option selected sesabled></option>
                                                                            <option :value="1">1</option>
                                                                            <option :value="2">2</option>
                                                                            <option :value="3">3</option>
                                                                            <option :value="4">4</option> 
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                    <label for="Prix_metre_carre_ca_total" class="block text-sm/6 font-medium text-primary-txt">P/m²</label>
                                                                <div> 
                                                                    <input type="number" 
                                                                    v-model="occupant.nbr_prix_metre_carre_ca_total"  
                                                                    readonly
                                                                    name="nbr_prix_metre_carre_ca_total" 
                                                                    id="Prix_metre_carre_ca_total"
                                                                    class="h-8 block w-36 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1">
                                                                    <label for="Coefficient_ca_total" class="block text-sm/6 font-medium text-primary-txt">Coefficient</label>
                                                                <div> 
                                                                    <select type="select" 
                                                                    v-model="occupant.nbr_coefficient_ca_total" 
                                                                    name="nbr_coefficient_ca_total" 
                                                                    id="Coefficient_ca_total"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                            <option selected desabled></option>
                                                                            <option value="0.1">0.1</option>
                                                                            <option value="0.2">0.2</option>
                                                                            <option value="0.3">0.3</option>
                                                                            <option value="0.4">0.4</option>
                                                                            <option value="0.5">0.5</option>
                                                                            <option value="0.6">0.6</option>
                                                                            <option value="0.7">0.7</option>
                                                                            <option value="0.8">0.8</option>
                                                                    </select>
                                                                </div>
                                                            </div> 
                                                            <div class="sm:col-span-1">
                                                                    <label for="Valeur_ca_total" class="block text-sm/6 font-medium text-primary-txt">Valeur</label>
                                                                <div> 
                                                                    <input type="number" 
                                                                    v-model="occupant.nbr_valeur_ca_total"  
                                                                    readonly
                                                                    name="nbr_valeur_ca_total" 
                                                                    id="Valeur_ca_total"  
                                                                    class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                    <label for="Valeur_total_cours" class="block text-sm/6 font-medium text-primary-txt">Valeur Total des Cours</label>
                                                    <input type="number" 
                                                    v-model="nbr_valeur_total_ca" 
                                                    step="0.01"
                                                    @blur="nbr_valeur_total_ca = arrondirDeuxDecimales(nbr_valeur_total_ca)" 
                                                    readonly
                                                    name="nbr_valeur_total_cours" id="Valeur_total_cours"  class="block w-full rounded-md bg-white 
                                                        px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
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
                                                                    <label for="CoursAmenagerTotale" class="block text-sm/6 font-medium text-primary-txt">Clôtures</label>
                                                                <div>   
                                                                    <select 
                                                                    v-model="occupant.slt_cours_amenager_clo" 
                                                                    name="slt_dependant_domaine" 
                                                                    id="Dependant_domaine" 
                                                                    class="h-8 block w-38 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                    <label for="Longueur_avant_clo" class="block text-sm/6 font-medium text-primary-txt">Linéaire</label>
                                                                <div>  
                                                                    <input type="number" 
                                                                    v-model="occupant.nbr_longueur_avant_clo" 
                                                                    name="nbr_longueur_avant_clo" 
                                                                    id="Longueur_avant_clo"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                </div>
                                                            </div>  
                                                            <div  class="sm:col-span-1">
                                                                    <label for="Categorie_clo" class="block text-sm/6 font-medium text-primary-txt">Catégorie</label>
                                                                <div>  
                                                                    <select type="select" 
                                                                    v-model="occupant.slt_categorie_clo" 
                                                                    name="slt_categorie_clo" 
                                                                    id="Categorie_clo"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                                    <label for="Prix_metre_carre_clo" class="block text-sm/6 font-medium text-primary-txt">P/mL</label>
                                                                <div> 
                                                                    <input type="number" 
                                                                    v-model="occupant.nbr_prix_metre_carre_clo" 
                                                                    name="nbr_prix_metre_carre_clo" 
                                                                    id="Prix_metre_carre_clo"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                    <label for="Coefficient_clo" class="block text-sm/6 font-medium text-primary-txt">Coefficient</label>
                                                                <div> 
                                                                    <select type="select"  
                                                                    v-model="occupant.nbr_coefficient_clo" 
                                                                    name="nbr_coefficient_clo" 
                                                                    id="Coefficient_clo"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                            <option selected desabled></option>
                                                                            <option value="0.1">0.1</option>
                                                                            <option value="0.2">0.2</option>
                                                                            <option value="0.3">0.3</option>
                                                                            <option value="0.4">0.4</option>
                                                                            <option value="0.5">0.5</option>
                                                                            <option value="0.6">0.6</option>
                                                                            <option value="0.7">0.7</option>
                                                                            <option value="0.8">0.8</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="sm:col-span-1"> 
                                                                    <label for="Valeur_clo" class="block text-sm/6 font-medium text-primary-txt">Valeur</label>
                                                                <div> 
                                                                    <input type="number"   
                                                                    v-model="occupant.nbr_valeur_clo" 
                                                                    @blur="arrondirOccupantValeur(occupant)"
                                                                    name="nbr_valeur_clo" 
                                                                    id="Valeur_clo"  
                                                                    class="h-8 block w-38 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                                    <label for="Valeur_total_clotur" 
                                                        class="block text-sm/6 font-medium text-primary-txt">Valeurs Totale des Clôtures
                                                    </label>
                                                    <input type="number"         
                                                        v-model="nbr_valeur_total_clotur" 
                                                        step="0.01"
                                                        @blur="nbr_valeur_total_clotur = arrondirDeuxDecimales(nbr_valeur_total_clotur)" 
                                                        readonly
                                                        name="nbr_valeur_total_clotur" 
                                                        id="Valeur_total_clotur"  
                                                        class="block w-full rounded-md bg-white 
                                                        px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                        outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                        focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                </div>
                                            </div>
                                        </div>

                                        <!--    AMENAGEMENTS PARTICULIERS   -->
                                        <div v-show="activeTab === 'amenagement'" class="space-y-4">
                                            <center><h6 class="text-lg font-bold text-primary-txt">VALEUR</h6></center>  
                                
                                            <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                <TransitionGroup  name="slide-fade" tag="div"> 
                                                    <div 
                                                        v-for="(occupant, i) in form.occupantsAP" 
                                                        :key="occupant.id" 
                                                        class="sm:col-span-8"> 
                                                        <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">  

                                                            <div  class="sm:col-span-1">
                                                                <div>
                                                                    <label for="Designation_1" class="ms-2 text-sm font-medium text-primary-txt dark:text-gray-300">Designeation</label>
                                                                    <input id="Designation_1"          
                                                                    v-model="occupant.txt_designation_am" 
                                                                    type="text" 
                                                                    class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6" />
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1"> 
                                                                <div>
                                                                    <label for="Valeur_unitaire_1" class="ms-2 text-sm font-medium text-primary-txt dark:text-gray-300">Valeur Unitaire</label>
                                                                    <input type="number"           
                                                                    v-model="occupant.nbr_valeur_unitaire_am"
                                                                    id="Valeur_unitaire_1"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">                         
                                                                </div>
                                                            </div>  
                                                            <div  class="sm:col-span-1">
                                                                <div> 
                                                                    <label for="Quantile_1" class="ms-2 text-sm font-medium text-primary-txt dark:text-gray-300">Quantité</label>
                                                                    <input type="number"            
                                                                    v-model="occupant.nbr_quantile_am" 
                                                                    id="Quantile_1" 
                                                                    class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                </div>
                                                            </div> 
                                                            <div  class="sm:col-span-1">
                                                                <div> 
                                                                    <label for="Coeficien_1" class="ms-2 text-sm font-medium text-primary-txt dark:text-gray-300">Coeficien</label>
                                                                    <select type="select"             
                                                                    v-model="occupant.slt_coeficien_am" 
                                                                    id="Coeficien_1"  
                                                                    class="h-8 block w-28 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
                                                                    outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400 
                                                                    focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6">
                                                                            <option selected desabled></option>
                                                                            <option value="0.1">0.1</option>
                                                                            <option value="0.2">0.2</option>
                                                                            <option value="0.3">0.3</option>
                                                                            <option value="0.4">0.4</option>
                                                                            <option value="0.5">0.5</option>
                                                                            <option value="0.6">0.6</option>
                                                                            <option value="0.7">0.7</option>
                                                                            <option value="0.8">0.8</option>
                                                                            <option value="0.9">0.9</option>
                                                                            <option value="1">1</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div  class="sm:col-span-1">
                                                                <div> 
                                                                    <label for="Valeur" class="ms-2 text-sm font-medium text-primary-txt dark:text-gray-300">Valeur</label>
                                                                    <input type="number"              
                                                                    v-model="occupant.nbr_valeur_am"  
                                                                    id="Valeur"  
                                                                    class="h-8 block w-34 rounded-md bg-white px-3 py-1.5 text-base text-primary-txt 
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
                                            <div  class="flex justify-center text-primary-txt gap-6 mb-8" >
                                                <div> 
                                                    <label for="valeur_totale_ap" class="text-primary-txt gap-6 mb-8" >
                                                        Valeur Totale des A.P
                                                    </label>
                                                    <input type="number"                    
                                                        v-model="nbr_valeur_totale_ap" 
                                                        step="0.01"  
                                                        @blur="nbr_valeur_totale_ap = arrondirDeuxDecimales(nbr_valeur_totale_ap)" 
                                                        readonly
                                                        name="nbr_valeur_totale_ap" 
                                                        id="valeur_totale_ap"  
                                                        class="block w-full rounded-md bg-white 
                                                        px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
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
                                            <div  class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 justify-center">

                                                <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                    <div> 
                                                        <label for="nbr_valeurVenaleLimeuble" class="block text-sm/6 font-medium text-primary-txt">Valeur Vénale de l'immeuble</label>
                                                        <input type="number"        
                                                            v-model="nbr_valeurVenaleLimeuble" 
                                                            readonly
                                                            name="nbr_valeurVenaleLimeuble" 
                                                            id="nbr_valeurVenaleLimeuble"  
                                                            step="0.01"  
                                                            @blur="nbr_valeurVenaleLimeuble = arrondirDeuxDecimales(nbr_valeurVenaleLimeuble)" 
                                                            class="block w-64 rounded-md bg-white 
                                                            px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                            outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                            focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                    </div>
                                                </div>
                                                <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                    <div> 
                                                        <label for="Valeur_locative" class="block text-sm/6 font-medium text-primary-txt">Valeur Locative</label>
                                                        <input type="number"        
                                                            v-model="nbr_valeurLocative"
                                                            readonly
                                                            name="nbr_valeurLocative" 
                                                            id="Valeur_locative"  
                                                            step="0.01"
                                                            @blur="nbr_valeurLocative = arrondirDeuxDecimales(nbr_valeurLocative)"                                                           class="block w-64 rounded-md bg-white 
                                                            px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                            outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                            focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                    </div>
                                                    <!-- <p>Taux détecté : {{ occupant.tauxValeurLocative }}</p> -->
                                                </div>
                                                <div  class="grid gap-6 mb-6 md:grid-cols-1">
                                                    <div> 
                                                        <label for="Date_devaluation" class="block text-sm/6 font-medium text-primary-txt">Date d'évaluation</label>
                                                        <input 
                                                            type="date" 
                                                            v-model="form.dt_dateEvaluation" 
                                                            required 
                                                            name="dt_dateEvaluation"  
                                                            id="Date_devaluation" 
                                                            class="block w-64 rounded-md bg-white 
                                                                px-3 py-1.5 text-base text-primary-txt outline outline-1 -outline-offset-1 
                                                                outline-primary-only placeholder:text-gray-400 focus:outline focus:outline-2 
                                                                focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                    </div>
                                                </div> 
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bouton de soumission -->
                                
                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn @click.prevent="submitForm" no-shadow no-hover-effect
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
<style scoped>
    .slide-fade-enter-active, .slide-fade-leave-active {
    transition: all 0.5s ease;
    }
    .slide-fade-enter-from {
    opacity: 0;
    transform: translateY(20px);
    }
    .slide-fade-leave-to {
    opacity: 0;
    transform: translateY(20px);
    }

    .slide-fade-enter-active {
    transition: all 0.3s ease-out;
    }

    .slide-fade-leave-active {
    transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
    }

    .slide-fade-enter-from,
    .slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
    }


    .slide-fade-enter-active, .slide-fade-leave-active {
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .slide-fade-enter-from, .slide-fade-leave-to {
        opacity: 0;
        transform: translateX(20px); /* Si tu veux déplacer un peu l'élément, sinon retire ça */
    }
</style>










