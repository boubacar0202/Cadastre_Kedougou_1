<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import BaseInput from "@/Components/BaseInput.vue"
import BaseSelect from "@/Components/BaseSelect.vue"
import DeleteModal from "@/Components/DeleteModal.vue"
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
const showDeleteModal = ref(false);
const traitementASupprimer = ref(null);
const loadingDelete = ref(false);
const filtresDetail = [
    { value: 'tous', label: 'Tous' },
    { value: 'enregistrement', label: 'Enregistrements' },
    { value: 'modification', label: 'Modifications' },
    { value: 'suppression', label: 'Suppressions' },
    { value: 'recuperation', label: 'Récupérations' },
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
    { key: 'suppressions', label: 'Suppressions' },
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
        acc.suppressions += row.suppressions || 0;
        acc.recherches += row.recherches || 0;
        acc.score += row.score || 0;
        return acc;
    }, { enregistrements: 0, modifications: 0, suppressions: 0, recherches: 0, score: 0 });
});

const traitementsFiltres = computed(() => {
    const lignes = detail.value?.traitements || [];
    if (filtreDetail.value === 'tous') return lignes;
    if (filtreDetail.value === 'recuperation') {
        return lignes.filter(item => estRecuperation(item));
    }
    if (filtreDetail.value === 'recherche') {
        return lignes.filter(item => item.action === 'recherche' && !estRecuperation(item));
    }
    return lignes.filter(item => item.action === filtreDetail.value);
});

function estRecuperation(item) {
    return String(item?.libelle || '').startsWith('Récupération');
}

function libelleAction(action, libelle = '') {
    if (String(libelle || '').startsWith('Récupération')) return 'Récupération';
    if (action === 'enregistrement') return 'Enregistrement';
    if (action === 'modification') return 'Modification';
    if (action === 'suppression') return 'Suppression';
    if (action === 'recherche') return 'Recherche';
    return action || 'Traitement';
}

function referenceAffichee(reference) {
    if (!reference) return '—';
    const texte = String(reference).replace(/\s*\{[^{}]+\}/g, '').trim();
    return texte || '—';
}

function actionClass(action, libelle = '') {
    if (String(libelle || '').startsWith('Récupération')) return 'bg-violet-100 text-violet-800';
    if (action === 'enregistrement') return 'bg-emerald-100 text-emerald-800';
    if (action === 'modification') return 'bg-sky-100 text-sky-800';
    if (action === 'suppression') return 'bg-rose-100 text-rose-800';
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
            suppressions: row.suppressions || 0,
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
    showDeleteModal.value = false;
    traitementASupprimer.value = null;
}

function appreciationDepuisScore(score) {
    if (score >= 50) return 'Excellente';
    if (score >= 25) return 'Bonne';
    if (score >= 10) return 'Moyenne';
    if (score >= 1) return 'Faible';
    return 'Aucune activité';
}

function supprimerTraitement(item) {
    if (!item?.id || loadingDelete.value) return;
    traitementASupprimer.value = item;
    showDeleteModal.value = true;
}

function retirerTraitementLocal(item) {
    if (!detail.value) return;
    detail.value.traitements = (detail.value.traitements || []).filter((ligne) => ligne.id !== item.id);
    const cle = {
        enregistrement: 'enregistrements',
        modification: 'modifications',
        suppression: 'suppressions',
        recherche: 'recherches',
    }[item.action];
    if (cle && detail.value.totaux) {
        detail.value.totaux[cle] = Math.max(0, (detail.value.totaux[cle] || 0) - 1);
    }
    detail.value.total = Math.max(0, (detail.value.total || 1) - 1);

    const ligne = evaluationLignes.value.find((row) => row.user_id === detail.value.user_id);
    if (!ligne || !cle) return;
    ligne[cle] = Math.max(0, (ligne[cle] || 0) - 1);
    ligne.score = (ligne.enregistrements * 3) + (ligne.modifications * 2) + (ligne.recherches || 0);
    ligne.appreciation = appreciationDepuisScore(ligne.score);
}

