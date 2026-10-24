<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import DeleteModal from "@/Components/DeleteModal.vue";
import { toast } from "@/Components/AppToast";
import { Head, router, useForm } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { computed, ref, watch } from "vue";
import axios from "axios";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    baremes: { type: Array, default: () => [] },
});

const groupes = [
    { value: "batiment", label: "Bâtiment" },
    { value: "secteur", label: "Secteur" },
    { value: "cours_amenagee", label: "Cours aménagée" },
    { value: "cloture", label: "Clôture" },
    { value: "coefficient", label: "Coefficient" },
    { value: "taux_locatif", label: "Taux locatif" },
];

const groupesAvecType = ["batiment", "taux_locatif"];

const typesBatiment = [
    { value: "Maison individuelle", label: "Maison individuelle" },
    { value: "Immeuble collectif", label: "Immeuble collectif" },
];

const libelleGroupe = (groupe) => groupes.find((item) => item.value === groupe)?.label ?? groupe;

const form = useForm({
    groupe: "batiment",
    type: "Maison individuelle",
    code: "",
    prix: "",
    ordre: "",
    actif: true,
});

const filtreGroupe = ref("");
const lignes = ref([]);
const selectedBareme = ref(null);
const showDeleteModal = ref(false);
const loadingDelete = ref(false);

function copierLignes(rows) {
    lignes.value = (rows ?? []).map((bareme) => ({
        ...bareme,
        type: bareme.type || "",
        prix: Number(bareme.prix),
        ordre: Number(bareme.ordre ?? 0),
        actif: Boolean(bareme.actif),
    }));
}

copierLignes(props.baremes);
watch(() => props.baremes, copierLignes);

const lignesFiltrees = computed(() =>
    lignes.value.filter((ligne) => !filtreGroupe.value || ligne.groupe === filtreGroupe.value)
);

const submitForm = () => {
    form.transform((data) => ({
        ...data,
        type: groupesAvecType.includes(data.groupe) ? data.type : "",
        actif: true,
        ordre: data.ordre === "" ? null : Number(data.ordre),
        prix: Number(data.prix),
    })).post(route("parametre.bareme.store"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Barème enregistré");
            form.reset("code", "prix", "ordre");
        },
        onError: (errors) => {
            Object.values(errors).forEach((message) => toast.error(message));
        },
    });
};

const enregistrer = (ligne) => {
    router.put(
        route("parametre.bareme.update", ligne.id),
        {
            groupe: ligne.groupe,
            type: groupesAvecType.includes(ligne.groupe) ? ligne.type : "",
            code: ligne.code,
            prix: Number(ligne.prix),
            ordre: Number(ligne.ordre),
            actif: Boolean(ligne.actif),
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success("Barème modifié"),
            onError: (errors) => {
                Object.values(errors).forEach((message) => toast.error(message));
            },
        }
    );
};

const demanderSuppression = (ligne) => {
    selectedBareme.value = ligne;
    showDeleteModal.value = true;
};

