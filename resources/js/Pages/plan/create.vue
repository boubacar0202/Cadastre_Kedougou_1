<script setup>
// ============================================================
// 1. IMPORTS
// ============================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import proj4 from 'proj4';
import { toast } from "@/Components/AppToast";
import * as turf from '@turf/turf';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
axios.defaults.headers.common['X-CSRF-TOKEN'] = page.props.csrf_token;

// ============================================================
// 2. PROPS
// ============================================================
const props = defineProps({
    point_densifiers: { type: Array, default: () => [] },
    shapefiles: { type: Array, default: () => [] },
});

// ============================================================
// 3. CONSTANTES
// ============================================================
const ZOOM_THRESHOLD_POINTS = 16;
const ZOOM_THRESHOLD_HISTORIC = 11;
const LARGE_SHAPEFILE_THRESHOLD = 5000;
const SHAPEFILE_RELOAD_DELAY = 500;

const DEFAULT_PARCEL_STYLE = {
    color: '#3388ff',
    weight: 2,
    fillColor: '#3388ff',
    fillOpacity: 0.25
};
const HOVER_PARCEL_STYLE = {
    color: '#2563eb',
    weight: 3,
    fillColor: '#3b82f6',
    fillOpacity: 0.45
};
const SELECTED_PARCEL_STYLE = {
    color: '#dc2626',
    weight: 5,
    fillColor: '#ef4444',
    fillOpacity: 0.6,
    opacity: 1,
    lineJoin: 'round'
};

// ============================================================
// 4. RÉFÉRENCES
// ============================================================
const map = ref(null);
const parcelsLayer = ref(null);
const selectionHighlightLayer = ref(null);
const selectedLayer = ref(null);
const selectedParcelleFeature = ref(null);

const convertToUTM = ref(false);
const geojsonData = ref(null);
const sections = ref([]);
const sectionPoints = ref([]);
const parcellePoints = ref([]);
const selectedInfo = ref(null);
const showInfo = ref(false);

const pointsDensifies = ref([]);
const markersLayer = ref(null);
const selectedPointId = ref(null);
const showList = ref(true);
const searchQueryList = ref('');
const searchQuery = ref('');
const searchResults = ref([]);
const userMarker = ref(null);
const locationLoading = ref(false);
const scaleDenominator = ref(1);
const currentZoom = ref(12);

// Shapefiles
const shapefiles = ref([]);
const shapefilesLayerGroup = ref(null);
const showShapefileModal = ref(false);
const activeTab = ref('list');
const uploading = ref(false);
const form = ref({ nom: '', shp: null, shx: null, dbf: null, prj: null, srid: '4326' });
const shapefileCache = new Map();
let shapefileReloadTimer = null;
let currentZoomAnimation = null;

// Sites historiques
const historicalSites = ref([
    { name: "Village de Dinguessou", lat: 12.5599, lng: -12.1742, description: "Ancien village historique de la région.", type: "historique" },
    { name: "Palais de Kédougou", lat: 12.5612, lng: -12.1710, description: "Ancienne résidence coloniale.", type: "historique" },
]);
const historicLayerGroup = ref(null);

// ============================================================
// 5. UTILITAIRES
// ============================================================
const isDev = import.meta.env.DEV;
const log = (...args) => isDev && console.log(...args);

function safeFlyTo(lat, lng, zoom = 20, options = {}) {
    if (!map.value) return;
    try {
        map.value.flyTo([lat, lng], zoom, options);
    } catch (e) {
        console.warn('flyTo error:', e.message);
    }
}

function safeFitBounds(bounds, options = {}) {
    if (!map.value || !bounds) return;
    try {
        if (!bounds.isValid || !bounds.isValid()) return;
        map.value.fitBounds(bounds, options);
    } catch (e) {
        console.warn('fitBounds error:', e.message);
    }
}

// 🎯 Zoom intelligent avec anti-rebond
function zoomToParcelle(layer) {
    if (!map.value || !layer) return;

    if (currentZoomAnimation) {
        clearTimeout(currentZoomAnimation);
        currentZoomAnimation = null;
    }

    try {
        const bounds = layer.getBounds();
        if (!bounds.isValid()) return;

        currentZoomAnimation = setTimeout(() => {
            try {
                const size = bounds.getNorthEast().distanceTo(bounds.getSouthWest());
                let maxZoom = 19;
                if (size < 20) maxZoom = 21;
                else if (size < 50) maxZoom = 20;
                else if (size < 200) maxZoom = 19;
                else if (size < 1000) maxZoom = 18;
                else maxZoom = 17;

                map.value.fitBounds(bounds, {
                    padding: [80, 80],
                    maxZoom,
                    animate: true,
                    duration: 0.5
                });
            } catch (e) {
                console.warn('⚠️ Zoom error:', e.message);
            }
            currentZoomAnimation = null;
        }, 50);
    } catch (e) {
        console.warn('⚠️ Erreur zoomToParcelle:', e);
    }
}

const UTM28N = '+proj=utm +zone=28 +datum=WGS84 +units=m +no_defs';
const WGS84 = '+proj=longlat +datum=WGS84 +no_defs';

function gpsToUtm(latitude, longitude) {
    const [x, y] = proj4(WGS84, UTM28N, [longitude, latitude]);
    return { x, y };
}

function utmToGps(est, nord) {
    const e = parseFloat(est);
    const n = parseFloat(nord);
    if (isNaN(e) || isNaN(n)) return null;
    try {
        const [longitude, latitude] = proj4(UTM28N, WGS84, [e, n]);
        return { latitude, longitude };
    } catch (error) {
        console.error('❌ Erreur proj4 :', error);
        return null;
    }
}

function isHeavyShapefile(shp) {
    const nb = shp.nb_entites || 0;
    if (nb > LARGE_SHAPEFILE_THRESHOLD) return true;
    const name = (shp.nom || '').toLowerCase();
    const table = (shp.nom_table || '').toLowerCase();
    return name.includes('numero') || name.includes('parcelle') || table.includes('parcelle');
}

