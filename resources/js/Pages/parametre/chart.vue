<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import BaseInput from "@/Components/BaseInput.vue"
import { Head } from "@inertiajs/vue3";
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, ref, watch, watchEffect, computed } from "vue";
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
    gridData: {
        type: Array,
        default: () => []
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

// Données du grid
const gridDataRef = ref(props.gridData || []);
const searchQuery = ref('');
const sortColumn = ref('');
const sortDirection = ref('asc');
const isRefreshing = ref(false);

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

// Computed pour les totaux du grid
const gridTotals = computed(() => {
    if (!gridDataRef.value.length) {
        return { dossiers: 0, extraits_de_plan: 0, delimitation: 0, rejetes: 0 };
    }
    return {
        dossiers: gridDataRef.value.reduce((sum, row) => sum + (row.dossiers || 0), 0),
        extraits_de_plan: gridDataRef.value.reduce((sum, row) => sum + (row.extraits_de_plan || 0), 0),
        delimitation: gridDataRef.value.reduce((sum, row) => sum + (row.delimitation || 0), 0),
        rejetes: gridDataRef.value.reduce((sum, row) => sum + (row.rejetes || 0), 0)
    };
});

// Computed pour les données filtrées du grid
const filteredGridData = computed(() => {
    let data = [...gridDataRef.value];
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        data = data.filter(item => 
            Object.values(item).some(val => 
                String(val).toLowerCase().includes(query)
            )
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
                ? String(valA).localeCompare(String(valB))
                : String(valB).localeCompare(String(valA));
        });
    }
    return data;
});

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
        const response = await axios.get('/api/chart-data');
        const data = response.data;
        chartData.value.pie = data.pie || chartData.value.pie;
        chartData.value.bar = data.bar || chartData.value.bar;
        gridDataRef.value = data.grid || [];
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

