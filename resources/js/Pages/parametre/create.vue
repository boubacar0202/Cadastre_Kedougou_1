<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Head, useForm } from "@inertiajs/vue3";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazInput from "maz-ui/components/MazInput";
import MazBtn from "maz-ui/components/MazBtn";
import MazRadio from "maz-ui/components/MazRadio";
import { onMounted, ref, watch, computed  } from "vue";
import axios from "axios";
import { useToast } from "maz-ui";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
 

defineOptions({ layout: DefaultLayout });

const toast = useToast();
 
 

const form = useForm({
    rd_immatriculation_terrain: "",
    // autres champs obligatoires…
});
  
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
                Pramètres
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
                                <!-- Section Parcelle -->
                                <h5 class="text-lg text-primary-txt font-bold">
                                    Ajouter Lotissement
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                                    <BaseSelect
                                        label="Departement"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />

                                    <BaseInput
                                        label="Arrondissement"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />
 
                                    <BaseInput
                                        label="Commune"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />

                                     
                                    <BaseInput
                                        label="Dénommination"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />

                                     
                                    <BaseInput
                                        label="Nombre de Parcelles"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />
 
                                    <BaseInput
                                        label="Documents Afférants"
                                        name="txt_etat_cession"
                                        autocomplete="on"
                                        required="on"
                                        v-model="form.txt_etat_cession"
                                    />
                                </div>
                                <br>

 
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