// ============================================================
// 6. RECHERCHE LOCALITÉ
// ============================================================
let searchTimeout = null;
function searchLocation() {
    clearTimeout(searchTimeout);
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get('https://nominatim.openstreetmap.org/search', {
                params: { q: searchQuery.value, format: 'json', countrycodes: 'sn', limit: 6, addressdetails: 1 },
                headers: { 'User-Agent': 'Cadastre_Kedougou/1.0' }
            });
            searchResults.value = response.data;
        } catch (error) {
            console.error('Erreur de recherche:', error);
        }
    }, 500);
}

function selectLocation(result) {
    const lat = parseFloat(result.lat);
    const lon = parseFloat(result.lon);
    safeFlyTo(lat, lon, 14);
    searchResults.value = [];
    searchQuery.value = result.display_name.split(',').slice(0, 3).join(',');
    const { x, y } = gpsToUtm(lat, lon);
    const infoHtml = `
        <div style="min-width: 200px; padding: 5px;">
            <h4 style="margin: 0 0 8px 0; color: #613104;">📍 Localisation</h4>
            <hr>
            <p><b>Latitude :</b> ${lat.toFixed(6)}</p>
            <p><b>Longitude :</b> ${lon.toFixed(6)}</p>
            <p><b>UTM X (28N) :</b> ${Math.round(x)}</p>
            <p><b>UTM Y (28N) :</b> ${Math.round(y)}</p>
        </div>
    `;
    if (map.value) {
        L.popup().setLatLng([lat, lon]).setContent(infoHtml).openOn(map.value);
    }
    toast.info('📍 Localisation trouvée');
}

// ============================================================
// 7. POINTS DENSIFIÉS
// ============================================================
function addPointsToMap() {
    if (!map.value) return;

    if (markersLayer.value) {
        try { map.value.removeLayer(markersLayer.value); } catch (e) {}
        markersLayer.value = null;
    }
    markersLayer.value = L.layerGroup().addTo(map.value);

    const latLngs = [];
    let pointsAdded = 0;

    pointsDensifies.value.forEach((point) => {
        const coords = utmToGps(point.txt_estPoint, point.txt_nordPoint);
        if (!coords) return;
        const { latitude, longitude } = coords;
        latLngs.push([latitude, longitude]);
        pointsAdded++;

        const marker = L.circleMarker([latitude, longitude], {
            radius: 6,
            fillColor: '#2563eb',
            color: '#ffffff',
            weight: 2,
            fillOpacity: 1
        }).addTo(markersLayer.value);

        const popupContent = `
            <div style="min-width: 220px; padding: 5px;">
                <h3 style="margin: 0 0 8px 0; color: #613104; font-weight: bold;">📍 ${point.txt_nomPoint || 'Point'}</h3>
                <hr>
                <p><b>Est (X) :</b> ${point.txt_estPoint || '-'}</p>
                <p><b>Nord (Y) :</b> ${point.txt_nordPoint || '-'}</p>
                <p><b>Hauteur (Z) :</b> ${point.txt_hauteurPoint || '-'}</p>
                ${point.txt_documentsAfferants ? `<p><a href="/storage/${point.txt_documentsAfferants}" target="_blank" style="color: #613104;">📄 Voir PDF</a></p>` : ''}
            </div>
        `;
        marker.bindPopup(popupContent);

        marker.on('click', function () {
            resetParcelleSelection();
            showInfo.value = true;
            selectedInfo.value = { type: 'point', point: point, loading: false, data: point };
            selectedPointId.value = point.id;
            document.querySelectorAll('.point-list-item').forEach(el => el.classList.remove('active'));
            const listItem = document.querySelector(`[data-point-id="${point.id}"]`);
            if (listItem) listItem.classList.add('active');
        });
    });

    if (latLngs.length > 0) {
        const bounds = L.latLngBounds(latLngs);
        safeFitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        toast.success(`✅ ${pointsAdded} points densifiés chargés`);
    }
}

// ============================================================
// 8. LOCALISER UN POINT
// ============================================================
function localiserPoint(point) {
    if (!point) return;
    const coords = utmToGps(point.txt_estPoint, point.txt_nordPoint);
    if (!coords) {
        toast.error('Coordonnées invalides');
        return;
    }
    const { latitude, longitude } = coords;
    safeFlyTo(latitude, longitude, 20, { duration: 1.2 });

    const popupContent = `
        <div style="min-width: 200px; padding: 5px;">
            <h4 style="margin: 0 0 8px 0; color: #613104;">📍 ${point.txt_nomPoint || 'Point'}</h4>
            <hr>
            <p><b>Est (X) :</b> ${point.txt_estPoint || '-'}</p>
            <p><b>Nord (Y) :</b> ${point.txt_nordPoint || '-'}</p>
            <p><b>Hauteur (Z) :</b> ${point.txt_hauteurPoint || '-'}</p>
            ${point.txt_documentsAfferants ? `<p><a href="/storage/${point.txt_documentsAfferants}" target="_blank" style="color: #613104;">📄 Voir PDF</a></p>` : ''}
        </div>
    `;
    if (map.value) {
        try {
            L.popup().setLatLng([latitude, longitude]).setContent(popupContent).openOn(map.value);
        } catch (e) {}
    }

    resetParcelleSelection();
    showInfo.value = true;
    selectedInfo.value = { type: 'point', point: point, loading: false, data: point };
    selectedPointId.value = point.id;
    document.querySelectorAll('.point-list-item').forEach(el => el.classList.remove('active'));
    const listItem = document.querySelector(`[data-point-id="${point.id}"]`);
    if (listItem) listItem.classList.add('active');
}

