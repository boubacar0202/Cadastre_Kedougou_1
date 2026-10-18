<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import BaseInput from "@/Components/BaseInput.vue"
import BaseSelect from "@/Components/BaseSelect.vue"
import { Head } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, onUnmounted, ref, watch, watchEffect, computed } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";   
import Chart from 'chart.js/auto';

// Props
const props = defineProps({
    pieData: {
        type: Object,
        default: () => ({
            labels: ['Catégorie A', 'Catégorie B', 'Catégorie C'],
            data: [30, 45, 25]
        })
    },
    barData: {
        type: Object,
        default: () => ({
            labels: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin'],
            datasets: [
                {
                    label: 'Extrait de plan',
                    data: [8, 12, 5, 7, 3, 6],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    borderRadius: 4,
                },
                {
                    label: 'Délimitation',
                    data: [4, 7, 3, 5, 2, 4],
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    borderRadius: 4,
                }
            ]
        })
    },
    evaluation: {
        type: Object,
        default: () => ({
            annee: new Date().getFullYear(),
            trimestre: Math.ceil((new Date().getMonth() + 1) / 3),
            label: '',
            lignes: []
        })
    },
    stats: {
        type: Object,
        default: () => ({
            total_dossiers: 0,
            dossiers_delimitation: 0,
            dossiers_extrait_plan: 0,
            dossiers_implantation: 0,
            dossiers_enquete: 0,
            dossiers_terrain_enqueter: 0,
            dossiers_pourcentage_terrain_enqueter: 0,
            total_mutations: 0,
            total_lotissements: 0,
            total_utilisateurs: 0
        })
    }
});

// Références pour les graphiques
const pieChartRef = ref(null);
const barChartRef = ref(null);
let pieChartInstance = null;
let barChartInstance = null;

// Offsets indépendants pour chaque service
const offsetDelimitation = ref(0);
const offsetExtrait = ref(0);

// Données des graphiques
const chartData = ref({
    pie: {
        labels: props.pieData.labels || [],
        data: props.pieData.data || [],
        colors: props.pieData.colors || []
    },
    bar: {
        labels: props.barData.labels,
        datasets: props.barData.datasets || []
    }
});

// Évaluation des utilisateurs
const annee = ref(props.evaluation?.annee || new Date().getFullYear());
const trimestre = ref(props.evaluation?.trimestre || Math.ceil((new Date().getMonth() + 1) / 3));
const evaluationLignes = ref(props.evaluation?.lignes || []);
const searchQuery = ref('');
const sortColumn = ref('score');
const sortDirection = ref('desc');
const isRefreshing = ref(false);
const isLoadingEvaluation = ref(false);
const detailOuvert = ref(false);
const detailChargement = ref(false);
const detail = ref(null);
const filtreDetail = ref('tous');
const filtresDetail = [
    { value: 'tous', label: 'Tous' },
    { value: 'enregistrement', label: 'Enregistrements' },
    { value: 'modification', label: 'Modifications' },
    { value: 'recherche', label: 'Recherches' },
];

const annees = [new Date().getFullYear(), new Date().getFullYear() - 1, new Date().getFullYear() - 2];
const trimestresOptions = [
    { value: 1, label: '1er trimestre' },
    { value: 2, label: '2e trimestre' },
    { value: 3, label: '3e trimestre' },
    { value: 4, label: '4e trimestre' },
];
const colonnesEvaluation = [
    { key: 'utilisateur', label: 'Utilisateur' },
    { key: 'role', label: 'Rôle' },
    { key: 'trimestre', label: 'Trimestre' },
    { key: 'enregistrements', label: 'Enregistrements' },
    { key: 'modifications', label: 'Modifications' },
    { key: 'recherches', label: 'Recherches' },
    { key: 'score', label: 'Score' },
    { key: 'appreciation', label: 'Appréciation' },
];

// Statistiques
const stats = ref(props.stats || {});

// Données pour les deux services
const cessionDataDelimitation = ref({
    months: [],
    total: { nombre_unitaire: 0, montant_total: 0 }
});

