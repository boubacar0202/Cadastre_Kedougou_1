<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Link, Head, useForm, usePage } from '@inertiajs/vue3'; 
import MazBtn from "maz-ui/components/MazBtn"; 
import { computed, ref } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
import BaseInput from "@/Components/BaseInput.vue";
import BaseSelect from "@/Components/BaseSelect.vue";
import DeleteModal from "@/Components/DeleteModal.vue";
 
defineOptions({ layout: DefaultLayout });

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    pendingUsers: {
        type: Array,
        default: () => []
    }
});

const isAdmin = computed(() => usePage().props.auth?.user?.role === "admin");
const comptesAValider = computed(() => props.pendingUsers?.length ?? 0);

const loadingDelete = ref(false);
const validatingId = ref(null);
const showModal = ref(false); 
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const showDeleteUtilisateur = ref(false)
const selectedUtilisateur = ref(null)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const togglePasswordConfirmation = () => {
  showPasswordConfirmation.value = !showPasswordConfirmation.value
}
  
const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
});

const roleOptions = [
    { label: "Administrateur", value: "admin" },
    { label: "Utilisateur", value: "user" },
];

const ouvrirModal = () => {
    form.reset();
    form.clearErrors();
    showPassword.value = false;
    showPasswordConfirmation.value = false;
    showModal.value = true;
};

const fermerModal = () => {
    showModal.value = false;
    showPassword.value = false;
    showPasswordConfirmation.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (!isAdmin.value) {
        toast.error("Seuls les administrateurs peuvent créer un compte.");
        return;
    }

    form.post(route("parametre.admin.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.error) {
                toast.error(page.props.flash.error);
                return;
            }
            toast.success(page.props.flash?.success || "Utilisateur créé avec succès.");
            fermerModal();
        },
        onError: () => {
            toast.error("Veuillez corriger les champs du formulaire.");
        },
    });
}; 


const libelleRole = (role) => {
    if (role === "admin") return "Administrateur";
    if (role === "user") return "Utilisateur";
    return role || "-";
};

const formatDate = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "-";
    return date.toLocaleDateString("fr-FR");
};

const validerCompte = async (user) => {
    if (!user?.id || validatingId.value) return;

    try {
        validatingId.value = user.id;
        const { data } = await axios.post(route("parametre.admin.validate", user.id));

        if (data.success) {
            toast.success(data.message);
            Inertia.reload({ preserveState: false });
        } else {
            toast.error(data.message);
        }
    } catch (error) {
        console.error("Erreur validation :", error);
        toast.error(error.response?.data?.message || "Impossible de valider le compte.");
    } finally {
        validatingId.value = null;
    }
};
  
// Ouvrir le modal de suppression
const supprimerUtilisateur = (user) => {
    if (!user?.id) return;
    selectedUtilisateur.value = user;
    showDeleteUtilisateur.value = true;
};

// Confirmer la suppression
const confirmerSuppressionUtilisateur = async ({ adminCode } = {}) => {
    if (!selectedUtilisateur.value?.id || loadingDelete.value) return;

    if (!adminCode || String(adminCode).trim() === "") {
        toast.error("Veuillez entrer le code administrateur.");
        return;
    }

    try {
        loadingDelete.value = true; 
        const url = route('parametre.admin.destroy', selectedUtilisateur.value.id);
        const { data } = await axios.delete(url, {
            data: { code: String(adminCode).trim() }
        });

        if (data.success) {
            showDeleteUtilisateur.value = false;
            toast.success(data.message);
            Inertia.reload({ preserveState: false });
        } else {
            toast.error(data.message);
        }

    } catch (error) {
        console.error("Erreur suppression :", error);
        toast.error(error.response?.data?.message || "Impossible de supprimer l'utilisateur !");
    } finally {
        loadingDelete.value = false;
    }
};
 

</script>