// ============================================================
// 9. GÉOLOCALISATION
// ============================================================
function getUserLocation() {
    if (!navigator.geolocation) {
        toast.error('La géolocalisation n\'est pas supportée');
        return;
    }
    locationLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;
            if (userMarker.value && map.value) {
                try { map.value.removeLayer(userMarker.value); } catch (e) {}
            }
            const userIcon = L.divIcon({
                className: 'user-marker',
                html: `<div style="background:#2563eb; width:20px; height:20px; border-radius:50%; border:3px solid white; box-shadow:0 0 20px rgba(37,99,235,0.6);"><div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:8px; height:8px; background:white; border-radius:50%;"></div></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
            userMarker.value = L.marker([latitude, longitude], { icon: userIcon })
                .addTo(map.value)
                .bindPopup('📍 Vous êtes ici');
            safeFlyTo(latitude, longitude, 15);
            locationLoading.value = false;
            toast.success('📍 Votre position a été localisée');
        },
        () => {
            locationLoading.value = false;
            toast.error('Impossible d\'obtenir votre position');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// ============================================================
// 10. ÉCHELLE
// ============================================================
function updateScale() {
    if (!map.value) return;
    try {
        const center = map.value.getCenter();
        const zoom = map.value.getZoom();
        currentZoom.value = zoom;
        const latRad = center.lat * Math.PI / 180;
        let denominator = 591657550.5 * Math.cos(latRad) / Math.pow(2, zoom);
        let rounded = Math.round(denominator);
        if (rounded > 1000) {
            const magnitude = Math.pow(10, Math.floor(Math.log10(rounded)));
            rounded = Math.round(rounded / magnitude) * magnitude;
        } else if (rounded > 100) {
            rounded = Math.round(rounded / 10) * 10;
        }
        scaleDenominator.value = rounded;
    } catch (e) {}
}

// ============================================================
// 11. FIND SECTION / PARCELLE
// ============================================================
function findSection(feature) {
    try {
        const pt = turf.center(feature);

        for (const section of sections.value) {
            if (!section?.geometry?.coordinates) continue;

            let polygon;
            try {
                if (section.geometry.type === 'MultiPolygon') {
                    polygon = turf.multiPolygon(section.geometry.coordinates);
                } else if (section.geometry.type === 'Polygon') {
                    polygon = turf.polygon(section.geometry.coordinates);
                } else {
                    continue;
                }
            } catch (e) { continue; }

            if (turf.booleanPointInPolygon(pt, polygon)) {
                for (const secPoint of sectionPoints.value) {
                    if (!secPoint?.geometry?.coordinates) continue;
                    let coords;
                    if (secPoint.geometry.type === 'MultiPoint') coords = secPoint.geometry.coordinates[0];
                    else if (secPoint.geometry.type === 'Point') coords = secPoint.geometry.coordinates;
                    else continue;

                    const point = turf.point(coords);
                    if (turf.booleanPointInPolygon(point, polygon)) {
                        return secPoint.properties?.RefName
                            || secPoint.properties?.refname
                            || secPoint.properties?.REFNAME
                            || secPoint.properties?.nom
                            || secPoint.properties?.NOM
                            || 'N/A';
                    }
                }
                return section.properties?.RefName
                    || section.properties?.refname
                    || section.properties?.nom
                    || 'N/A';
            }
        }
    } catch (e) {
        console.warn('⚠️ findSection:', e);
    }
    return 'N/A';
}

function findParcelle(feature) {
    try {
        const center = turf.center(feature);
        let nearest = null;
        let minDistance = Infinity;

        parcellePoints.value.forEach(p => {
            if (!p?.geometry?.coordinates) return;
            const coords = p.geometry.type === 'MultiPoint' ? p.geometry.coordinates[0] : p.geometry.coordinates;
            const pt = turf.point(coords);
            const distance = turf.distance(center, pt);
            if (distance < minDistance) {
                minDistance = distance;
                nearest = p;
            }
        });

        return nearest?.properties?.RefName
            || nearest?.properties?.refname
            || nearest?.properties?.NUMERO
            || nearest?.properties?.numero
            || nearest?.properties?.NOM
            || 'N/A';
    } catch (e) {
        console.warn('⚠️ findParcelle:', e);
        return 'N/A';
    }
}

// ============================================================
// 12. RESET PARCELLE (sécurisé)
// ============================================================
function resetParcelleSelection() {
    try {
        if (selectionHighlightLayer.value && map.value) {
            if (map.value.hasLayer(selectionHighlightLayer.value)) {
                map.value.removeLayer(selectionHighlightLayer.value);
            }
            selectionHighlightLayer.value = null;
        }
    } catch (e) {
        console.warn('⚠️ Reset selection:', e.message);
        selectionHighlightLayer.value = null;
    }
    selectedLayer.value = null;
    selectedParcelleFeature.value = null;
}

// ============================================================
// 13. SÉLECTION PARCELLE (calque SVG dédié)
// ============================================================
function selectParcelle(feature, layer) {
    resetParcelleSelection();

    try {
        // Calque rouge indépendant — TOUJOURS visible
        selectionHighlightLayer.value = L.geoJSON(feature, {
            style: SELECTED_PARCEL_STYLE,
            interactive: false
        }).addTo(map.value);

        selectedLayer.value = layer;
        selectedParcelleFeature.value = feature;

        zoomToParcelle(layer);
    } catch (e) {
        console.warn('⚠️ selectParcelle:', e);
    }
}

// ============================================================
// 14. CHARGEMENT GEOJSON PRINCIPAL
// ============================================================
function loadGeoJSON() {
    if (!geojsonData.value || !map.value) return;

    if (parcelsLayer.value) {
        try { map.value.removeLayer(parcelsLayer.value); } catch (e) {}
        parcelsLayer.value = null;
    }

    parcelsLayer.value = L.geoJSON(geojsonData.value, {
        filter: (feature) =>
            feature.geometry.type === 'MultiPolygon' ||
            feature.geometry.type === 'Polygon',
        style: DEFAULT_PARCEL_STYLE,
        onEachFeature: (feature, layer) => {

            layer.on('mouseover', () => {
                if (layer !== selectedLayer.value) {
                    layer.setStyle(HOVER_PARCEL_STYLE);
                }
            });

            layer.on('mouseout', () => {
                if (layer !== selectedLayer.value) {
                    layer.setStyle(DEFAULT_PARCEL_STYLE);
                }
            });

            layer.on('click', (e) => {
                if (e.originalEvent) L.DomEvent.stopPropagation(e);

                selectedPointId.value = null;
                document.querySelectorAll('.point-list-item').forEach(el => el.classList.remove('active'));

                selectParcelle(feature, layer);

                const parcelleNum = findParcelle(feature);
                const section = findSection(feature);

                showInfo.value = false;
                selectedInfo.value = null;

                nextTick(() => {
                    selectedInfo.value = {
                        type: 'parcelle',
                        parcelle: parcelleNum || '—',
                        section: section || 'Non définie',
                        loading: true,
                        data: null
                    };
                    showInfo.value = true;
                });

                axios.post('/terrain/search', {
                    parcelle: parcelleNum,
                    section: section
                })
                .then(res => {
                    if (selectedInfo.value?.parcelle === parcelleNum) {
                        selectedInfo.value.data = res.data;
                        selectedInfo.value.loading = false;
                    }
                })
                .catch(() => {
                    if (selectedInfo.value) selectedInfo.value.loading = false;
                });
            });
        }
    }).addTo(map.value);
}

// ============================================================
// 15. SITES HISTORIQUES
// ============================================================
function addHistoricalSites() {
    const group = L.layerGroup();
    historicalSites.value.forEach(site => {
        const icon = L.divIcon({
            className: 'historic-marker',
            html: `<div style="background: #f59e0b; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">🏛️</div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const marker = L.marker([site.lat, site.lng], { icon })
            .bindPopup(`
                <div style="min-width: 200px;">
                    <h4 style="margin: 0 0 5px 0; color: #b45309;">🏛️ ${site.name}</h4>
                    <p style="margin: 0; font-size: 13px;">${site.description || 'Site historique'}</p>
                </div>
            `);
        group.addLayer(marker);
    });
    historicLayerGroup.value = group;
    group.addTo(map.value);
    updateHistoricVisibility();
}

