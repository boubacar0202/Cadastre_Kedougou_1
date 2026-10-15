<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import MazBtn from 'maz-ui/components/MazBtn';
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { toast } from "@/Components/AppToast";
import * as XLSX from 'xlsx';

defineOptions({ layout: DefaultLayout });

  
// import { toast } from 'vue3-toastify' 
const props = defineProps({

    arrivee: Object,    
    arrivees: Array,

    departs: Array,
    depart: Object,
 
    trimestres: Array,
    countsByTrimestre: Object,
    countsTraiterByTrimestre: Object,
    stockFin: Object,
    courriersRetourners: Number,
    instancesRestantes: Object,

    trimestre: String,
    prev: String,
    next: String,
    trimestreLabel: String,
 
});
 
// ✅ Label du trimestre 

// ✅ Trimestre actuel (backend ou frontend)
// Trimestre actuel   
const trimestreLabel = computed(() => props.trimestreLabel);
const now = new Date();
const currentQuarter = Math.floor(now.getMonth() / 3) + 1;
const currentYear = now.getFullYear();

const designations = [
    "Morcellements",
    "Réquisition d\'immatriculation",
    'Demande Avis Technique',
    "Demande de terrain/Echange",
    "Prospection de terrain",
    "Autorisation de construction",
    "Autorisation de lotir",
    "Demande d\'états des lieux",
    "Demande de délimitation",
    "Demande de reconstruction",
    "Réquisition DSCOS", 
    "Tribunal",
    "Litiges",
    "Demande d\'extraits de plan",
    "Demande de situation foncière", 
    "Demande de Cession définitive a Titre Gratuit",
    "Demande de Régularisation par voie de Bail",
    "Demande d\'attestation du Cadastre",
    "Projets de Lotissements reçus",
    "Réceptions de lotissements",
    "Lotissements réalisés sans respect des procédures",
    "Duplication de CIC",
    "Demande de Titre foncier",
    "Autirisationde morceler",
    "Demande d\'évaluation",
    "Nombre de fiches de mise à jour reçues",
    "Nombre de dossiers techniques en attente de fiches de mise à jour",
]; 