<template>
    <Head title="Administrateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Liste des administrateurs</h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">

                        <!-- Delete Modal -->
                        <DeleteModal 
                            :show="showDeleteUtilisateur"
                            :itemName="selectedUtilisateur?.name || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteUtilisateur = $event"
                            @confirm="confirmerSuppressionUtilisateur"
                        />

                        <!-- End Delete Modal -->
                        <div class="p-4 border-b bg-primary flex items-center justify-between rounded-t-xl gap-2"> 
                            <h2 class="text-xl font-semibold leading-tight text-white">Liste des administrateurs</h2>
                           
                            <div v-if="isAdmin" class="flex items-center justify-end">
                                <button
                                    type="button"
                                    @click="ouvrirModal"
                                    class="inline-flex items-center gap-2 bg-primary-only text-primary-txt font-semibold leading-tight 
                                    px-4 py-2 rounded-lg shadow-md transition duration-200 hover:brightness-110 hover:-translate-y-0.5 hover:shadow-lg 
                                    hover:bg-primary-only hover:font-bold focus:outline-none focus:ring-2 focus:ring-white/70 active:translate-y-0"
                                >
                                    <i class="fas fa-plus">Ajouter un Utilisateur</i> 
                                </button>
                            </div>
                        </div>

                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                            <div v-if="showModal"
                                class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                                @click.self="fermerModal">
                                <div class="bg-white rounded-xl shadow-md w-full max-w-2xl">
                                    <div class="p-4 border-b bg-primary flex items-center justify-between rounded-t-xl">
                                        <h3 class="text-xl font-semibold leading-tight text-white">Ajouter un Utilisateur</h3>
                                        <button 
                                            type="button" 
                                            class="text-white text-4xl hover:text-red-500 hover:cursor-pointer hover:font-bold transition-colors duration-200 leading-none px-2" 
                                            @click="fermerModal" aria-label="Fermer"
                                        >
                                            ×
                                        </button>
                                    </div>

                                    <form @submit.prevent="submitForm">
                                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <BaseInput
                                                class="md:col-span-2"
                                                label="Nom complet"
                                                name="name"
                                                required
                                                v-model="form.name"
                                                :error="form.errors.name"
                                            />

                                            <BaseInput
                                                label="Adresse email"
                                                name="email"
                                                type="email"
                                                required
                                                v-model="form.email"
                                                :error="form.errors.email"
                                            />

                                            <BaseInput
                                                label="Mot de passe"
                                                name="password"
                                                :type="showPassword ? 'text' : 'password'"
                                                required
                                                autocomplete="new-password"
                                                v-model="form.password"
                                                :error="form.errors.password"
                                            >
                                                <template #append>
                                                    <button
                                                        type="button"
                                                        @click="togglePassword"
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-primary-txt"
                                                    >
                                                        <span v-if="showPassword">👁️</span>
                                                        <span v-else>👁️‍🗨️</span>
                                                    </button>
                                                </template>
                                            </BaseInput>

                                            <BaseInput
                                                label="Confirmation du mot de passe"
                                                name="password_confirmation"
                                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                                required
                                                autocomplete="new-password"
                                                v-model="form.password_confirmation"
                                                :error="form.errors.password_confirmation"
                                            >
                                                <template #append>
                                                    <button
                                                        type="button"
                                                        @click="togglePasswordConfirmation"
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-primary-txt"
                                                    >
                                                        <span v-if="showPasswordConfirmation">👁️</span>
                                                        <span v-else>👁️‍🗨️</span>
                                                    </button>
                                                </template>
                                            </BaseInput> 

                                            <BaseSelect 
                                                label="Rôle"
                                                name="role"
                                                required
                                                placeholder="Choisir un rôle"
                                                :options="roleOptions"
                                                v-model="form.role"
                                                :custom-error="form.errors.role"
                                            />
                                        </div>
                                        <br>

                                        <div class="flex justify-center gap-3 pb-6">
                                            <button 
                                                type="button" 
                                                @click="fermerModal" 
                                                class="px-3 py-1.5 rounded-lg border border-gray-300 text-primary-txt font-medium hover:cursor-pointer hover:bg-red-400 hover:text-white hover:font-bold transition-colors duration-200"
                                            >
                                                Annuler
                                            </button>
                                            <MazBtn
                                                no-shadow
                                                no-hover-effect
                                                type="submit"
                                                :disabled="form.processing"
                                                class="h-10 text-white bg-gradient-to-r from-primary via-primary-dark to-primary
                                                    hover:bg-gradient-to-br hover:cursor-pointer hover:font-bold focus:ring-4 focus:outline-none focus:ring-blue-300
                                                    font-medium rounded-lg text-xs px-5 py-2 text-center"
                                                size="medium"
                                            >
                                                Enregistrer
                                            </MazBtn>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </Transition> 
 
                        <!-- Tableau des Utilisateurs -->
                        <div class="p-6">  
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl mt-4">
                                <div class="container">
                                    <div class="card-body">
                                        <table class="table table-auto w-full text-base text-left text-primary-txt">
                                            <thead
                                                class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap"
                                            >
                                                <tr class="h-10  text-sm text-primary-txt text-center font-semibold">
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">N°</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Prénom & Nom</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Email</th> 
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Rôle</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">ACTIONS</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(user, index) in props.users" 
                                                    :key="user?.id ?? index"
                                                    class="h-8 bg-white text-sm text-primary-txt text-center hover:bg-primary-form"
                                                >
                                                    <td class="sticky left-0 z-0 border bg-white px-6 py-4">{{ index + 1 }}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.name || '-' }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.email  || '-' }}</td>  
                                                    <td class="px-2 py-1 border whitespace-nowrap bg-primary-only">{{ user.role || '-' }}</td>
                                                    <td class="px-2 py-1 border flex justify-center gap-2">
                                                        <!-- Edit -->  
                                                        <Link :href="`/parametre/adminEdit/${user.id}`">
                                                            <MazBtn no-shadow no-hover-effect size="large" 
                                                                class="h-9 w-8 text-white bg-gradient-to-r from-green-500 via-green-600 
                                                                to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50 
                                                                dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center me-2 mb-2">
                                                                ✏️
                                                            </MazBtn>
                                                        </Link>
                            
                                                        <!-- Delete -->
                                                        <MazBtn v-if="isAdmin" size="large" color="danger" @click="() => supprimerUtilisateur(user)" :disabled="loadingDelete"
                                                            class="h-9 w-8 text-white bg-gradient-to-r from-danger-500 via-danger-600 
                                                            to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                            focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50 
                                                            dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-5 
                                                            py-2.5 text-center me-2 mb-2">
                                                            🗑
                                                        </MazBtn>
                                                    </td>
                                                </tr>

                                                <!-- si aucun Utilisateur -->
                                                <tr v-if="!props.users || props.users.length === 0"> 
                                                    <td class="px-6 py-4 text-center text-primary-txt/60 italic" colspan="9">Aucun administrateur trouvé.</td>
                                                </tr> 
                                            </tbody>
                                        </table> 
                                    </div> 
                                </div>

                            </div>
                        </div>
 
                        <!-- Tableau des validation de comptes -->
                        <div v-if="isAdmin && comptesAValider > 0" class="p-6">
                            <div class="p-4 border-b bg-primary-form flex items-center justify-between rounded-t-xl">
                                <h2 class="text-xl font-semibold leading-tight text-primary-txt">Comptes en attente de validation</h2>
                                <span class="text-primary-txt font-semibold">{{ comptesAValider }}</span>
                            </div>
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-b-xl">
                                <div class="container">
                                    <div class="card-body">
                                        <table class="table table-auto w-full text-base text-left text-primary-txt">
                                            <thead
                                                class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap"
                                            >
                                                <tr class="h-10  text-sm text-white text-center font-semibold">
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">N°</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Prénom & Nom</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Email</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Rôle</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Inscription</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">ACTIONS</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(user, index) in props.pendingUsers"
                                                    :key="user?.id ?? index"
                                                    class="h-8 bg-white text-sm text-primary-txt text-center hover:bg-primary-form"
                                                >
                                                    <td class="sticky left-0 z-0 border bg-white px-6 py-4">{{ index + 1 }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.name || '-' }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.email  || '-' }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap bg-primary-only">{{ libelleRole(user.role) }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ formatDate(user.created_at) }}</td>
                                                    <td class="px-2 py-1 border">
                                                        <div class="flex justify-center items-center gap-2">
                                                            <MazBtn
                                                                no-shadow
                                                                no-hover-effect
                                                                size="small"
                                                                :disabled="validatingId === user.id"
                                                                @click="() => validerCompte(user)"
                                                                class="h-8 text-white bg-gradient-to-r from-green-500 via-green-600
                                                                to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                                focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50
                                                                dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-3
                                                                py-2 text-center"
                                                            >
                                                                Valider
                                                            </MazBtn>

                                                            <MazBtn
                                                                size="small"
                                                                color="danger"
                                                                @click="() => supprimerUtilisateur(user)"
                                                                :disabled="loadingDelete"
                                                                class="h-8 text-white bg-gradient-to-r from-danger-500 via-danger-600
                                                                to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none
                                                                focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50
                                                                dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-3
                                                                py-2 text-center"
                                                            >
                                                                Supprimer
                                                            </MazBtn>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr v-if="!props.pendingUsers || props.pendingUsers.length === 0">
                                                    <td class="px-6 py-4 text-center text-primary-txt/60 italic" colspan="6">Aucun compte en attente de validation.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Tableau des validation de comptes -->

 
                        <!--Registre Opperationnel-->
                        <div class="p-6">
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl mt-4">
                                <div class="container">
                                    <div class="card-body">
                                        <table class="table table-auto w-full text-base text-left text-primary-txt">
                                            <thead
                                                class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-primary-txt whitespace-nowrap"
                                            >
                                                <tr class="h-10  text-sm text-primary-txt text-center font-semibold">
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">N°</th>
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">Prénom & Nom</th> 
                                                    <th class="px-2 py-2 border bg-primary-only font-bold">ACTIONS</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr>
                                                    <td class="px-6 py-4 text-center text-primary-txt/60 italic" colspan="9">Aucun registre opperationnel trouvé.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

