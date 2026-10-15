<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import { onMounted, ref, watch, computed } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { router } from "@inertiajs/vue3";

defineOptions({ layout: DefaultLayout });

const today = new Date().toISOString().split("T")[0];

const { arrivees } = defineProps({
    arrivees: Object,
});

const existingPdf = ref(arrivees?.fichierPDF || null);
 
const form = useForm({
    //  courrierarrivee
    txt_numdordre: arrivees?.txt_numdordre || '',
    txt_numbordereau: arrivees?.txt_numbordereau || '',
    txt_numdossier: arrivees?.txt_numdossier || '',
    dt_datearrivee: arrivees?.dt_datearrivee || '',  
    txt_typecourrier: arrivees?.txt_typecourrier || '', 
    txt_caractere: arrivees?.txt_caractere || '', 
    txt_numcourier: arrivees?.txt_numcourier || '',
    dt_datecourier: arrivees?.dt_datecourier || '',
    txt_reference: arrivees?.txt_reference || '',
    txt_categorie: arrivees?.txt_categorie || '',
    txt_designation: arrivees?.txt_designation || '',
    txt_prenomNom: arrivees?.txt_prenomNom || '',
    txt_numLot: arrivees?.txt_numLot || '',
    txt_superficie: arrivees?.txt_superficie || '',
    txt_numTF: arrivees?.txt_numTF || '',
    txt_lotissement: arrivees?.txt_lotissement || '',
    dt_date: arrivees?.dt_date || '',
    txt_heure: arrivees?.txt_heure || '',
    txt_lieu: arrivees?.txt_lieu || '',
    txt_nombrepiece: arrivees?.txt_nombrepiece || '',
    txt_objet: arrivees?.txt_objet || '',
    txt_expediteur: arrivees?.txt_expediteur || '',
    txt_agenttraiteur: arrivees?.txt_agenttraiteur || '',
    txt_observation: arrivees?.txt_observation || "",
    fichierPDF: null,
});

const show = computed(() => form.txt_categorie === "Convocation - Invitation");

const showBail = computed(() =>
    (form.txt_designation === "Demande de Régularisation par voie de Bail" || form.txt_designation === "Demande de CIC") &&
    form.txt_typecourrier !== "BE"
);

const designationsParCategorie = {
    "Demande SERVICES": ["Morcellements", "Réquisition d'immatriculation", "Demande Avis Technique", "Demande de terrain/Echange", "Prospection de terrain",
        "Autorisation de construction", "Demande d'extraits de plan", "Autorisation de lotir", "Demande d'états des lieux", "Demande de délimitation", "Demande de reconstruction",
        "Réquisition DSCOS", "Tribunal", "Litiges", "Demande de situation foncière", "Demande de Cession définitive", "Demande d'évaluation",
        "Demande de Cession définitive a Titre Gratuit", "Demande de Régularisation par voie de Bail", "Demande d'attestation du Cadastre",
        "Réceptions de lotissements", "Demande de CIC", "Duplication de CIC", "Demande de Titre foncier",
        "Autorisation de morceler",
    ],
    "Convocation - Invitation": ["Réunion", "Audience Publique", "Alerte", "Visite de site",
        "Rencontre", "Randonnée", "Marche", "Session", "Congré", "Cérémonie", "Inauguration",
        "Journée dédiée", "Forum", "Formation", "Séminaire", "Caravane", "Atelier",
    ],
    "Information": ["Note de service", "Circulaire", "Rapport",
        "Transmission lettre", "Document administratif", "Texte Juridique",
        "Lettre", "Rapport d'étude impact", "PV - Compte - rendu", "Texte juridique", "Décision - Arrêté", "Délibération", "Résolution", "Autres",
    ],
    "Réclamation - Signalement": ["Dénonciation", "Plainte", "Alerte"],
    "Réquisition - Instruction": ["Réquisition matériel", "Instruction à suivre", "DE"],
};

const designations = ref([]);

const chargerDesignations = (categorie, reset = false) => {
    let liste = designationsParCategorie[categorie] || [];

    if (!reset && form.txt_designation && !liste.includes(form.txt_designation)) {
        liste = [form.txt_designation, ...liste];
    }

    designations.value = liste;

    if (reset) {
        form.txt_designation = "";
    }
};

onMounted(() => {
    if (form.txt_categorie) {
        chargerDesignations(form.txt_categorie);
    }
});