const confirmerSuppression = async ({ adminCode } = {}) => {
    if (!selectedBareme.value || loadingDelete.value) return;

    if (!adminCode || String(adminCode).trim() === "") {
        toast.error("Veuillez entrer le code administrateur.");
        return;
    }

    try {
        loadingDelete.value = true;
        const { data } = await axios.delete(route("parametre.bareme.destroy", selectedBareme.value.id), {
            data: { code: String(adminCode).trim() },
        });

        if (data.success) {
            toast.success(data.message);
            showDeleteModal.value = false;
            router.reload({ preserveScroll: true });
        } else {
            toast.error(data.message || "Code invalide.");
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Impossible de supprimer le barème.");
    } finally {
        loadingDelete.value = false;
    }
};
</script>

<template>
    <Head title="Barèmes d'évaluation" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Barèmes d'évaluation</h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Nouveau barème</h1>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-2">
                                <BaseSelect v-model="form.groupe" label="Groupe" :options="groupes" />
                                <BaseSelect
                                    v-if="groupesAvecType.includes(form.groupe)"
                                    v-model="form.type"
                                    label="Type"
                                    :options="typesBatiment"
                                />
                                <BaseInput v-model="form.code" label="Code" type="text" />
                                <BaseInput v-model="form.prix" label="Prix / valeur" type="number" min="0" step="any" />
                                <p v-if="form.groupe === 'coefficient'" class="sm:col-span-6 text-sm text-primary-txt">
                                    Codes utilisés par l'évaluation : tva, surface_utile, terrain_bati.
                                </p>
                                <p v-else-if="form.groupe === 'taux_locatif'" class="sm:col-span-6 text-sm text-primary-txt">
                                    Le code reprend la catégorie du bâtiment. Le code defaut s'applique aux autres catégories.
                                </p>
                                <BaseInput v-model="form.ordre" label="Ordre" type="number" min="0" />
                                <div class="sm:col-span-6 flex justify-center">
                                    <MazBtn
                                        type="submit"
                                        :loading="form.processing"
                                        class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary font-medium rounded-lg text-sm px-5 py-2.5 mt-2"
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

        <div class="pb-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl flex items-center justify-between gap-3">
                            <h1 class="text-lg text-white font-semibold">
                                Barèmes ({{ lignesFiltrees.length }})
                            </h1>
                            <BaseSelect
                                v-model="filtreGroupe"
                                :options="groupes"
                                placeholder="Tous les groupes"
                                wrapper-class="w-56"
                            />
                        </div>

                        <DeleteModal
                            :show="showDeleteModal"
                            :itemName="selectedBareme ? `${libelleGroupe(selectedBareme.groupe)} ${selectedBareme.code}` : ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteModal = $event"
                            @confirm="confirmerSuppression"
                        />

                        <div class="p-6 overflow-auto">
                            <table class="table-auto w-full text-sm text-left text-primary-txt">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Groupe</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Type</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Code</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Prix / valeur</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Ordre</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Actif</th>
                                        <th class="px-3 py-2 text-xs text-primary font-bold text-center border bg-primary-only">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="ligne in lignesFiltrees" :key="ligne.id" class="bg-white text-center">
                                        <td class="px-2 py-2 border border-primary-only">{{ libelleGroupe(ligne.groupe) }}</td>
                                        <td class="px-2 py-2 border border-primary-only">
                                            <BaseSelect
                                                v-if="groupesAvecType.includes(ligne.groupe)"
                                                v-model="ligne.type"
                                                :options="typesBatiment"
                                                wrapper-class="w-full"
                                            />
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-2 py-1 border border-primary-only">
                                            <BaseInput v-model="ligne.code" type="text" input-class="text-center" wrapper-class="w-full" />
                                        </td>
                                        <td class="px-2 py-1 border border-primary-only">
                                            <BaseInput v-model.number="ligne.prix" type="number" min="0" step="any" input-class="text-right" wrapper-class="w-full" />
                                        </td>
                                        <td class="px-2 py-1 border border-primary-only">
                                            <BaseInput v-model.number="ligne.ordre" type="number" min="0" input-class="text-center" wrapper-class="w-full" />
                                        </td>
                                        <td class="px-2 py-1 border border-primary-only">
                                            <input v-model="ligne.actif" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary" />
                                        </td>
                                        <td class="px-2 py-1 border border-primary-only whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="h-8 text-white bg-gradient-to-r from-green-500 via-green-600 to-green-700 font-medium rounded-xl text-xs px-3 mr-2"
                                                @click="enregistrer(ligne)"
                                            >
                                                Enregistrer
                                            </button>
                                            <button
                                                type="button"
                                                class="h-8 text-white bg-red-500 via-red-600 to-red-700 font-medium rounded-xl text-xs px-3"
                                                @click="demanderSuppression(ligne)"
                                            >
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="lignesFiltrees.length === 0">
                                        <td colspan="7" class="px-3 py-6 text-center border border-primary-only">Aucun barème</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
