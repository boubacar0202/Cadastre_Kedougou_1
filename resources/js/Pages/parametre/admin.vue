<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Link } from '@inertiajs/vue3'; 
import { Head } from "@inertiajs/vue3"; 
import MazBtn from "maz-ui/components/MazBtn"; 
import { ref} from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
 
defineOptions({ layout: DefaultLayout });

const loadingDelete = ref(false); 

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
});
  
const supprimerLotissement = async (user) => {
    if (!user || !user.id) return;
 
    const ok = confirm(`Voullez-vous vraiment suppression : ${user.name} ?`);
    if (!ok) return;
    const code = prompt(`Veuillez enter votre code pour supprimer : ${user.name} :`);
    if (!code) return;

    try {
        loadingDelete.value = true; 
        const url = route('parametre.admin.destroy', user.id);
        const { data } = await axios.delete(url, {
            data: { code } // ✅ envoyer le code au backend
        });

        if (data.success) {
            toast.success(data.message);
            Inertia.reload({ preserveState: false });
        } else {
            toast.error(data.message);
        }

    } catch (error) {
        console.error("Erreur suppression :", error);
        if (error.response?.status === 403) {
            toast.error(error.response.data.message || "Code invalide, veuillez contacter l'administrateur !");
        } else {
            toast.error("Impossible de supprimer l'utilisateur !");
        }
    } finally {
        loadingDelete.value = false;
    }
};
 

</script>

<template>
    <Head title="Administrateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Liste des Utilisateurs</h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">Liste des Utilisateurs</h1>
                        </div>

                        <div class="p-6">  
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl mt-4">
                                <div class="container">
                                    <div class="card-body">
                                        <table class="table table-auto w-full text-base text-left text-gray-500">
                                            <thead
                                                class="sticky top-0 z-10 text-primary-txt text-center uppercase bg-primary-form bg-gray-50 dark:bg-gray-700 dark:text-gray-400 whitespace-nowrap"
                                            >
                                                <tr class="h-10">
                                                <th class="px-2 py-2 text-xs text-white text-center border bg-primary font-bold">N°</th>
                                                <th class="px-2 py-2 text-xs text-white text-center border bg-primary font-bold">Prénom & Nom</th>
                                                <th class="px-2 py-2 text-xs text-white text-center border bg-primary font-bold">Email</th> 
                                                <th class="px-2 py-2 text-xs text-center bg-primary-only font-bold">ACTIONS</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr
                                                    v-for="(user, index) in props.users" 
                                                    :key="user?.id ?? index"
                                                    class="h-8 bg-white text-xs text-primary-txt text-center font-bold hover:bg-primary-form"
                                                >
                                                    <td class="sticky left-0 z-0 border bg-white px-6 py-4 font-bold">{{ index + 1 }}</td> 
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.name || '-' }}</td>
                                                    <td class="px-2 py-1 border whitespace-nowrap">{{ user.email  || '-' }}</td>  
                                                    <td class="px-2 py-1 border flex justify-center gap-2">
                                                        <!-- Edit -->  
                                                        <Link :href="`/parametre/adminEdit/${user.id}`">
                                                            <MazBtn size="large" 
                                                                class="h-8 w-36 text-white bg-gradient-to-r from-green-500 via-green-600 
                                                                to-green-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-green-300 dark:focus:ring-green-800 shadow-lg shadow-green-500/50 
                                                                dark:shadow-lg dark:shadow-green-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center me-2 mb-2">
                                                                Modifier
                                                            </MazBtn>
                                                        </Link>
                            
                                                        <!-- Delete -->
                                                        <MazBtn size="large" color="danger" @click="() => supprimerLotissement(user)" :disabled="loadingDelete"
                                                            class="h-8 w-36 text-white bg-gradient-to-r from-danger-500 via-danger-600 
                                                            to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                            focus:ring-danger-300 dark:focus:ring-danger-800 shadow-lg shadow-danger-500/50 
                                                            dark:shadow-lg dark:shadow-danger-800/80 font-medium rounded-lg text-sm px-5 
                                                            py-2.5 text-center me-2 mb-2">
                                                            Supprimer
                                                        </MazBtn>
                                                    </td>
                                                </tr>

                                                <!-- si aucun lot -->
                                                <tr v-if="!props.users || props.users.length === 0"> 
                                                    <td class="px-6 py-4 text-center text-gray-400 italic" colspan="9">Aucun lotissement trouvé.</td>
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