// ✅ Fonction d'export Excel
const exportToExcel = () => {
    try {
        // 1. Construire les données du tableau
        const data = designations.map((designation, index) => ({
            'N°': index + 1,
            'Désignation': designation,
            'Stock Fin Précédent': props.stockFin?.[designation] ?? 0,
            'Reçus': props.countsByTrimestre?.[designation] ?? 0,
            'Traités': props.countsTraiterByTrimestre?.[designation] ?? 0,
            'Retournés': props.courriersRetourners ?? 0,
            'Instances': props.instancesRestantes?.[designation] ?? 0,
        }));

        // 2. Ajouter une ligne de totaux (optionnel)
        const totals = data.reduce((acc, row) => {
            acc['Stock Fin Précédent'] += row['Stock Fin Précédent'];
            acc['Reçus'] += row['Reçus'];
            acc['Traités'] += row['Traités'];
            acc['Retournés'] += row['Retournés'];
            acc['Instances'] += row['Instances'];
            return acc;
        }, {
            'N°': '',
            'Désignation': 'TOTAL',
            'Stock Fin Précédent': 0,
            'Reçus': 0,
            'Traités': 0,
            'Retournés': 0,
            'Instances': 0,
        });

        data.push(totals);

        // 3. Créer la feuille et le classeur
        const worksheet = XLSX.utils.json_to_sheet(data);

        // 4. Ajuster la largeur des colonnes
        worksheet['!cols'] = [
            { wch: 6 },   // N°
            { wch: 55 },  // Désignation
            { wch: 20 },  // Stock Fin Précédent
            { wch: 12 },  // Reçus
            { wch: 12 },  // Traités
            { wch: 12 },  // Retournés
            { wch: 12 },  // Instances
        ];

        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(
            workbook,
            worksheet,
            `Trimestre ${trimestreLabel.value}`
        );

        // 5. Télécharger le fichier
        const fileName = `Trimestre_${trimestreLabel.value?.replace(/\s+/g, '_') || props.trimestre}_${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success('Export Excel réussi !');
    } catch (error) {
        console.error('Erreur export Excel:', error);
        toast.error("Erreur lors de l'export Excel.");
    }
};
 
// ✅ Navigation sécurisée entre trimestres
const goTo = (trimestre) => {
    // 🔴 Sécurité absolue
    if (!trimestre || typeof trimestre !== 'string') {
        return toast.error("Aucun trimestre disponible.");
    }

    const [year, quarter] = trimestre.split('-').map(Number);

    if (Number.isNaN(year) || Number.isNaN(quarter)) {
        return toast.error("Trimestre invalide.");
    }

    // Bloquer si trimestre futur
    if (year > currentYear || (year === currentYear && quarter > currentQuarter)) {
        return toast.error(
            `Le trimestre ${year}-T${quarter} est dans le futur.`
        );
    }

    router.get(route('parametre.trimestre.create'), { trimestre }, {
        preserveState: true,
        replace: true
    });
};

</script>

<template>
    <Head title="Trimestre" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Trimestres
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2 py-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary rounded-t-xl flex flex-wrap items-center justify-between gap-3">
                            <h1 class="text-lg text-white font-semibold">Trimestre {{ trimestreLabel }}</h1>
                            <div class="flex flex-wrap items-center gap-2">
                            <MazBtn
                                size="medium"
                                class="h-8 px-4 text-primary hover:text-primary-only bg-gradient-to-r from-primary-dark via-primary-dark to-primary-dark font-medium rounded-lg text-sm"
                                @click="goTo(prev)"
                            >
                                Retour
                            </MazBtn>
                            <MazBtn
                                size="medium"
                                class="h-8 px-4 text-primary hover:text-primary-only bg-gradient-to-r from-primary-dark via-primary-dark to-primary-dark font-medium rounded-lg text-sm"
                                @click="goTo(next)"
                            >
                                Suivant
                            </MazBtn>
                            <MazBtn
                                size="medium"
                                class="h-8 px-4 text-white bg-primary-only font-medium rounded-lg text-sm"
                                @click="exportToExcel"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Exporter fichier Excel
                            </MazBtn>
                            </div>
                        </div>
                        <div class="p-6 overflow-auto">
                    <table class="min-w-full text-sm text-left text-primary-txt">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">N°</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only whitespace-nowrap">Désignation</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">Stock Fin Précédent</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">Reçus</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">Traités</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">Retournés</th>
                                <th class="px-4 py-2 text-left text-xs text-primary font-bold border bg-primary-only">Instances</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(designation, index) in designations" :key="index"
                                class="hover:bg-primary-only">
                                <td class="px-4 py-2 text-left border border-b text-primary-txt text-sm">{{ index + 1 }}</td>
                                <td class="px-4 py-2 text-left border border-b text-primary-txt text-sm "> {{ designation }}</td>
                                <td class="px-4 py-2 border border-b text-primary-txt font-bold text-sm text-center">{{ props.stockFin[designation] ?? 0 }}</td> 
                                <td class="px-4 py-2 border border-b text-primary-txt font-bold text-sm text-center">{{ props.countsByTrimestre[designation] ?? 0 }}</td>                                
                                <td class="px-4 py-2 border border-b text-primary-txt font-bold text-sm text-center">{{ props.countsTraiterByTrimestre[designation] ?? 0 }}</td>
                                <td class="px-4 py-2 border border-b text-primary-txt font-bold text-sm text-center">{{ props.courriersRetourners ?? 0 }}</td>
                                <td class="px-4 py-2 border border-b text-primary-txt font-bold text-sm text-center">{{ props.instancesRestantes[designation] ?? 0 }}</td>
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

 