function updateHistoricVisibility() {
    if (!map.value || !historicLayerGroup.value) return;
    const zoom = map.value.getZoom();
    if (zoom >= ZOOM_THRESHOLD_HISTORIC) {
        if (!map.value.hasLayer(historicLayerGroup.value)) map.value.addLayer(historicLayerGroup.value);
    } else {
        if (map.value.hasLayer(historicLayerGroup.value)) map.value.removeLayer(historicLayerGroup.value);
    }
}

// ============================================================
// 16. SWITCH UTM/GPS
// ============================================================
const convert = () => {
    convertToUTM.value = !convertToUTM.value;
    toast[convertToUTM.value ? 'success' : 'info'](
        convertToUTM.value ? 'Coordonnées UTM activées' : 'Coordonnées GPS activées'
    );
};

// ============================================================
// 17. LISTE / STATS
// ============================================================
function toggleList() {
    showList.value = !showList.value;
}

const stats = computed(() => ({
    total: pointsDensifies.value.length,
    withDescription: pointsDensifies.value.filter(p => p.txt_descriptionPoint).length,
    withPDF: pointsDensifies.value.filter(p => p.txt_documentsAfferants).length
}));

const filteredPoints = computed(() => {
    if (!searchQueryList.value.trim()) return pointsDensifies.value;
    const q = searchQueryList.value.toLowerCase();
    return pointsDensifies.value.filter(p =>
        (p.txt_nomPoint || '').toLowerCase().includes(q) ||
        (p.txt_descriptionPoint || '').toLowerCase().includes(q)
    );
});

// ============================================================
// 18. MODALE SHAPEFILES
// ============================================================
function openShapefileModal(tab = 'list') {
    activeTab.value = tab;
    showShapefileModal.value = true;
}

function closeShapefileModal() {
    showShapefileModal.value = false;
    form.value = { nom: '', shp: null, shx: null, dbf: null, prj: null, srid: '4326' };
    document.querySelectorAll('input[type="file"]').forEach(i => i.value = '');
}

// ============================================================
// 19. CHARGEMENT SHAPEFILES
// ============================================================
async function chargerShapefiles() {
    if (!map.value) return;

    if (shapefilesLayerGroup.value) {
        try { map.value.removeLayer(shapefilesLayerGroup.value); } catch (e) {}
    }
    shapefilesLayerGroup.value = L.layerGroup().addTo(map.value);

    if (!shapefiles.value.length) return;

    const zoom = map.value.getZoom();

    for (const shp of shapefiles.value) {
        if (!shp.visible) continue;

        if (isHeavyShapefile(shp) && zoom < ZOOM_THRESHOLD_POINTS) {
            log(`⏭️ ${shp.nom} ignoré (zoom ${zoom} < ${ZOOM_THRESHOLD_POINTS})`);
            continue;
        }

        let data = shapefileCache.get(shp.id);

        if (!data) {
            try {
                const response = await axios.get(`/api/shapefiles/${shp.id}/geojson`);
                data = response.data;
                if (!data || !data.features) continue;
                shapefileCache.set(shp.id, data);
            } catch (e) {
                console.error(`❌ #${shp.id}:`, e.message);
                toast.error(`Impossible de charger "${shp.nom}"`);
                continue;
            }
        }

        const isPointLayer = data.features[0]?.geometry?.type === 'Point';

        const layer = L.geoJSON(data, {
            pointToLayer: isPointLayer
                ? (feature, latlng) => L.circleMarker(latlng, {
                    radius: 4,
                    fillColor: shp.couleur || '#3388ff',
                    color: '#ffffff',
                    weight: 1,
                    fillOpacity: 0.9
                })
                : undefined,
            style: {
                color: shp.couleur || '#3388ff',
                weight: 2,
                fillColor: shp.couleur || '#3388ff',
                fillOpacity: 0.25,
            },
            onEachFeature: (feature, lyr) => {
                const props = feature.properties || {};
                const rows = Object.entries(props)
                    .filter(([k]) => !k.startsWith('geom') && k !== 'id')
                    .slice(0, 8)
                    .map(([k, v]) => `<p><b>${k}:</b> ${v ?? '-'}</p>`)
                    .join('');
                lyr.bindPopup(`
                    <div style="min-width:220px">
                        <h4 style="margin:0 0 6px;color:${shp.couleur}">📐 ${shp.nom}</h4>
                        <hr>${rows}
                    </div>
                `);
            },
        }).addTo(shapefilesLayerGroup.value);

        layer._shpId = shp.id;
    }
}

function scheduleShapefileReload() {
    clearTimeout(shapefileReloadTimer);
    shapefileReloadTimer = setTimeout(() => chargerShapefiles(), SHAPEFILE_RELOAD_DELAY);
}