async function confirmerSuppressionTraitement({ adminCode } = {}) {
    const item = traitementASupprimer.value;
    if (!item?.id || loadingDelete.value) return;
    if (!adminCode || String(adminCode).trim() === '') {
        toast.error('Veuillez entrer le code administrateur.');
        return;
    }

    loadingDelete.value = true;
    try {
        const { data } = await axios.delete(`/api/evaluation-utilisateur/${item.id}`, {
            data: { code: String(adminCode).trim() },
        });
        if (!data?.success) {
            toast.error(data?.message || 'Impossible de supprimer ce traitement.');
            return;
        }
        retirerTraitementLocal(item);
        showDeleteModal.value = false;
        traitementASupprimer.value = null;
        toast.success(data.message || 'Traitement supprimé.');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Impossible de supprimer ce traitement.');
    } finally {
        loadingDelete.value = false;
    }
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
    <Head title="Évaluation - Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Évaluation Statistique</h2>
        </template>

        <div class="eval-page">
            <header class="eval-header">
                <div>
                    <p class="eval-kicker">Cadastre de Kédougou</p>
                    <h1>Évaluation statistique</h1>
                </div>
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
            </header>

            <section class="stat-grid">
                <article class="stat-card stat-card--orange">
                    <p class="stat-label">Délimitation</p>
                    <p class="stat-value text-orange-600">{{ stats.dossiers_delimitation || 0 }}</p>
                </article>
                <article class="stat-card stat-card--teal">
                    <p class="stat-label">Extrait de plan</p>
                    <p class="stat-value text-teal-600">{{ stats.dossiers_extrait_plan || 0 }}</p>
                </article>
                <article class="stat-card stat-card--brown">
                    <p class="stat-label">Implantation</p>
                    <p class="stat-value text-primary-txt">{{ stats.dossiers_implantation || 0 }}</p>
                </article>
                <article class="stat-card stat-card--rose">
                    <div class="stat-head">
                        <p class="stat-label">Terrains enquêtés</p>
                        <p class="stat-goal">Objectif 1 500</p>
                    </div>
                    <div class="stat-head">
                        <p class="stat-value text-pink-600">{{ stats.dossiers_terrain_enqueter || 0 }}</p>
                        <p class="stat-percent">{{ stats.dossiers_pourcentage_terrain_enqueter || 0 }}%</p>
                    </div>
                    <div class="stat-meter" aria-hidden="true">
                        <span :style="{ width: Math.min(Number(stats.dossiers_pourcentage_terrain_enqueter) || 0, 100) + '%' }"></span>
                    </div>
                </article>
            </section>

            <section class="chart-grid">
                <article class="panel">
                    <div class="panel-head">
                        <h2>Répartition des dossiers</h2>
                        <span class="panel-kicker">Répartition</span>
                    </div>
                    <div class="chart-frame"><canvas ref="pieChartRef"></canvas></div>
                </article>
                <article class="panel">
                    <div class="panel-head">
                        <h2>Évolution mensuelle</h2>
                        <span class="panel-kicker">Comparaison</span>
                    </div>
                    <div class="chart-frame"><canvas ref="barChartRef"></canvas></div>
                </article>
            </section>

            <h2 class="section-title">États de cession</h2>

            <section class="cession-grid">
                <article class="panel">
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
                    <div class="table-scroll">
                        <table class="data-table">
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
                </article>

                <article class="panel">
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
                    <div class="table-scroll">
                        <table class="data-table">
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
                </article>
            </section>

            <section class="panel eval-card">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-txt">Évaluation des utilisateurs</h2>
                        <p class="text-sm text-primary-txt/70 mt-1">
                            Selon les traitements du trimestre : enregistrements, modifications, suppressions, récupérations et recherches.  
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <BaseSelect v-model.number="annee" :options="annees" wrapper-class="w-28" />
                        <BaseSelect v-model.number="trimestre" :options="trimestresOptions" wrapper-class="w-44" />
                        <BaseInput v-model="searchQuery" placeholder="Rechercher un utilisateur..." wrapper-class="w-56" />
                        <MazBtn @click="exportEvaluation" color="success" size="sm">Exporter CSV</MazBtn>
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="data-table eval-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="col in colonnesEvaluation" :key="col.key"
                                    @click="sortBy(col.key)"
                                    class="px-2 py-2 text-left text-xs font-medium text-primary-txt uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center gap-1">
                                        {{ col.label }}
                                        <span v-if="sortColumn === col.key" class="text-xs">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </th>
                                <th class="px-2 py-2 text-center text-xs font-medium text-primary-txt uppercase tracking-wider">Détail</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="isLoadingEvaluation">
                                <td :colspan="colonnesEvaluation.length + 1" class="px-2 py-2 text-center text-primary-txt">Chargement de l'évaluation...</td>
                            </tr>
                            <template v-else>
                                <tr v-for="row in filteredEvaluation" :key="row.user_id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-2 py-2 whitespace-nowrap text-sm font-medium text-primary-txt">{{ row.utilisateur }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-primary-txt">{{ row.role }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-primary-txt">{{ row.trimestre }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.enregistrements }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.modifications }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.suppressions || 0 }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center text-primary-txt">{{ row.recherches }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center font-semibold text-primary-txt">{{ row.score }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="appreciationClass(row.appreciation)">
                                            {{ row.appreciation }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-center text-primary-txt">
                                        <MazBtn @click="afficherInformations(row)" color="primary" size="sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        </MazBtn>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="!isLoadingEvaluation && filteredEvaluation.length === 0">
                                <td :colspan="colonnesEvaluation.length + 1" class="px-6 py-4 text-center text-primary-txt">Aucun utilisateur pour ce trimestre</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="eval-foot">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                        <span>{{ filteredEvaluation.length }} utilisateur(s)</span>
                        <div class="flex flex-wrap gap-4">
                            <span>Enregistrements : {{ evaluationTotals.enregistrements }}</span>
                            <span>Modifications : {{ evaluationTotals.modifications }}</span>
                            <span>Suppressions : {{ evaluationTotals.suppressions }}</span>
                            <span>Recherches : {{ evaluationTotals.recherches }}</span>
                            <span>Score cumulé : {{ evaluationTotals.score }}</span>
                        </div>
                    </div>
                    <p class="eval-scale">
                        Score = enregistrements × 3 + modifications × 2 + recherches × 1. Les suppressions sont comptées à part.
                        Appréciation : aucune activité (0), faible (1 à 9), moyenne (10 à 24), bonne (25 à 49), excellente (50 et plus).
                    </p>
                </div>
            </section>
        </div>

        <div v-if="detailOuvert" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="fermerDetail"></div>
            <div class="relative flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
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
                    <span>Suppressions : {{ detail?.totaux?.suppressions || 0 }}</span>
                    <span>Recherches : {{ detail?.totaux?.recherches || 0 }}</span>
                </div>

                <div class="flex flex-wrap gap-2 px-6 py-3">
                    <button
                        v-for="filtre in filtresDetail"
                        :key="filtre.value"
                        type="button"
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors"
                        :class="filtreDetail === filtre.value ? 'bg-primary text-white' : 'bg-gray-100 text-primary-txt'"
                        @click="filtreDetail = filtre.value"
                    >
                        {{ filtre.label }}
                    </button>
                </div>

                <div class="journal-panel">
                    <p v-if="detailChargement" class="journal-state">Chargement des traitements...</p>
                    <p v-else-if="traitementsFiltres.length === 0" class="journal-state">
                        Aucun traitement pour ce filtre sur le trimestre.
                    </p>
                    <div v-else class="journal-scroll">
                        <table class="journal-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Référence</th>
                                    <th>Avis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in traitementsFiltres" :key="item.id">
                                    <td class="journal-date">{{ item.date }}</td>
                                    <td>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="actionClass(item.action, item.libelle)">
                                            {{ libelleAction(item.action, item.libelle) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="journal-module">{{ item.module || '—' }}</div>
                                        <div class="journal-libelle">{{ item.libelle }}</div>
                                    </td>
                                    <td>
                                        <div class="journal-ref">{{ referenceAffichee(item.reference) }}</div>
                                    </td>
                                    <td>
                                        <MazBtn
                                            size="sm"
                                            color="danger"
                                            @click="supprimerTraitement(item)"
                                            :disabled="loadingDelete && traitementASupprimer?.id === item.id"
                                            class="h-6 w-6 text-white bg-gradient-to-r from-danger-500 via-danger-600 to-danger-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-danger-300 font-medium rounded-lg text-sm px-5 py-2.5"
                                            >
                                            🗑
                                        </MazBtn>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="detail?.tronque" class="journal-note">
                        Affichage des 300 traitements les plus récents sur {{ detail.total }}.
                    </p>
                </div>
            </div>
        </div>

        <DeleteModal
            :show="showDeleteModal"
            :itemName="traitementASupprimer?.libelle || 'ce traitement'"
            :loading="loadingDelete"
            @update:show="showDeleteModal = $event"
            @confirm="confirmerSuppressionTraitement"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .animate-spin { animation: spin 1s linear infinite; }
    canvas { max-height: 100%; max-width: 100%; }

    .eval-page {
        display: flex;
        flex-direction: column;
        gap: 1.35rem;
        padding: 1.35rem 1.5rem 2.25rem;
    }
    .eval-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
    }
    .eval-kicker {
        margin: 0 0 0.2rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(100, 52, 7, 0.55);
    }
    .eval-header h1 {
        margin: 0;
        font-size: 1.7rem;
        line-height: 1.15;
        letter-spacing: -0.03em;
        color: #613104;
    }
    .stat-grid,
    .chart-grid,
    .cession-grid {
        display: grid;
        gap: 1rem;
    }
    .stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .chart-grid,
    .cession-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .stat-card,
    .panel {
        background: #fff;
        border: 1px solid #f0e4d4;
        border-radius: 1.05rem;
        box-shadow: 0 10px 28px rgba(97, 49, 4, 0.05);
    }
    .stat-card {
        position: relative;
        overflow: hidden;
        padding: 1rem 1.05rem 0.95rem 1.15rem;
    }
    .stat-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.85rem;
        bottom: 0.85rem;
        width: 3px;
        border-radius: 999px;
    }
    .stat-card--orange::before { background: #e07a2f; }
    .stat-card--teal::before { background: #0f8f86; }
    .stat-card--brown::before { background: #613104; }
    .stat-card--rose::before { background: #c45c78; }
    .stat-label {
        margin: 0;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(100, 52, 7, 0.58);
    }
    .stat-value {
        margin: 0.35rem 0 0;
        font-size: 1.85rem;
        font-weight: 700;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .stat-card--orange .stat-value { color: #ea580c; }
    .stat-card--teal .stat-value { color: #0d9488; }
    .stat-card--brown .stat-value { color: #643407; }
    .stat-card--rose .stat-value { color: #db2777; }
    .stat-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .stat-goal,
    .stat-percent {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .stat-goal { color: #ef4444; }
    .stat-percent {
        font-size: 1.05rem;
        font-variant-numeric: tabular-nums;
        color: #db2777;
    }
    .stat-meter {
        margin-top: 0.75rem;
        height: 0.38rem;
        overflow: hidden;
        border-radius: 999px;
        background: #f6efe7;
    }
    .stat-meter span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #c45c78, #e7a0b2);
    }
    .panel { padding: 1.15rem 1.2rem 1.2rem; }
    .panel-head,
    .section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .panel-head h2,
    .section-title,
    .eval-card h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #613104;
    }
    .section-title { margin: 0.35rem 0 0; }
    .panel-kicker {
        padding: 0.2rem 0.55rem;
        border: 1px solid #f0e4d4;
        border-radius: 999px;
        background: #faf6f1;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgba(100, 52, 7, 0.55);
    }
    .chart-frame {
        height: 20rem;
        margin-top: 0.9rem;
        padding: 0.65rem;
        border: 1px solid #f6efe7;
        border-radius: 0.85rem;
        background: linear-gradient(180deg, #fffdfb 0%, #fbf7f2 100%);
    }
    .table-scroll {
        margin-top: 0.85rem;
        overflow: auto;
        border: 1px solid #f0e4d4;
        border-radius: 0.85rem;
    }
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 520px;
    }
    .data-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        padding: 0.72rem 0.9rem;
        background: #faf6f1;
        color: #613104;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid #eadccb;
        user-select: none;
        cursor: default;
    }
    .data-table th.text-center,
    .data-table td.text-center { text-align: center; }
    .data-table th.text-right,
    .data-table td.text-right { text-align: right; }
    .data-table td {
        padding: 0.7rem 0.9rem;
        color: #643407;
        font-size: 0.84rem;
        border-bottom: 1px solid #f6efe7;
        vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: 0; }
    .data-table tbody tr { transition: background-color 0.18s ease; }
    .eval-table { min-width: 860px; }
    .eval-table th { cursor: pointer; }
    .eval-table th:hover { background: #f3ece4; }
    .eval-table tbody tr:hover td { background: #fffaf6; }
    .eval-card h2 + p {
        margin: 0.25rem 0 0;
        font-size: 0.82rem;
        color: rgba(100, 52, 7, 0.62);
    }
    .eval-foot {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        margin-top: 0.9rem;
        padding-top: 0.85rem;
        border-top: 1px solid #f0e4d4;
        color: #643407;
        font-size: 0.84rem;
    }
    .eval-scale {
        margin: 0;
        font-size: 0.72rem;
        color: rgba(100, 52, 7, 0.6);
    }
    @media (max-width: 1100px) {
        .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 860px) {
        .chart-grid,
        .cession-grid,
        .stat-grid { grid-template-columns: 1fr; }
        .chart-frame { height: 16rem; }
        .eval-page { padding: 1rem; }
    }

    .journal-panel {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 0;
        flex: 1;
        padding: 0 1.5rem 1.25rem;
    }
    .journal-scroll {
        min-height: 0;
        flex: 1;
        overflow: auto;
        border: 1px solid #f0e4d4;
        border-radius: 0.9rem;
        background: #fff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }
    .journal-table {
        width: 100%;
        min-width: 760px;
        border-collapse: separate;
        border-spacing: 0;
    }
    .journal-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        padding: 0.8rem 0.95rem;
        background: #faf6f1;
        color: #613104;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid #eadccb;
        box-shadow: 0 1px 0 #eadccb;
        cursor: default;
    }
    .journal-table thead th:hover {
        background: #faf6f1;
    }
    .journal-table tbody td {
        padding: 0.85rem 0.95rem;
        vertical-align: top;
        border-bottom: 1px solid #f6efe7;
        color: #643407;
    }
    .journal-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .journal-table tbody tr:hover td {
        background: #fffaf6;
    }
    .journal-date {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        font-size: 0.8rem;
        color: #8a5a2b;
        width: 9.5rem;
    }
    .journal-module {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.3;
    }
    .journal-libelle {
        margin-top: 0.15rem;
        font-size: 0.72rem;
        line-height: 1.35;
        color: rgba(100, 52, 7, 0.55);
    }
    .journal-ref {
        max-width: 34rem;
        max-height: 8.5rem;
        overflow: auto;
        padding: 0.55rem 0.7rem;
        border: 1px solid #f0e4d4;
        border-radius: 0.7rem;
        background: #fbf8f4;
        color: #4a2c0a;
        font-size: 0.78rem;
        line-height: 1.45;
        white-space: pre-line;
        word-break: break-word;
    }
    .journal-state {
        margin: 0.25rem 0 0.5rem;
        padding: 2.25rem 1rem;
        text-align: center;
        font-size: 0.875rem;
        color: #643407;
        background: #fbf8f4;
        border: 1px dashed #e6c5a2;
        border-radius: 0.9rem;
    }
    .journal-note {
        margin: 0;
        font-size: 0.75rem;
        color: rgba(100, 52, 7, 0.7);
    }
</style>