const cessionDataExtrait = ref({
    months: [],
    total: { nombre_unitaire: 0, montant_total: 0 }
});

const filteredEvaluation = computed(() => {
    let data = [...evaluationLignes.value];
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        data = data.filter(item =>
            String(item.utilisateur || '').toLowerCase().includes(query)
            || String(item.role || '').toLowerCase().includes(query)
            || String(item.appreciation || '').toLowerCase().includes(query)
        );
    }
    if (sortColumn.value) {
        data.sort((a, b) => {
            const valA = a[sortColumn.value];
            const valB = b[sortColumn.value];
            if (typeof valA === 'number' && typeof valB === 'number') {
                return sortDirection.value === 'asc' ? valA - valB : valB - valA;
            }
            return sortDirection.value === 'asc'
                ? String(valA).localeCompare(String(valB), 'fr')
                : String(valB).localeCompare(String(valA), 'fr');
        });
    }
    return data;
});

const evaluationTotals = computed(() => {
    return filteredEvaluation.value.reduce((acc, row) => {
        acc.enregistrements += row.enregistrements || 0;
        acc.modifications += row.modifications || 0;
        acc.recherches += row.recherches || 0;
        acc.score += row.score || 0;
        return acc;
    }, { enregistrements: 0, modifications: 0, recherches: 0, score: 0 });
});

const traitementsFiltres = computed(() => {
    const lignes = detail.value?.traitements || [];
    if (filtreDetail.value === 'tous') return lignes;
    return lignes.filter(item => item.action === filtreDetail.value);
});

function libelleAction(action) {
    if (action === 'enregistrement') return 'Enregistrement';
    if (action === 'modification') return 'Modification';
    if (action === 'recherche') return 'Recherche';
    return action || 'Traitement';
}

function actionClass(action) {
    if (action === 'enregistrement') return 'bg-emerald-100 text-emerald-800';
    if (action === 'modification') return 'bg-sky-100 text-sky-800';
    if (action === 'recherche') return 'bg-amber-100 text-amber-800';
    return 'bg-gray-100 text-gray-600';
}

async function afficherInformations(row) {
    if (!row?.user_id) {
        toast.error("Impossible d'identifier cet utilisateur.");
        return;
    }
    filtreDetail.value = 'tous';
    detail.value = {
        utilisateur: row.utilisateur,
        role: row.role,
        trimestre: row.trimestre,
        totaux: {
            enregistrements: row.enregistrements || 0,
            modifications: row.modifications || 0,
            recherches: row.recherches || 0,
        },
        traitements: [],
        total: 0,
        tronque: false,
    };
    detailOuvert.value = true;
    detailChargement.value = true;
    try {
        const { data } = await axios.get('/api/evaluation-utilisateur', {
            params: {
                user_id: row.user_id,
                annee: annee.value,
                trimestre: trimestre.value,
            },
        });
        detail.value = data;
    } catch (error) {
        console.error('Erreur détail traitements:', error);
        toast.error("Impossible de charger le détail des traitements.");
        detailOuvert.value = false;
    } finally {
        detailChargement.value = false;
    }
}

function fermerDetail() {
    detailOuvert.value = false;
}

function onEscapeDetail(event) {
    if (event.key === 'Escape' && detailOuvert.value) {
        fermerDetail();
    }
}

function appreciationClass(appreciation) {
    if (appreciation === 'Excellente') return 'bg-green-100 text-green-800';
    if (appreciation === 'Bonne') return 'bg-sky-100 text-sky-800';
    if (appreciation === 'Moyenne') return 'bg-amber-100 text-amber-800';
    if (appreciation === 'Faible') return 'bg-orange-100 text-orange-800';
    return 'bg-gray-100 text-gray-600';
}