watch(() => form.txt_categorie, (newCategorie, oldCategorie) => {
    if (newCategorie !== oldCategorie) {
        chargerDesignations(newCategorie, true);
    }
});
  
// reupèration references courrier arrivée
watch(
    () => [form.txt_numcourier, form.dt_datecourier],
    ([newNum, newDate]) => {
        if (!newDate) return;
        const date = new Date(newDate);
        if (isNaN(date.getTime())) return;
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        form.txt_reference = `${newNum} du ${day}-${month}-${year}`;
    }
);
  
// 📁 Gestion du changement de fichier
function handleFileChange(event) {
    const file = event.target.files[0];
    if (file) {
        form.fichierPDF = file;
        console.log("Fichier sélectionné:", file.name);
    } else {
        form.fichierPDF = null;
    }
}

// ✅ Fonction suppression fichier PDF
const deleteFile = (arrivee) => {
    if (!arrivee || !arrivee.id) {
        console.error('Aucun départ fourni à deleteFile')
        return
    }

    if (confirm('Voulez-vous vraiment supprimer ce fichier PDF ?')) {
        router.delete(route("arrivee.pdf.destroy", arrivee.id), {
            onSuccess: () => {
                existingPdf.value = null;
                arrivee.fichierPDF = null;
                toast.success("Fichier supprimé avec succès");
            },
            onError: () => {  
                toast.error('Erreur lors de la suppression du fichier ❌')
            },
        })
    }
}

async function submit() {
    try {
        console.log("📤 Envoi avec axios...");
        
        const formData = new FormData();
        
        // Ajouter tous les champs UNE SEULE FOIS
        formData.append('_method', 'PUT'); // Important pour Laravel
        formData.append('txt_numdordre', form.txt_numdordre);
        formData.append('txt_numbordereau', form.txt_numbordereau);
        formData.append('txt_numdossier', form.txt_numdossier);
        formData.append('dt_datearrivee', form.dt_datearrivee);
        formData.append('txt_typecourrier', form.txt_typecourrier);
        formData.append('txt_caractere', form.txt_caractere);
        formData.append('txt_numcourier', form.txt_numcourier);
        formData.append('dt_datecourier', form.dt_datecourier);
        formData.append('txt_reference', form.txt_reference);
        formData.append('txt_categorie', form.txt_categorie);
        formData.append('txt_designation', form.txt_designation);
        formData.append('txt_prenomNom', form.txt_prenomNom);
        formData.append('txt_numLot', form.txt_numLot);
        formData.append('txt_superficie', form.txt_superficie);
        formData.append('txt_numTF', form.txt_numTF);
        formData.append('txt_lotissement', form.txt_lotissement);
        formData.append('dt_date', form.dt_date);
        formData.append('txt_heure', form.txt_heure);
        formData.append('txt_lieu', form.txt_lieu);
        formData.append('txt_nombrepiece', form.txt_nombrepiece);
        formData.append('txt_objet', form.txt_objet);
        formData.append('txt_expediteur', form.txt_expediteur);
        formData.append('txt_agenttraiteur', form.txt_agenttraiteur);
        formData.append('txt_observation', form.txt_observation);
        
        // Ajouter le fichier
        if (form.fichierPDF instanceof File) {
            formData.append('fichierPDF', form.fichierPDF);
        }

        // Debug : vérifiez ce qui est envoyé
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ', value);
        }

        const response = await axios.post(route('arrivee.update', arrivees.id), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        });

        toast.success("Modification réussie!");
        router.visit(route("instancearrivee.create"), { replace: true });
        
    } catch (error) {
        console.error('Erreur détaillée:', error.response?.data);
        if (error.response?.data?.errors) {
            Object.values(error.response.data.errors).forEach(err => {
                toast.error(err);
            });
        } else {
            toast.error('Erreur lors de la modification');
        }
    }
}


</script>