// ============================================================
// 20. UPLOAD
// ============================================================
async function uploadShapefile() {
    uploading.value = true;
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => { if (v) fd.append(k, v); });

    try {
        await axios.post('/shapefiles', fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('✅ Shapefile importé');

        const { data } = await axios.get('/shapefiles');
        shapefiles.value = data;
        shapefileCache.clear();
        await chargerShapefiles();

        activeTab.value = 'list';
        form.value = { nom: '', shp: null, shx: null, dbf: null, prj: null, srid: '4326' };
        document.querySelectorAll('input[type="file"]').forEach(i => i.value = '');
    } catch (e) {
        const msg = e.response?.data?.errors?.shp || e.response?.data?.message || 'Erreur import';
        toast.error(msg);
    } finally {
        uploading.value = false;
    }
}

// ============================================================
// 21. VISIBILITÉ / SUPPRESSION / ZOOM SHAPEFILE
// ============================================================
async function toggleShapefileVisibility(shp) {
    try {
        await axios.patch(`/shapefiles/${shp.id}`, { visible: !shp.visible });
        shp.visible = !shp.visible;
        await chargerShapefiles();
        toast.info(shp.visible ? `"${shp.nom}" affiché` : `"${shp.nom}" masqué`);
    } catch (e) {
        toast.error('Erreur de mise à jour');
    }
}

async function deleteShapefile(shp) {
    if (!confirm(`Supprimer "${shp.nom}" ?`)) return;
    try {
        await axios.delete(`/shapefiles/${shp.id}`);
        shapefiles.value = shapefiles.value.filter(s => s.id !== shp.id);
        shapefileCache.delete(shp.id);
        await chargerShapefiles();
        toast.success('Shapefile supprimé');
    } catch (e) {
        toast.error('Erreur suppression');
    }
}

async function zoomToShapefile(shp) {
    if (!shapefilesLayerGroup.value) return;
    let targetLayer = null;
    shapefilesLayerGroup.value.eachLayer(layer => {
        if (layer._shpId === shp.id) targetLayer = layer;
    });
    if (targetLayer && targetLayer.getBounds().isValid()) {
        safeFitBounds(targetLayer.getBounds(), { padding: [40, 40], maxZoom: 18 });
        closeShapefileModal();
    } else {
        toast.warning('Shapefile masqué ou vide');
    }
}

// ============================================================
// 22. INITIALISATION
// ============================================================
onMounted(() => {
    const kedougou = [12.5599, -12.1742];

    map.value = L.map('map', {
        preferCanvas: false,
        zoomAnimation: false,
        fadeAnimation: false,
        markerZoomAnimation: false,
        updateWhenIdle: false,
        updateWhenZooming: true,
        keepBuffer: 2,
        zoomSnap: 0.5,
        zoomDelta: 0.5,
        wheelPxPerZoomLevel: 100,
        inertia: true,
        easeLinearity: 0.25
    }).setView(kedougou, 12);

    // 🚫 Désactiver le double-clic zoom (source de bugs)
    map.value.doubleClickZoom.disable();

    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { attribution: '&copy; Google', maxZoom: 24 });
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 });
    const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', { attribution: '&copy; Google', maxZoom: 24 });
    const googleStreets = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', { attribution: '&copy; Google', maxZoom: 22 });
    const openTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenTopoMap', maxZoom: 17 });

    const baseMaps = {
        'Hybride (Google)': googleHybrid,
        'Rue (OpenStreetMap)': osm,
        'Satellite (Google)': googleSatellite,
        'Routier (Google)': googleStreets,
        'Topographique (OpenTopoMap)': openTopoMap
    };

    googleHybrid.addTo(map.value);
    L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map.value);

    updateScale();
    map.value.on('zoomend moveend', updateScale);

    map.value.on('zoomend', () => {
        updateHistoricVisibility();
        scheduleShapefileReload();
    });

    map.value.getContainer().style.cursor = 'grab';

    map.value.whenReady(async () => {
        try {
            const response = await fetch('/storage/dinguessou_plateau_complet.geojson');
            geojsonData.value = await response.json();

            sections.value = [];
            parcellePoints.value = [];
            sectionPoints.value = [];

            geojsonData.value.features.forEach(f => {
                if (f.properties?.Level === 6) sections.value.push(f);
                if (f.properties?.Layer === "Numero_Parcelle") parcellePoints.value.push(f);
                if (f.geometry?.type === "MultiPoint" && !f.properties?.Layer && f.properties?.RefName) {
                    sectionPoints.value.push(f);
                }
            });

            log(`📊 Sections: ${sections.value.length}, Parcelles: ${parcellePoints.value.length}, Points: ${sectionPoints.value.length}`);

            loadGeoJSON();

            pointsDensifies.value = props.point_densifiers;
            addPointsToMap();

            addHistoricalSites();

            shapefiles.value = props.shapefiles || [];
            await chargerShapefiles();

        } catch (error) {
            console.error('❌ Erreur chargement :', error);
            toast.error('Erreur chargement des données');
        }
    });
});

// ============================================================
// 23. NETTOYAGE
// ============================================================
onBeforeUnmount(() => {
    clearTimeout(shapefileReloadTimer);
    if (currentZoomAnimation) clearTimeout(currentZoomAnimation);
    if (map.value) {
        try {
            map.value.off();
            map.value.remove();
        } catch (e) {}
        map.value = null;
    }
    shapefileCache.clear();
    parcelsLayer.value = null;
    selectionHighlightLayer.value = null;
});
</script>

