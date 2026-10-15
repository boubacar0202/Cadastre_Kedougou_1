<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { toast } from "@/Components/AppToast";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import MazBtn from "maz-ui/components/MazBtn";

defineOptions({ layout: DefaultLayout });

// ✅ Prop au SINGULIER : le service à éditer
const props = defineProps({
    service: Object,
});

// ✅ useForm pré-rempli avec les données existantes
const form = useForm({
    _method: 'put',  // ✅ ESSENTIEL pour Inertia + PUT
    txt_numeroNote: props.service.txt_numeroNote ?? "",
    txt_typeNote:   props.service.txt_typeNote   ?? "",
    txt_titreNote:  props.service.txt_titreNote  ?? "",
    fichierPDF:     null,  // null = ne pas remplacer
});

function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.type !== "application/pdf") {
        toast.error("Veuillez sélectionner un fichier PDF valide.");
        return;
    }
    if (file.size > 100 * 1024 * 1024) {
        toast.error("Le fichier dépasse 100 Mo.");
        return;
    }
    form.fichierPDF = file;
}

const submitForm = () => {
    form.post(route('parametre.service.update', props.service.id), {
        forceFormData: true,  // ✅ ESSENTIEL pour envoyer le fichier
        onSuccess: () => {
            toast.success("Modifié avec succès");
        },
        onError: (errors) => {
            Object.values(errors).forEach(e => toast.error(e));
        }
    });
};
</script>

<template>
    <Head title="Modifier un texte" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modifier un Recueil de texte
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Modification du texte : {{ service.txt_numeroNote }}
                            </h1>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="p-6">
                                <h5 class="text-lg text-primary-txt font-bold mb-4">
                                    Références du texte
                                </h5>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-2">

                                    <BaseSelect
                                        class="sm:col-span-1"
                                        v-model="form.txt_typeNote"
                                        label="Types"
                                        :options="[
                                            {value:'Décret', label:'Décret'},
                                            {value:'Arrété', label:'Arrété'},
                                            {value:'Note de service', label:'Note de service'},
                                            {value:'Circulaire', label:'Circulaire'},
                                            {value:'Ordonnance', label:'Ordonnance'},
                                            {value:'Loi', label:'Loi'},
                                            {value:'Note cadre', label:'Note cadre'},
                                            {value:'Code', label:'Code'},
                                            {value:'Documents', label:'Documents'},
                                            {value:'Autres', label:'Autres'}
                                        ]"
                                    />

                                    <BaseInput
                                        class="sm:col-span-1"
                                        v-model="form.txt_numeroNote"
                                        label="Numéro"
                                        type="text"
                                    />

                                    <BaseInput
                                        class="sm:col-span-3"
                                        v-model="form.txt_titreNote"
                                        label="Titre"
                                        type="text"
                                    />

                                    <BaseInput
                                        type="file"
                                        accept="application/pdf"
                                        class="sm:col-span-1"
                                        label="Remplacer le PDF"
                                        @change="handleFileUpload"
                                    />
                                </div>

                                <!-- Aperçu de l'ancien PDF -->
                                <div v-if="service.fichierPDF" class="mt-4 text-sm">
                                    <span class="text-primary-600">PDF actuel : </span>
                                    <a :href="`/storage/${service.fichierPDF}`" target="_blank"
                                       class="text-primary font-semibold underline">Voir le PDF</a>
                                </div>

                                <div class="flex justify-center mt-4">
                                    <MazBtn
                                        type="submit"
                                        :loading="form.processing"
                                        no-shadow
                                        no-hover-effect
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary hover:bg-gradient-to-br font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                                        size="medium"
                                    >
                                        Mettre à jour
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