// Formatage des montants
function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0 FCFA';
    }
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Création des graphiques
function createCharts() {
    // Pie Chart
    if (pieChartRef.value) {
        if (pieChartInstance) pieChartInstance.destroy();
        const pieData = chartData.value.pie;
        if (pieData.labels?.length && pieData.data?.length) {
            pieChartInstance = new Chart(pieChartRef.value.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: pieData.labels,
                    datasets: [{
                        data: pieData.data,
                        backgroundColor: pieData.colors?.length ? pieData.colors : [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                        ],
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' } },
                        title: { display: true, text: 'Répartition des Dossiers', font: { size: 16, weight: 'bold' }, padding: { bottom: 20 } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    // Bar Chart
    if (barChartRef.value) {
        if (barChartInstance) barChartInstance.destroy();
        const barData = chartData.value.bar;
        if (barData.labels?.length && barData.datasets?.length) {
            barChartInstance = new Chart(barChartRef.value.getContext('2d'), {
                type: 'bar',
                data: { labels: barData.labels, datasets: barData.datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { usePointStyle: true, padding: 20, font: { size: 12, weight: 'bold' } } },
                        title: { display: true, text: 'Comparaison: Extrait de plan vs Délimitation', font: { size: 16, weight: 'bold' }, padding: { bottom: 20 } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }
}

// Chargement des données pour un service avec un offset donné
async function loadCessionData(service, targetRef, offset) {
    try {
        const response = await axios.get('/api/cession-data', {
            params: { slt_service_rendu: service, offset }
        });
        const data = response.data || {};
        if (!data.months) data.months = [];
        data.months = data.months.map(m => ({
            ...m,
            details: m.details || {},
            nombre_unitaire: m.nombre_unitaire ?? 0,
            montant_total: m.montant_total ?? 0
        }));
        if (!data.total) data.total = { nombre_unitaire: 0, montant_total: 0 };
        targetRef.value = data;
    } catch (error) {
        console.error(`Erreur chargement ${service}:`, error);
        toast.error(`Erreur chargement ${service}`);
    }
}

// Fonctions de navigation pour Délimitation
function changerTrimestreDelimitation(delta) {
    const newOffset = offsetDelimitation.value + delta;
    if (newOffset > 0) {
        toast.warning('Vous êtes déjà sur le trimestre en cours pour Délimitation');
        return;
    }
    offsetDelimitation.value = newOffset;
    loadCessionData('Délimitation', cessionDataDelimitation, offsetDelimitation.value);
}

// Fonctions de navigation pour Extrait de plan
function changerTrimestreExtrait(delta) {
    const newOffset = offsetExtrait.value + delta;
    if (newOffset > 0) {
        toast.warning('Vous êtes déjà sur le trimestre en cours pour Extrait de plan');
        return;
    }
    offsetExtrait.value = newOffset;
    loadCessionData('Extrait de plan', cessionDataExtrait, offsetExtrait.value);
}

// Obtenir le libellé d'un trimestre pour un offset donné
function getTrimestreLabel(offset) {
    const date = new Date();
    const currentMonth = date.getMonth();
    let currentQuarter = Math.floor(currentMonth / 3);
    let year = date.getFullYear();
    
    let totalQuarters = currentQuarter + offset;
    let yearOffset = Math.floor(totalQuarters / 4);
    let newQuarter = ((totalQuarters % 4) + 4) % 4;
    year += yearOffset;
    
    if (totalQuarters < 0) {
        yearOffset = Math.ceil(totalQuarters / 4) - 1;
        year = date.getFullYear() + yearOffset;
        newQuarter = ((totalQuarters - yearOffset * 4) % 4 + 4) % 4;
    }
    
    const trimestres = ['1er', '2e', '3e', '4e'];
    return `${trimestres[newQuarter]} trimestre ${year}`;
}

// Mise à jour globale des données
async function updateChartData() {
    isRefreshing.value = true;
    try {
        const response = await axios.get('/api/chart-data', {
            params: { annee: annee.value, trimestre: trimestre.value },
        });
        const data = response.data;
        chartData.value.pie = data.pie || chartData.value.pie;
        chartData.value.bar = data.bar || chartData.value.bar;
        evaluationLignes.value = data.evaluation?.lignes || [];
        const statsResponse = await axios.get('/api/stats');
        stats.value = statsResponse.data;
        
        // Recharger les deux tableaux avec leurs offsets actuels
        await loadCessionData('Délimitation', cessionDataDelimitation, offsetDelimitation.value);
        await loadCessionData('Extrait de plan', cessionDataExtrait, offsetExtrait.value);
        
        createCharts();
        toast.success('Données mises à jour avec succès !');
    } catch (error) {
        console.error('Erreur mise à jour:', error);
        toast.error('Erreur lors du chargement des données');
    } finally {
        isRefreshing.value = false;
    }
}

async function loadEvaluation() {
    isLoadingEvaluation.value = true;
    try {
        const { data } = await axios.get('/api/chart-data', {
            params: { annee: annee.value, trimestre: trimestre.value },
        });
        evaluationLignes.value = data.evaluation?.lignes || [];
    } catch (error) {
        console.error('Erreur évaluation:', error);
        toast.error("Impossible de charger l'évaluation du trimestre.");
    } finally {
        isLoadingEvaluation.value = false;
    }
}

function exportEvaluation() {
    if (filteredEvaluation.value.length === 0) {
        toast.warning('Aucune évaluation à exporter');
        return;
    }
    const headers = colonnesEvaluation.map(col => col.label);
    const keys = colonnesEvaluation.map(col => col.key);
    const csvContent = [
        headers.join(';'),
        ...filteredEvaluation.value.map(row => keys.map(key => row[key] ?? '').join(';'))
    ].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `evaluation_utilisateurs_T${trimestre.value}_${annee.value}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    toast.success('Exportation réussie !');
}

function sortBy(column) {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
}

watch([annee, trimestre], () => {
    loadEvaluation();
});

watch(chartData, () => createCharts(), { deep: true });

onMounted(() => {
    createCharts();
    loadCessionData('Délimitation', cessionDataDelimitation, offsetDelimitation.value);
    loadCessionData('Extrait de plan', cessionDataExtrait, offsetExtrait.value);
    window.addEventListener('keydown', onEscapeDetail);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onEscapeDetail);
});

watchEffect((onInvalidate) => {
    onInvalidate(() => {
        if (pieChartInstance) pieChartInstance.destroy();
        if (barChartInstance) barChartInstance.destroy();
    });
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Évaluation - Dashboard" />
        
        <div class="p-6">
            <!-- En-tête principal -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-primary-txt flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none">
                        <path d="M4 20V4" stroke="#ff7a18" stroke-width="2"/>
                        <path d="M4 20h16" stroke="#ff7a18" stroke-width="2"/>
                        <path d="M7 15l3-4 3 2 4-6" stroke="#00c6ff" stroke-width="2" fill="none"/>
                    </svg>
                    Évaluation Statistique
                </h1>
                <div class="flex gap-2 mt-2 md:mt-0">
                    <MazBtn @click="updateChartData" color="primary" size="sm" :disabled="isRefreshing">
                        <span v-if="isRefreshing" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            Chargement...
                        </span>
                        <span v-else>Rafraîchir</span>
                    </MazBtn>
                </div>
            </div>

            <!-- Cartes statistiques -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-orange-500">
                    <p class="text-sm text-primary-txt">Délimitation</p>
                    <p class="text-2xl font-bold text-orange-600">{{ stats.dossiers_delimitation || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-teal-500">
                    <p class="text-sm text-primary-txt">Extrait de plan</p>
                    <p class="text-2xl font-bold text-teal-600">{{ stats.dossiers_extrait_plan || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-indigo-500">
                    <p class="text-sm text-primary-txt">Implantation</p>
                    <p class="text-2xl font-bold text-primary">{{ stats.dossiers_implantation || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-pink-500">
                    <div class="flex justify-between items-center mb-1">
                        <p class="text-sm text-primary-txt">Terrains Enquêtés</p>
                        <p class="text-xs font-bold text-red-500">Objectif : 1500</p> 
                    </div> 
                    <div class="flex justify-between items-center">
                        <p class="text-2xl font-bold text-pink-600">{{ stats.dossiers_terrain_enqueter || 0 }}</p>  
                        <p class="text-lg font-bold text-pink-600">{{ stats.dossiers_pourcentage_terrain_enqueter || 0 }}%</p>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-primary-txt flex items-center gap-2">
                            <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                            Répartition des Dossiers
                        </h2>
                        <span class="text-sm text-primary-txt">Pie Chart</span>
                    </div>
                    <div class="h-80"><canvas ref="pieChartRef"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-primary-txt flex items-center gap-2">
                            <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                            Évolution Mensuelle
                        </h2>
                        <span class="text-sm text-primary-txt">Bar Chart</span>
                    </div>
                    <div class="h-80"><canvas ref="barChartRef"></canvas></div>
                </div>
            </div>

            <!-- Section États de Cession avec deux tableaux indépendants -->
            <h2 class="text-2xl font-bold text-primary-txt flex items-center gap-2 mb-4">
                <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                États de Cession
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tableau Délimitation -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                        <h3 class="text-lg font-bold text-primary-txt flex items-center gap-2">
                            <span class="w-1 h-6 bg-orange-500 rounded-full justify-start"></span>
                            Délimitation 
                            <span class="text-xs text-primary-txt">{{ getTrimestreLabel(offsetDelimitation) }}</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <!-- Boutons de navigation Délimitation -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button @click="changerTrimestreDelimitation(-1)" 
                                    class="px-2 py-1 text-xs font-medium text-primary-txt hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Préc.
                                </button>
                                <span class="text-primary-txt/60">|</span>
                                <button @click="changerTrimestreDelimitation(1)" 
                                    class="px-2 py-1 text-xs font-medium text-primary-txt hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    Suiv.
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">
                                {{ cessionDataDelimitation.total?.nombre_unitaire || 0 }} total
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-primary-txt">Mois</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-primary-txt">Type</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-primary-txt">Nb</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-primary-txt">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template v-for="(month, mi) in (cessionDataDelimitation.months || [])" :key="mi">
                                    <template v-if="month.details && Object.keys(month.details).length > 0">
                                        <tr v-for="(detail, type) in month.details" :key="type" v-if="type !== 'TOTAL'"
                                            class="hover:bg-orange-50">
                                            <td class="px-4 py-2 text-sm text-center font-medium text-primary-txt">{{ month.name }}</td>
                                            <td class="px-4 py-2"><span class="px-2 py-0.5 bg-orange-100 text-orange-800 rounded-full text-xs">{{ type }}</span></td>
                                            <td class="px-4 py-2 text-center text-sm">{{ detail?.nombre ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail?.montant_total ?? 0) }}</td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center text-primary-txt/60 py-2 text-sm">Aucune donnée pour {{ month.name }}</td>
                                    </tr>
                                    <tr class="bg-orange-50 font-bold border-t-2 border-orange-200">
                                        <td class="px-4 py-2 text-sm text-primary-txt text-center">TOTAL</td>
                                        <td class="px-4 py-2 text-center text-sm text-primary-txt">—</td>
                                        <td class="px-4 py-2 text-center text-orange-600">{{ month.nombre_unitaire ?? 0 }}</td>
                                        <td class="px-4 py-2 text-right text-orange-600">{{ formatCurrency(month.montant_total ?? 0) }}</td>
                                    </tr>
                                </template>
                                <tr class="bg-green-50 font-bold border-t-4 border-green-400">
                                    <td colspan="2" class="px-4 py-3 text-sm text-green-800 text-center">TOTAL GÉNÉRAL TRIMESTRIEL</td>
                                    <td class="px-4 py-3 text-center text-green-700">{{ cessionDataDelimitation.total?.nombre_unitaire ?? 0 }}</td>
                                    <td class="px-4 py-3 text-right text-green-700">{{ formatCurrency(cessionDataDelimitation.total?.montant_total ?? 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tableau Extrait de plan -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                        <h3 class="text-lg font-bold text-primary-txt flex items-center gap-2 justify-start">
                            <span class="w-1 h-6 bg-teal-500 rounded-full"></span>
                            Extrait de plan 
                            <span class="text-xs text-primary-txt">{{ getTrimestreLabel(offsetExtrait) }}</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <!-- Boutons de navigation Extrait de plan -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button @click="changerTrimestreExtrait(-1)" 
                                    class="px-2 py-1 text-xs font-medium text-primary-txt hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Préc.
                                </button>
                                <span class="text-primary-txt/60">|</span>
                                <button @click="changerTrimestreExtrait(1)" 
                                    class="px-2 py-1 text-xs font-medium text-primary-txt hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    Suiv.
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-full text-xs font-semibold">
                                {{ cessionDataExtrait.total?.nombre_unitaire || 0 }} total
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-primary-txt">Mois</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-primary-txt">Type</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-primary-txt">Nb</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-primary-txt">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template v-for="(month, mi) in (cessionDataExtrait.months || [])" :key="mi">
                                    <template v-if="month.details && Object.keys(month.details).length > 0">
                                        <tr v-for="(detail, type) in month.details" :key="type" v-if="type !== 'TOTAL'"
                                            class="hover:bg-teal-50">
                                            <td class="px-4 py-2 text-sm text-center font-medium text-primary-txt">{{ month.name }}</td>
                                            <td class="px-4 py-2"><span class="px-2 py-0.5 bg-teal-100 text-teal-800 rounded-full text-xs">{{ type }}</span></td>
                                            <td class="px-4 py-2 text-center text-sm">{{ detail?.nombre ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail?.montant_total ?? 0) }}</td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center text-primary-txt/60 py-2 text-sm">Aucune donnée pour {{ month.name }}</td>
                                    </tr>
                                    <tr class="bg-teal-50 font-bold border-t-2 border-teal-200">
                                        <td class="px-4 py-2 text-sm text-primary-txt text-center">TOTAL</td>
                                        <td class="px-4 py-2 text-center text-sm text-primary-txt">—</td>
                                        <td class="px-4 py-2 text-center text-teal-600">{{ month.nombre_unitaire ?? 0 }}</td>
                                        <td class="px-4 py-2 text-right text-teal-600">{{ formatCurrency(month.montant_total ?? 0) }}</td>
                                    </tr>
                                </template>
                                <tr class="bg-green-50 font-bold border-t-4 border-green-400">
                                    <td colspan="2" class="px-4 py-3 text-sm text-green-800 text-center">TOTAL GÉNÉRAL TRIMESTRIEL</td>
                                    <td class="px-4 py-3 text-center text-green-700">{{ cessionDataExtrait.total?.nombre_unitaire ?? 0 }}</td>
                                    <td class="px-4 py-3 text-right text-green-700">{{ formatCurrency(cessionDataExtrait.total?.montant_total ?? 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Évaluation des utilisateurs -->
            <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-txt">Évaluation des utilisateurs</h2>
                        <p class="text-sm text-primary-txt/70 mt-1">
                            Selon les traitements du trimestre  
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <BaseSelect v-model.number="annee" :options="annees" wrapper-class="w-28" />
                        <BaseSelect v-model.number="trimestre" :options="trimestresOptions" wrapper-class="w-44" />
                        <BaseInput v-model="searchQuery" placeholder="Rechercher un utilisateur..." wrapper-class="w-56" />
                        <MazBtn @click="exportEvaluation" color="success" size="sm">Exporter CSV</MazBtn>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="col in colonnesEvaluation" :key="col.key"
                                    @click="sortBy(col.key)"
                                    class="px-4 py-3 text-left text-xs font-medium text-primary-txt uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center gap-1">
                                        {{ col.label }}
                                        <span v-if="sortColumn === col.key" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-primary-txt uppercase tracking-wider">Détail</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="isLoadingEvaluation">
                                <td :colspan="colonnesEvaluation.length + 1" class="px-6 py-4 text-center text-primary-txt">Chargement de l'évaluation...</td>
                            </tr>
                            <template v-else>
                            <tr v-for="row in filteredEvaluation" :key="row.user_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-primary-txt">{{ row.utilisateur }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-primary-txt">{{ row.role }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-primary-txt">{{ row.trimestre }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.enregistrements }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.modifications }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.recherches }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-semibold text-primary-txt">{{ row.score }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="appreciationClass(row.appreciation)">
                                        {{ row.appreciation }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-primary-txt">
                                    <MazBtn @click="afficherInformations(row)" color="primary" size="sm">Voir</MazBtn>
                                </td>
                            </tr>
                            </template>
                            <tr v-if="!isLoadingEvaluation && filteredEvaluation.length === 0">
                                <td :colspan="colonnesEvaluation.length + 1" class="px-6 py-4 text-center text-primary-txt">Aucun utilisateur pour ce trimestre</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col gap-2 text-sm text-primary-txt">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                        <span>{{ filteredEvaluation.length }} utilisateur(s)</span>
                        <div class="flex flex-wrap gap-4">
                            <span>Enregistrements : {{ evaluationTotals.enregistrements }}</span>
                            <span>Modifications : {{ evaluationTotals.modifications }}</span>
                            <span>Recherches : {{ evaluationTotals.recherches }}</span>
                            <span>Score cumulé : {{ evaluationTotals.score }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-primary-txt/70">
                        Appréciation : Aucune activité (0), Faible (1 à 9), Moyenne (10 à 24), Bonne (25 à 49), Excellente (50 et plus).
                    </p>
                </div>
            </div>
        </div>

        <div v-if="detailOuvert" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="fermerDetail"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-primary-txt">{{ detail?.utilisateur }}</h3>
                        <p class="mt-1 text-sm text-primary-txt/70">
                            {{ detail?.role || '—' }} · {{ detail?.trimestre }}
                        </p>
                    </div>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-primary-txt hover:bg-gray-100" @click="fermerDetail">
                        Fermer
                    </button>
                </div>

                <div class="flex flex-wrap gap-3 border-b border-gray-100 px-6 py-3 text-sm text-primary-txt">
                    <span>Enregistrements : {{ detail?.totaux?.enregistrements || 0 }}</span>
                    <span>Modifications : {{ detail?.totaux?.modifications || 0 }}</span>
                    <span>Recherches : {{ detail?.totaux?.recherches || 0 }}</span>
                </div>

                <div class="flex flex-wrap gap-2 px-6 py-3">
                    <button
                        v-for="filtre in filtresDetail"
                        :key="filtre.value"
                        type="button"
                        class="rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                        :class="filtreDetail === filtre.value ? 'bg-primary text-white' : 'bg-gray-100 text-primary-txt'"
                        @click="filtreDetail = filtre.value"
                    >
                        {{ filtre.label }}
                    </button>
                </div>

                <div class="overflow-y-auto px-6 pb-6">
                    <p v-if="detailChargement" class="py-8 text-center text-sm text-primary-txt">Chargement des traitements...</p>
                    <p v-else-if="traitementsFiltres.length === 0" class="py-8 text-center text-sm text-primary-txt">
                        Aucun traitement pour ce filtre sur le trimestre.
                    </p>
                    <table v-else class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-primary-txt">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-primary-txt">Action</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-primary-txt">Module</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-primary-txt">Référence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="(item, index) in traitementsFiltres" :key="`${item.date}-${item.action}-${index}`">
                                <td class="whitespace-nowrap px-3 py-2 text-sm text-primary-txt">{{ item.date }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="actionClass(item.action)">
                                        {{ libelleAction(item.action) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm text-primary-txt">
                                    <div>{{ item.module || '—' }}</div>
                                    <div class="text-xs text-primary-txt/60">{{ item.libelle }}</div>
                                </td>
                                <td class="px-3 py-2 text-sm text-primary-txt">{{ item.reference || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="detail?.tronque" class="mt-3 text-xs text-primary-txt/70">
                        Affichage des 300 traitements les plus récents sur {{ detail.total }}.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .animate-spin { animation: spin 1s linear infinite; }
    canvas { max-height: 100%; max-width: 100%; }
    tr { transition: background-color 0.2s ease; }
    th { user-select: none; cursor: pointer; }
    th:hover { background-color: #f3f4f6; }
    @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } .h-80 { height: 250px; } }
</style>