<template>
    <Head title="Courrier Arrivées" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modification : Courriers Arrivées
            </h2>

        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary flex items-center justify-between rounded-t-xl gap-2">
                            <h2 class="text-2xl leading-tight text-white font-bold">Formulaire de modification des Courriers Arrivées</h2>
                        </div>
                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submit" enctype="multipart/form-data">
                            <div class="p-6">
                                <h5 class="text-lg text-primary-txt font-bold mb-4">
                                    Références du courrier
                                </h5>
                                <div class="mb-6">
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2"
                                    >  
                                         
                                        <BaseInput
                                            class="sm:col-span-1"
                                            label="N° Dordre Arrivée"
                                            name="txt_numdordre" 
                                            autocomplete="on" 
                                            v-model="form.txt_numdordre"
                                        />                                   
                                        <BaseInput 
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° BE"
                                            name="txt_numbordereau" 
                                            autocomplete="on"  
                                            v-model="form.txt_numbordereau"
                                        />         
                                        <BaseInput 
                                            v-if="showBail"
                                            class="sm:col-span-1"
                                            label="N° Dossier Foncier"
                                            name="txt_numdossier" 
                                            autocomplete="off"  
                                            v-model="form.txt_numdossier"
                                        />                                                          
                                        <BaseInput  
                                            class="sm:col-span-1"
                                            label="Date Réception"
                                            name="dt_datearrivee" 
                                            autocomplete="off" 
                                            type="date"
                                            required
                                            v-model="form.dt_datearrivee"
                                        />                                                        
                                        <BaseSelect 
                                            class="sm:col-span-1"
                                            label="Type de Courrier"
                                            name="txt_typecourrier"   
                                            id="txt_typecourrier" 
                                            v-model="form.txt_typecourrier"
                                            :options="[
                                                { value: 'Individuel', label: 'Individuel' },
                                                { value: 'BE', label: 'BE' }, 
                                                { value: 'BE à réponses Multiples', label: 'BE à réponses Multiples' },
                                            ]"
                                        />
                                    </div>
                                    <br />
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2"
                                    >                                                                                                                 
                                        <BaseInput  
                                            class="sm:col-span-1"
                                            label="N° Courrier du dossier"
                                            name="txt_numcourier" 
                                            autocomplete="off" 
                                            required
                                            v-model="form.txt_numcourier"
                                        />                                                                                                                                                    
                                        <BaseInput  
                                            class="sm:col-span-1"
                                            label="Date Courrier"
                                            name="dt_datecourier" 
                                            type="date"
                                            :max="today"
                                            autocomplete="off" 
                                            required
                                            v-model="form.dt_datecourier"
                                        />                                                                                                                                                                                                
                                        <BaseInput  
                                            class="sm:col-span-1"
                                            label="Réf.Courrier Arrivée"
                                            name="txt_reference"  
                                            autocomplete="off" 
                                            required
                                            v-model="form.txt_reference"
                                        />                                                                                                                                                                                                                                     
                                        <BaseSelect 
                                            class="sm:col-span-1"
                                            label="Catégorie"
                                            name="txt_categorie"  
                                            autocomplete="off" 
                                            required
                                            id="txt_categorie"
                                            v-model="form.txt_categorie" 
                                            :options="[
                                                {value:'Demande SERVICES', label:'Demande SERVICES'},
                                                {value:'Convocation - Invitation', label:'Convocation - Invitation'},
                                                {value:'Information', label:'Information'},
                                                {value:'Réclamation - Signalement', label:'Réclamation - Signalement'},
                                                {value:'Réquisition - Instruction', label:'Réquisition - Instruction'}
                                            ]" 
                                        /> 
                                        <BaseSelect 
                                            class="sm:col-span-2"
                                            label="Désignation"
                                            name="txt_designation"  
                                            autocomplete="off" 
                                            required
                                            id="txt_designation" 
                                            v-model="form.txt_designation" 
                                            :options="designations.map(designation => ({
                                                value: designation,
                                                label: designation
                                            }))" 
                                        />                                                                                                                                                                                                                                        
                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="Prénom et Nom"
                                            name="txt_prenomNom"  
                                            autocomplete="off"  
                                            v-model="form.txt_prenomNom"
                                        />                                                                                                                                                                                                                                                                             
                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="N° Lot"
                                            name="txt_numLot"  
                                            autocomplete="off"  
                                            v-model="form.txt_numLot"
                                        />                                                                                                                                                                                                                                                                                                                      
                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="Superficie (m²)"
                                            name="txt_superficie"  
                                            autocomplete="off"  
                                            v-model="form.txt_superficie"
                                        />                                                                                                                                                                                                                                                                                                                                                                 
                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-1"
                                            label="N° Titre Mère"
                                            name="txt_numTF"  
                                            autocomplete="off"  
                                            v-model="form.txt_numTF"
                                        />                                                                                                                                                                                                                                                                                                                                                                          
                                        <BaseInput  
                                            v-if="showBail" 
                                            class="sm:col-span-2"
                                            label="Lotissement"
                                            name="txt_lotissement"  
                                            autocomplete="off"  
                                            v-model="form.txt_lotissement"
                                        />                                                                                                                                                                                                                                                                                                                                                        
                                        <BaseInput  
                                            v-if="show" 
                                            class="sm:col-span-1"
                                            label="Date évenement"
                                            name="dt_date"  
                                            autocomplete="off"  
                                            v-model="form.dt_date"
                                            type="date"
                                        />                                                                                                                                                                                                                                                                                                                                                              
                                        <BaseInput  
                                            v-if="show" 
                                            class="sm:col-span-1"
                                            label="Heure évenement"
                                            name="txt_heure"  
                                            autocomplete="off"  
                                            v-model="form.txt_heure"
                                            type="time"
                                        />                                                                                                                                                                                                                                                                                                                                                                 
                                        <BaseInput  
                                            v-if="show" 
                                            class="sm:col-span-1"
                                            label="Lieu évenement"
                                            name="txt_lieu"  
                                            autocomplete="off"  
                                            v-model="form.txt_lieu"
                                        />  
                                        <BaseInput   
                                            class="sm:col-span-1"
                                            label="Nombre de pièces"
                                            name="txt_nombrepiece"  
                                            autocomplete="off"  
                                            v-model="form.txt_nombrepiece"
                                        />                          
                                        <BaseInput   
                                            class="sm:col-span-1"
                                            label="Objet"
                                            name="txt_objet"  
                                            autocomplete="off"  
                                            v-model="form.txt_objet"
                                        />                                                                         
                                        <BaseInput   
                                            class="sm:col-span-1"
                                            label="Expéditeur"
                                            name="txt_expediteur"  
                                            autocomplete="off"  
                                            v-model="form.txt_expediteur"
                                        /> 
                                        <BaseSelect 
                                            class="sm:col-span-1"
                                            label="Agent Traiteur"
                                            name="txt_agenttraiteur"  
                                            autocomplete="off" 
                                            required
                                            id="txt_agenttraiteur" 
                                            v-model="form.txt_agenttraiteur" 
                                            :options="[
                                                { value: 'Saliou FAYE', label: 'Saliou FAYE' }, 
                                                { value: 'Moustapha Diop', label: 'Moustapha Diop' },
                                                { value: 'Daouda Ndiaye', label: 'Daouda Ndiaye' },
                                                { value: 'Abdoulaye Camara', label: 'Abdoulaye Camara' },
                                                { value: 'Oumar Diop', label: 'Oumar Diop' },
                                            ]"
                                        /> 

                                        <BaseSelect 
                                            class="sm:col-span-2"
                                            label="Caractères"
                                            name="txt_caractere"  
                                            autocomplete="off" 
                                            id="txt_caractere" 
                                            v-model="form.txt_caractere" 
                                            :options="[
                                                { value: 'Confidentiel', label: 'Confidentiel' },
                                                { value: 'Urgent', label: 'Urgent' },
                                                { value: 'Secret', label: 'Secret' },
                                            ]"
                                        />    

                                        <BaseInput   
                                            class="sm:col-span-2"
                                            label="Observation"
                                            name="txt_observation"  
                                            autocomplete="off"  
                                            v-model="form.txt_observation"
                                        />  
                                        
                                        <BaseInput
                                            type="file"
                                            class="sm:col-span-4"
                                            label="Importer le Fichier PDF"
                                            name="fichierPDF"
                                            accept="application/pdf"
                                            @change="handleFileChange"
                                        />
                                                                                                            
                                        <div v-if="existingPdf" class="sm:col-span-4 flex items-center gap-3 text-sm text-primary-txt">
                                            <a :href="`/storage/${existingPdf}`" target="_blank" class="underline">
                                                PDF actuel
                                            </a>
                                            <button type="button" class="text-red-600" @click="deleteFile(arrivees)">
                                                Supprimer le PDF
                                            </button>
                                        </div>

                                    </div>
                                    <br /> 
                                </div> 

                                <!-- Bouton de soumission -->

                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn 
                                        type="submit" no-shadow no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark 
                                            to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                            focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                            dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                            py-2.5 text-center me-2 mb-2"
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