<template>
    <Head title="Plan cadastral - Kédougou" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full min-w-0 flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-3 text-2xl font-bold text-primary-txt">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 rounded-xl text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </span>
                    Plan cadastral – Kédougou
                </h2>

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-txt bg-primary-form rounded-full shadow-sm">
                        <span class="flex items-center justify-center w-6 h-6 text-xs text-white bg-primary rounded-full">📌</span>
                        {{ stats.total }} points densifiés
                    </span>

                    <button @click="openShapefileModal('list')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-primary rounded-lg shadow-sm hover:bg-primary-dark">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Gérer les plans
                        <span v-if="shapefiles.length" class="ml-1 px-2 py-0.5 text-xs bg-white/25 rounded-full">
                            {{ shapefiles.length }}
                        </span>
                    </button>

                    <button @click="toggleList"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-primary-txt transition bg-white border border-primary-only rounded-lg shadow-sm hover:bg-primary-form">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ showList ? 'Afficher' : 'Masquer' }} la liste
                    </button>

                    <button @click="getUserLocation" :disabled="locationLoading"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white transition bg-primary rounded-lg shadow-sm hover:bg-primary-dark disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ locationLoading ? 'Chargement…' : 'Ma position' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="p-6">
            <div class="relative overflow-hidden bg-white border border-primary-only rounded-2xl shadow-xl">
                <div id="map" class="w-full h-[700px]"></div>

                <div v-if="currentZoom < ZOOM_THRESHOLD_POINTS"
                    class="absolute top-4 right-4 z-[1000] px-3 py-2 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg shadow">
                    ℹ️ Zoomez à {{ ZOOM_THRESHOLD_POINTS }}+ pour voir les parcelles détaillées
                </div>

                <div class="absolute top-4 left-1/2 z-[1000] w-11/12 max-w-md -translate-x-1/2">
                    <div class="relative">
                        <BaseInput
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher une localité (ex: Kédougou, Saraya…)"
                            wrapper-class="w-full"
                            @input="searchLocation"
                        >
                            <template #append>
                                <svg class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-primary-txt/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </template>
                        </BaseInput>
                        <div v-if="searchResults.length > 0"
                            class="absolute top-full left-0 right-0 mt-2 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl border border-primary-only max-h-60 overflow-y-auto divide-y divide-primary-only">
                            <div v-for="result in searchResults" :key="result.place_id"
                                @click="selectLocation(result)"
                                class="flex items-start gap-3 px-4 py-3 transition cursor-pointer hover:bg-primary-form">
                                <span class="mt-0.5 text-primary">📍</span>
                                <div>
                                    <div class="text-sm font-medium text-primary-txt">{{ result.display_name.split(',').slice(0,3).join(',') }}</div>
                                    <div class="text-xs text-primary-txt capitalize">{{ result.type || 'Localité' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="absolute top-24 left-4 z-[1000]">
                    <div class="flex flex-col items-center gap-1 p-2 bg-white/90 rounded-2xl shadow-lg border border-primary-only backdrop-blur-sm">
                        <span class="text-[10px] font-medium tracking-wider text-primary-txt uppercase">Projection</span>
                        <button @click="convert"
                            class="relative w-14 h-8 rounded-full transition-all duration-300 shadow-inner overflow-hidden"
                            :class="convertToUTM ? 'bg-green-500' : 'bg-primary-only'">
                            <div class="absolute inset-0 bg-white/20 backdrop-blur-sm"></div>
                            <div class="absolute top-1 w-6 h-6 rounded-full bg-white shadow-md transition-all duration-300 flex items-center justify-center text-[10px] font-bold"
                                :class="convertToUTM ? 'left-7' : 'left-1'">
                                {{ convertToUTM ? 'UTM' : 'GPS' }}
                            </div>
                        </button>
                    </div>
                </div>

                <div class="absolute bottom-6 left-1/2 z-[1000] -translate-x-1/2 bg-white/90 backdrop-blur-sm px-5 py-2 rounded-full shadow-xl border border-primary-only text-sm font-medium flex items-center gap-2 select-none">
                    <span class="text-primary-txt">Échelle :</span>
                    <span class="font-mono font-bold text-primary-txt">1 / {{ scaleDenominator.toLocaleString() }}</span>
                </div>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2">
                    <div v-if="selectedInfo?.type === 'parcelle' && selectedParcelleFeature"
                        class="absolute top-20 right-6 z-[1000] flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-full shadow-lg">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                        Parcelle {{ selectedInfo.parcelle }} — Section {{ selectedInfo.section }}
                        <button @click="resetParcelleSelection(); selectedInfo = null; showInfo = false"
                            class="ml-1 hover:bg-red-700 rounded-full p-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 -translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-4">
                    <div v-if="!showList"
                        class="app-sidepanel absolute bottom-6 left-6 z-[1000] w-80 max-h-[60%] backdrop-blur-md overflow-hidden flex flex-col">
                        <div class="flex items-center justify-between p-4 border-b border-primary-only bg-gradient-to-r from-primary to-primary-menu rounded-t-3xl">
                            <h3 class="flex items-center gap-2 font-semibold text-white">
                                <span class="flex items-center justify-center w-6 h-6 text-xs bg-white/25 rounded-full">📍</span>
                                Points densifiés
                            </h3>
                            <span class="px-3 py-1 text-xs font-bold text-primary bg-primary-form rounded-full shadow-sm">
                                {{ filteredPoints.length }}
                            </span>
                        </div>
                        <div class="flex-1 p-3 space-y-1 overflow-y-auto">
                            <BaseInput
                                v-model="searchQueryList"
                                type="text"
                                placeholder="Filtrer les points…"
                                wrapper-class="mb-2 w-full"
                            />
                            <div v-if="filteredPoints.length === 0" class="py-8 text-sm text-center text-primary-txt/60">
                                <span class="block text-3xl mb-2">🔍</span>
                                Aucun point trouvé
                            </div>
                            <div v-for="point in filteredPoints" :key="point.id"
                                :data-point-id="point.id"
                                class="point-list-item flex items-start gap-3 p-3 transition-all border border-transparent rounded-xl cursor-pointer hover:bg-primary-form hover:border-primary-only"
                                :class="{ 'active bg-primary-form border-primary-menu shadow-sm': selectedPointId === point.id }"
                                @click="localiserPoint(point)">
                                <span class="mt-0.5 text-primary">📍</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary-txt truncate">{{ point.txt_nomPoint || 'Point' }}</div>
                                    <div class="text-xs text-primary-txt truncate">{{ point.txt_descriptionPoint || 'Aucune description' }}</div>
                                    <div class="flex items-center gap-1 mt-0.5 text-xs text-primary-txt/60 truncate">
                                        <span>{{ point.departement?.slt_departement || '' }}</span>
                                        <span v-if="point.departement">·</span>
                                        <span>{{ point.arrondissement?.slt_arrondissement || '' }}</span>
                                        <span v-if="point.arrondissement">·</span>
                                        <span>{{ point.commune?.slt_commune || '' }}</span>
                                    </div>
                                </div>
                                <span v-if="point.txt_documentsAfferants" class="text-xs text-green-500" title="PDF disponible">📄</span>
                            </div>
                        </div>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-4">
                    <div v-if="showInfo && selectedInfo"
                        class="plan-info app-sidepanel absolute bottom-6 right-6 z-[1000] w-80 max-h-[70%] overflow-y-auto p-5">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="flex items-center gap-2 text-lg font-bold text-primary">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-white text-sm bg-primary-menu">
                                    {{ selectedInfo.type === 'point' ? '📍' : '🏠' }}
                                </span>
                                <span>{{ selectedInfo.type === 'point' ? 'Point densifié' : 'Parcelle' }}</span>
                            </h3>
                            <button @click="showInfo = false; resetParcelleSelection()"
                                class="p-1.5 text-primary-txt/60 transition rounded-xl hover:text-primary hover:bg-primary-form">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="my-3 border-t border-primary-only"></div>

                        <template v-if="selectedInfo.type === 'point'">
                            <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <span class="font-medium text-primary-txt">Nom</span>
                                <span class="text-primary-txt truncate">{{ selectedInfo.point.txt_nomPoint || '-' }}</span>
                                <span class="font-medium text-primary-txt">Description</span>
                                <span class="text-primary-txt truncate">{{ selectedInfo.point.txt_descriptionPoint || '-' }}</span>
                                <span class="font-medium text-primary-txt">Situation</span>
                                <span class="text-primary-txt truncate">{{ selectedInfo.point.txt_situationPoint || '-' }}</span>
                                <span class="font-medium text-primary-txt">Département</span>
                                <span class="text-primary-txt truncate">{{ selectedInfo.point.departement?.slt_departement || '-' }}</span>
                                <span class="font-medium text-primary-txt">Commune</span>
                                <span class="text-primary-txt truncate">{{ selectedInfo.point.commune?.slt_commune || '-' }}</span>
                                <span class="font-medium text-primary-txt">Est (X)</span>
                                <span class="text-primary-txt">{{ selectedInfo.point.txt_estPoint || '-' }}</span>
                                <span class="font-medium text-primary-txt">Nord (Y)</span>
                                <span class="text-primary-txt">{{ selectedInfo.point.txt_nordPoint || '-' }}</span>
                                <span class="font-medium text-primary-txt">Hauteur (Z)</span>
                                <span class="text-primary-txt">{{ selectedInfo.point.txt_hauteurPoint || '-' }}</span>
                            </div>
                            <div v-if="selectedInfo.point.txt_documentsAfferants" class="mt-3">
                                <a :href="`/storage/${selectedInfo.point.txt_documentsAfferants}`" target="_blank"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary-dark">
                                    📄 Voir le PDF
                                </a>
                            </div>
                            <div class="mt-4">
                                <button @click="localiserPoint(selectedInfo.point)"
                                        class="flex items-center justify-center w-full gap-2 py-2.5 text-sm font-medium text-white transition bg-primary rounded-xl shadow-sm hover:bg-primary-dark">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Localiser
                                </button>
                            </div>
                        </template>

                        <template v-else>
                            <div class="p-3 mb-3 rounded-xl bg-gradient-to-br from-primary-form to-primary-only/70 border border-primary-only">
                                <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                    <span class="font-medium text-primary">Parcelle</span>
                                    <span class="font-bold text-primary-txt">{{ selectedInfo.parcelle || '—' }}</span>
                                    <span class="font-medium text-primary">Section</span>
                                    <span class="font-bold text-primary-txt">{{ selectedInfo.section || 'Non définie' }}</span>
                                </div>
                            </div>

                            <div v-if="selectedInfo.loading" class="flex items-center justify-center gap-2 py-6 text-primary">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-medium">Chargement…</span>
                            </div>

                            <div v-else-if="selectedInfo.data" class="space-y-2 text-sm">
                                <div class="flex items-center justify-between py-1.5 border-b border-primary-only/70">
                                    <span class="font-medium text-primary-txt">N° Dossier</span>
                                    <span class="font-semibold text-primary-txt">{{ selectedInfo.data.txt_num_dossier || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-primary-only/70">
                                    <span class="font-medium text-primary-txt">NICAD</span>
                                    <span class="font-semibold text-primary-txt">{{ selectedInfo.data.txt_nicad || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-primary-only/70">
                                    <span class="font-medium text-primary-txt">Surface</span>
                                    <span class="font-semibold text-primary-txt">{{ selectedInfo.data.nbr_surface || '-' }} m²</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-primary-only/70">
                                    <span class="font-medium text-primary-txt">N° Lot</span>
                                    <span class="font-semibold text-primary-txt">{{ selectedInfo.data.txt_num_lotissement || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5">
                                    <span class="font-medium text-primary-txt">Lotissement</span>
                                    <span class="font-semibold text-primary-txt text-right truncate ml-2">{{ selectedInfo.data.txt_lotissement || '-' }}</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </Transition>
            </div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showShapefileModal"
                    class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="closeShapefileModal">

                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95">
                        <div v-if="showShapefileModal"
                            class="w-full max-w-2xl max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">

                            <div class="flex items-center justify-between px-6 py-4 border-b border-primary-only bg-gradient-to-r from-primary-form to-primary-footer">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-10 h-10 text-white bg-primary rounded-xl shadow-md">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold text-primary-txt">Gestion des plans</h3>
                                        <p class="text-xs text-primary-txt">Importer et gérer vos shapefiles</p>
                                    </div>
                                </div>
                                <button @click="closeShapefileModal"
                                    class="text-primary-txt/60 transition rounded-lg hover:text-primary hover:bg-primary-form p-1.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex border-b border-primary-only bg-primary-form">
                                <button @click="activeTab = 'list'"
                                    class="flex-1 px-4 py-3 text-sm font-medium transition relative"
                                    :class="activeTab === 'list' ? 'text-primary bg-white' : 'text-primary-txt hover:text-primary'">
                                    <span class="inline-flex items-center gap-2">
                                        📋 Plans importés
                                        <span class="px-2 py-0.5 text-xs rounded-full"
                                            :class="activeTab === 'list' ? 'bg-primary-form text-primary' : 'bg-primary-only text-primary-txt'">
                                            {{ shapefiles.length }}
                                        </span>
                                    </span>
                                    <span v-if="activeTab === 'list'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></span>
                                </button>
                                <button @click="activeTab = 'upload'"
                                    class="flex-1 px-4 py-3 text-sm font-medium transition relative"
                                    :class="activeTab === 'upload' ? 'text-primary bg-white' : 'text-primary-txt hover:text-primary'">
                                    <span class="inline-flex items-center gap-2">⬆️ Importer un shapefile</span>
                                    <span v-if="activeTab === 'upload'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"></span>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6">
                                <div v-if="activeTab === 'list'" class="space-y-3">
                                    <div v-if="!shapefiles.length" class="flex flex-col items-center justify-center py-12 text-primary-txt/60">
                                        <p class="text-sm font-medium">Aucun plan importé</p>
                                        <button @click="activeTab = 'upload'"
                                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark">
                                            ⬆️ Importer maintenant
                                        </button>
                                    </div>

                                    <div v-for="shp in shapefiles" :key="shp.id"
                                        class="flex items-center gap-3 p-3 bg-white border border-primary-only rounded-xl hover:border-primary hover:shadow-sm transition group">
                                        <span class="flex-shrink-0 w-4 h-4 rounded-full ring-2 ring-white shadow"
                                            :style="{ background: shp.couleur || '#3388ff' }"></span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-semibold text-primary-txt truncate">{{ shp.nom }}</p>
                                                <span v-if="!shp.visible" class="px-2 py-0.5 text-[10px] font-medium text-primary-txt bg-primary-form rounded-full">Masqué</span>
                                            </div>
                                            <p class="text-xs text-primary-txt mt-0.5">{{ shp.nb_entites }} entité(s) · SRID {{ shp.srid }}</p>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition">
                                            <button @click="toggleShapefileVisibility(shp)" class="p-2 rounded-lg transition"
                                                :class="shp.visible ? 'text-primary hover:bg-primary-form' : 'text-primary-txt/60 hover:bg-primary-form'">
                                                👁
                                            </button>
                                            <button @click="zoomToShapefile(shp)" class="p-2 text-primary rounded-lg hover:bg-primary-form transition">🔍</button>
                                            <button @click="deleteShapefile(shp)" class="p-2 text-red-600 rounded-lg hover:bg-red-50 transition">🗑</button>
                                        </div>
                                    </div>
                                </div>

                                <form v-else @submit.prevent="uploadShapefile" class="space-y-5">
                                    <BaseInput
                                        v-model="form.nom"
                                        label="Nom du plan"
                                        name="nom"
                                        required
                                        type="text"
                                        placeholder="Ex : Plan cadastral Dinguessou"
                                        wrapper-class="w-full"
                                    />
                                    <BaseSelect
                                        v-model="form.srid"
                                        label="Projection"
                                        name="srid"
                                        :options="[
                                            { label: 'WGS84 — EPSG:4326', value: '4326' },
                                            { label: 'UTM 28N — EPSG:32628', value: '32628' },
                                            { label: 'Lambert 93 — EPSG:2154', value: '2154' },
                                        ]"
                                        wrapper-class="w-full"
                                    />
                                    <div class="p-4 border border-primary-only rounded-xl bg-white space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <BaseInput
                                                v-for="ext in ['shp', 'shx', 'dbf', 'prj']"
                                                :key="ext"
                                                :label="'.' + ext"
                                                type="file"
                                                :name="ext"
                                                :accept="'.' + ext"
                                                :required="ext !== 'prj'"
                                                wrapper-class="w-full"
                                                @change="e => form[ext] = e.target.files?.[0] || null"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-primary-only">
                                        <button type="button" @click="closeShapefileModal"
                                            class="px-4 py-2 text-sm font-medium text-primary-txt bg-white border border-primary-only rounded-lg hover:bg-primary-form">Annuler</button>
                                        <button type="submit" :disabled="uploading"
                                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-60">
                                            {{ uploading ? 'Import…' : 'Importer' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.leaflet-popup-content-wrapper {
    border-radius: 1.25rem !important;
    background: #fff9f4 !important;
    color: #643407 !important;
    border: 1px solid #e6c5a2 !important;
    box-shadow: 0 16px 40px rgba(97, 49, 4, 0.16) !important;
}
.leaflet-popup-content {
    margin: 12px !important;
    font-size: 13px;
    line-height: 1.5;
    color: #643407 !important;
}
.leaflet-popup-content h3,
.leaflet-popup-content h4,
.leaflet-popup-content a {
    color: #613104 !important;
}
.leaflet-popup-tip { background: #fff9f4 !important; }

.plan-info.app-sidepanel {
    background: linear-gradient(180deg, #fff9f4 0%, #faefe6 100%);
    border: 1px solid #e6c5a2;
    border-radius: 1.5rem;
    box-shadow: 0 16px 40px rgba(97, 49, 4, 0.16);
    color: #643407;
}

.leaflet-container { cursor: grab; background: #faefe6; }
.leaflet-container:active { cursor: grabbing; }
.leaflet-interactive { cursor: pointer; }

.user-marker { animation: pulse-blue 1.5s ease-in-out infinite; }
@keyframes pulse-blue {
    0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(37, 99, 235, 0.6); }
    50% { transform: scale(1.1); box-shadow: 0 0 30px rgba(37, 99, 235, 0.8); }
}

.point-list-item.active {
    background-color: #ffe5c5 !important;
    border-color: #c7812e !important;
}

@media (max-width: 640px) {
    .absolute {
        position: relative !important;
        left: auto !important;
        right: auto !important;
        bottom: auto !important;
        top: auto !important;
        transform: none !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        z-index: 10 !important;
    }
    .relative { display: flex; flex-direction: column; gap: 0.5rem; }
    #map { height: 500px !important; }
}
</style>