// Export CSV
function exportGridData() {
    if (filteredGridData.value.length === 0) {
        toast.warning('Aucune donnée à exporter');
        return;
    }
    const headers = Object.keys(filteredGridData.value[0]);
    const csvContent = [
        headers.join(','),
        ...filteredGridData.value.map(row => headers.map(key => row[key] || '').join(','))
    ].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `statistiques_${new Date().toISOString().split('T')[0]}.csv`;
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

function addGridRow() {
    gridDataRef.value.push({
        mois: `Nouveau ${gridDataRef.value.length + 1}`,
        dossiers: Math.floor(Math.random() * 30) + 1,
        extraits_de_plan: Math.floor(Math.random() * 15) + 1,
        delimitation: Math.floor(Math.random() * 15) + 1,
        rejetes: Math.floor(Math.random() * 5)
    });
    toast.success('Ligne ajoutée avec succès !');
}

function removeGridRow(index) {
    gridDataRef.value.splice(index, 1);
    toast.success('Ligne supprimée !');
}

watch(chartData, () => createCharts(), { deep: true });

onMounted(() => {
    createCharts();
    loadCessionData('Délimitation', cessionDataDelimitation, offsetDelimitation.value);
    loadCessionData('Extrait de plan', cessionDataExtrait, offsetExtrait.value);
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
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
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
                    <p class="text-sm text-gray-500">Délimitation</p>
                    <p class="text-2xl font-bold text-orange-600">{{ stats.dossiers_delimitation || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-teal-500">
                    <p class="text-sm text-gray-500">Extrait de plan</p>
                    <p class="text-2xl font-bold text-teal-600">{{ stats.dossiers_extrait_plan || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500">Implantation</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ stats.dossiers_implantation || 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-pink-500">
                    <div class="flex justify-between items-center mb-1">
                        <p class="text-sm text-gray-500">Terrains Enquêtés</p>
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
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                            Répartition des Dossiers
                        </h2>
                        <span class="text-sm text-gray-500">Pie Chart</span>
                    </div>
                    <div class="h-80"><canvas ref="pieChartRef"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                            Évolution Mensuelle
                        </h2>
                        <span class="text-sm text-gray-500">Bar Chart</span>
                    </div>
                    <div class="h-80"><canvas ref="barChartRef"></canvas></div>
                </div>
            </div>

            <!-- Section États de Cession avec deux tableaux indépendants -->
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2 mb-4">
                <span class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></span>
                États de Cession
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tableau Délimitation -->
                <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-2">
                        <h3 class="text-lg font-bold text-gray-700 flex items-center gap-2">
                            <span class="w-1 h-6 bg-orange-500 rounded-full justify-start"></span>
                            Délimitation 
                            <span class="text-xs text-gray-500">{{ getTrimestreLabel(offsetDelimitation) }}</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <!-- Boutons de navigation Délimitation -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button @click="changerTrimestreDelimitation(-1)" 
                                    class="px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Préc.
                                </button>
                                <span class="text-gray-400">|</span>
                                <button @click="changerTrimestreDelimitation(1)" 
                                    class="px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
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
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Mois</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Type</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Nb</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template v-for="(month, mi) in (cessionDataDelimitation.months || [])" :key="mi">
                                    <template v-if="month.details && Object.keys(month.details).length > 0">
                                        <tr v-for="(detail, type) in month.details" :key="type" v-if="type !== 'TOTAL'"
                                            class="hover:bg-orange-50">
                                            <td class="px-4 py-2 text-sm text-center font-medium text-gray-800">{{ month.name }}</td>
                                            <td class="px-4 py-2"><span class="px-2 py-0.5 bg-orange-100 text-orange-800 rounded-full text-xs">{{ type }}</span></td>
                                            <td class="px-4 py-2 text-center text-sm">{{ detail?.nombre ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail?.montant_total ?? 0) }}</td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center text-gray-400 py-2 text-sm">Aucune donnée pour {{ month.name }}</td>
                                    </tr>
                                    <tr class="bg-orange-50 font-bold border-t-2 border-orange-200">
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">TOTAL</td>
                                        <td class="px-4 py-2 text-center text-sm text-gray-500">—</td>
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
                        <h3 class="text-lg font-bold text-gray-700 flex items-center gap-2 justify-start">
                            <span class="w-1 h-6 bg-teal-500 rounded-full"></span>
                            Extrait de plan 
                            <span class="text-xs text-gray-500">{{ getTrimestreLabel(offsetExtrait) }}</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <!-- Boutons de navigation Extrait de plan -->
                            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                                <button @click="changerTrimestreExtrait(-1)" 
                                    class="px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    Préc.
                                </button>
                                <span class="text-gray-400">|</span>
                                <button @click="changerTrimestreExtrait(1)" 
                                    class="px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white hover:shadow rounded transition-all flex items-center gap-1">
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
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Mois</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Type</th>
                                    <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600">Nb</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template v-for="(month, mi) in (cessionDataExtrait.months || [])" :key="mi">
                                    <template v-if="month.details && Object.keys(month.details).length > 0">
                                        <tr v-for="(detail, type) in month.details" :key="type" v-if="type !== 'TOTAL'"
                                            class="hover:bg-teal-50">
                                            <td class="px-4 py-2 text-sm text-center font-medium text-gray-800">{{ month.name }}</td>
                                            <td class="px-4 py-2"><span class="px-2 py-0.5 bg-teal-100 text-teal-800 rounded-full text-xs">{{ type }}</span></td>
                                            <td class="px-4 py-2 text-center text-sm">{{ detail?.nombre ?? 0 }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail?.montant_total ?? 0) }}</td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center text-gray-400 py-2 text-sm">Aucune donnée pour {{ month.name }}</td>
                                    </tr>
                                    <tr class="bg-teal-50 font-bold border-t-2 border-teal-200">
                                        <td class="px-4 py-2 text-sm text-gray-700 text-center">TOTAL</td>
                                        <td class="px-4 py-2 text-center text-sm text-gray-500">—</td>
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

            <!-- Grid -->
            <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <h2 class="text-lg font-semibold text-gray-700">Statistiques Détaillées</h2>
                    <div class="flex flex-wrap gap-2 w-full md:w-auto">
                        <BaseInput v-model="searchQuery" placeholder="Rechercher..." class="w-48" />
                        <MazBtn @click="exportGridData" color="success" size="sm">Exporter CSV</MazBtn>
                        <MazBtn @click="addGridRow" color="primary" size="sm">+ Ajouter</MazBtn>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="(value, key) in (filteredGridData[0] || {})" :key="key"
                                    @click="sortBy(key)"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center gap-1">
                                        {{ key.charAt(0).toUpperCase() + key.slice(1) }}
                                        <span v-if="sortColumn === key" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(row, index) in filteredGridData" :key="index" class="hover:bg-gray-50 transition-colors">
                                <td v-for="(value, key) in row" :key="key" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ value }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <MazBtn @click="removeGridRow(index)" color="danger" size="xs">Supprimer</MazBtn>
                                </td>
                            </tr>
                            <tr v-if="filteredGridData.length === 0">
                                <td colspan="100%" class="px-6 py-4 text-center text-gray-500">Aucune donnée disponible</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-sm text-gray-600">
                    <span>{{ filteredGridData.length }} ligne(s) affichée(s)</span>
                    <div class="flex flex-wrap gap-4">
                        <span>Total dossiers : {{ gridTotals.dossiers }}</span>
                        <span>Extrait de plan : {{ gridTotals.extraits_de_plan }}</span>
                        <span>Délimitation : {{ gridTotals.delimitation }}</span>
                        <span>Rejetés : {{ gridTotals.rejetes }}</span>
                    </div>
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