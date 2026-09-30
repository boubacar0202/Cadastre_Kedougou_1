<script setup>
// ============================================================
// 1. IMPORTS
// ============================================================
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
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
// 3. RÉFÉRENCES
// ============================================================
const map = ref(null);
const selectedLayer = ref(null);
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

// === Shapefiles ===
const shapefiles = ref([]);
const shapefilesLayerGroup = ref(null);
const selectedShapefileId = ref(null);
const showShapefileModal = ref(false);
const activeTab = ref('list');
const uploading = ref(false);
const form = ref({ nom: '', shp: null, shx: null, dbf: null, prj: null, srid: '4326' });

// --- Sites historiques ---
const historicalSites = ref([
    {
        name: "Village de Dinguessou",
        lat: 12.5599,
        lng: -12.1742,
        description: "Ancien village historique de la région.",
        type: "historique"
    },
    {
        name: "Palais de Kédougou",
        lat: 12.5612,
        lng: -12.1710,
        description: "Ancienne résidence coloniale.",
        type: "historique"
    },
]);
const historicLayerGroup = ref(null);

// ============================================================
// 4. UTILITAIRES
// ============================================================
function safeFlyTo(lat, lng, zoom = 20, options = {}) {
    if (!map.value) return;
    if (map.value._loaded) {
        map.value.flyTo([lat, lng], zoom, options);
    } else {
        map.value.whenReady(() => map.value.flyTo([lat, lng], zoom, options));
    }
}

function safeFitBounds(bounds, options = {}) {
    if (!map.value || !bounds) return;
    try {
        if (!bounds.isValid || !bounds.isValid()) return;
    } catch (e) { return; }
    if (map.value._loaded) {
        map.value.fitBounds(bounds, options);
    } else {
        map.value.whenReady(() => map.value.fitBounds(bounds, options));
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

// ============================================================
// 5. RECHERCHE LOCALITÉ
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
                params: {
                    q: searchQuery.value,
                    format: 'json',
                    countrycodes: 'sn',
                    limit: 6,
                    addressdetails: 1
                },
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
            <h4 style="margin: 0 0 8px 0; color: #2563eb;">📍 Localisation</h4>
            <hr>
            <p><b>Latitude :</b> ${lat.toFixed(6)}</p>
            <p><b>Longitude :</b> ${lon.toFixed(6)}</p>
            <p><b>UTM X (28N) :</b> ${Math.round(x)}</p>
            <p><b>UTM Y (28N) :</b> ${Math.round(y)}</p>
        </div>
    `;
    L.popup().setLatLng([lat, lon]).setContent(infoHtml).openOn(map.value);
    toast.info('📍 Localisation trouvée');
}

// ============================================================
// 6. POINTS DENSIFIÉS
// ============================================================
function addPointsToMap() {
    if (!map.value) return;

    if (markersLayer.value) {
        map.value.removeLayer(markersLayer.value);
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

        const marker = L.marker([latitude, longitude], {
            title: point.txt_nomPoint || 'Point'
        }).addTo(markersLayer.value);

        const popupContent = `
            <div style="min-width: 220px; padding: 5px;">
                <h3 style="margin: 0 0 8px 0; color: #2563eb; font-weight: bold;">
                    📍 ${point.txt_nomPoint || 'Point'}
                </h3>
                <hr>
                <p><b>Est (X) :</b> ${point.txt_estPoint || '-'}</p>
                <p><b>Nord (Y) :</b> ${point.txt_nordPoint || '-'}</p>
                <p><b>Hauteur (Z) :</b> ${point.txt_hauteurPoint || '-'}</p>
                ${point.txt_documentsAfferants ? `<p><a href="/storage/${point.txt_documentsAfferants}" target="_blank" style="color: #2563eb;">📄 Voir PDF</a></p>` : ''}
            </div>
        `;
        marker.bindPopup(popupContent);

        marker.on('click', function () {
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
// 7. LOCALISER UN POINT
// ============================================================
function localiserPoint(point) {
    if (!point) return;
    const coords = utmToGps(point.txt_estPoint, point.txt_nordPoint);
    if (!coords) {
        toast.error('Coordonnées invalides');
        return;
    }
    const { latitude, longitude } = coords;
    safeFlyTo(latitude, longitude, 20, { duration: 1.5 });

    const popupContent = `
        <div style="min-width: 200px; padding: 5px;">
            <h4 style="margin: 0 0 8px 0; color: #2563eb;">📍 ${point.txt_nomPoint || 'Point'}</h4>
            <hr>
            <p><b>Est (X) :</b> ${point.txt_estPoint || '-'}</p>
            <p><b>Nord (Y) :</b> ${point.txt_nordPoint || '-'}</p>
            <p><b>Hauteur (Z) :</b> ${point.txt_hauteurPoint || '-'}</p>
            ${point.txt_documentsAfferants ? `<p><a href="/storage/${point.txt_documentsAfferants}" target="_blank" style="color: #2563eb;">📄 Voir PDF</a></p>` : ''}
        </div>
    `;
    if (map.value && map.value._loaded) {
        L.popup().setLatLng([latitude, longitude]).setContent(popupContent).openOn(map.value);
    }

    showInfo.value = true;
    selectedInfo.value = { type: 'point', point: point, loading: false, data: point };
    selectedPointId.value = point.id;
    document.querySelectorAll('.point-list-item').forEach(el => el.classList.remove('active'));
    const listItem = document.querySelector(`[data-point-id="${point.id}"]`);
    if (listItem) listItem.classList.add('active');
}

// ============================================================
// 8. GÉOLOCALISATION
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
            if (userMarker.value) map.value.removeLayer(userMarker.value);
            const userIcon = L.divIcon({
                className: 'user-marker',
                html: `
                    <div style="background:#2563eb; width:20px; height:20px; border-radius:50%; border:3px solid white; box-shadow:0 0 20px rgba(37,99,235,0.6);">
                        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:8px; height:8px; background:white; border-radius:50%;"></div>
                    </div>
                `,
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
// 9. ÉCHELLE DYNAMIQUE
// ============================================================
function updateScale() {
    if (!map.value) return;
    const center = map.value.getCenter();
    const zoom = map.value.getZoom();
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
}

// ============================================================
// 10. CHARGEMENT GEOJSON PRINCIPAL
// ============================================================
function loadGeoJSON() {
    function findSection(feature) {
        const pt = turf.center(feature);
        for (const section of sections.value) {
            const polygon = turf.multiPolygon(section.geometry.coordinates);
            if (turf.booleanPointInPolygon(pt, polygon)) {
                for (const secPoint of sectionPoints.value) {
                    const point = turf.point(secPoint.geometry.coordinates[0]);
                    if (turf.booleanPointInPolygon(point, polygon)) {
                        return secPoint.properties.RefName;
                    }
                }
            }
        }
        return 'N/A';
    }

    function findParcelle(feature) {
        const center = turf.center(feature);
        let nearest = null;
        let minDistance = Infinity;
        parcellePoints.value.forEach(p => {
            const pt = turf.point(p.geometry.coordinates[0]);
            const distance = turf.distance(center, pt);
            if (distance < minDistance) {
                minDistance = distance;
                nearest = p;
            }
        });
        return nearest?.properties?.RefName ?? 'N/A';
    }

    L.geoJSON(geojsonData.value, {
        filter: (feature) => feature.geometry.type === 'MultiPolygon',
        style: { color: '#3388ff', weight: 2, fillOpacity: 0.3 },
        onEachFeature: (feature, layer) => {
            layer.on('click', () => {
                selectedPointId.value = null;
                document.querySelectorAll('.point-list-item').forEach(el => el.classList.remove('active'));

                showInfo.value = true;
                if (selectedLayer.value) {
                    selectedLayer.value.setStyle({ color: '#3388ff', weight: 2, fillOpacity: 0.3 });
                }
                layer.setStyle({ color: '#f00000', weight: 3, fillOpacity: 0.6 });
                selectedLayer.value = layer;
                const parcelleNum = findParcelle(feature);
                const section = findSection(feature);

                selectedInfo.value = {
                    type: 'parcelle',
                    parcelle: parcelleNum,
                    section: section,
                    loading: true,
                    data: null
                };

                axios.post('/terrain/search', {
                    parcelle: parcelleNum,
                    section: section
                })
                .then(res => {
                    selectedInfo.value.data = res.data;
                    selectedInfo.value.loading = false;
                })
                .catch(() => {
                    selectedInfo.value.loading = false;
                });

                safeFitBounds(layer.getBounds());
            });
        }
    }).addTo(map.value);
}

// ============================================================
// 11. SITES HISTORIQUES ✅ CORRIGÉ (plus de PNG 404)
// ============================================================

function addHistoricalSites() {
    const group = L.layerGroup();
    historicalSites.value.forEach(site => {
        // ✅ divIcon avec emoji — aucune image requise
        const icon = L.divIcon({
            className: 'historic-marker',
            html: `
                <div style="
                    background: #f59e0b;
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    border: 3px solid white;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                ">🏛️</div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const marker = L.marker([site.lat, site.lng], { icon })
            .bindPopup(`
                <div style="min-width: 200px;">
                    <h4 style="margin: 0 0 5px 0; color: #b45309;">🏛️ ${site.name}</h4>
                    <p style="margin: 0; font-size: 13px;">${site.description || 'Site historique'}</p>
                    <hr style="margin: 5px 0;">
                    <small style="color: gray;">${site.lat.toFixed(5)}, ${site.lng.toFixed(5)}</small>
                </div>
            `);
        group.addLayer(marker);
    });
    historicLayerGroup.value = group;
    group.addTo(map.value);
    updateHistoricVisibility();
    map.value.on('zoomend', updateHistoricVisibility);
}

function updateHistoricVisibility() {
    if (!map.value || !historicLayerGroup.value) return;
    const zoom = map.value.getZoom();
    if (zoom >= 11) {
        if (!map.value.hasLayer(historicLayerGroup.value)) {
            map.value.addLayer(historicLayerGroup.value);
        }
    } else {
        if (map.value.hasLayer(historicLayerGroup.value)) {
            map.value.removeLayer(historicLayerGroup.value);
        }
    }
}

// ============================================================
// 12. SWITCH UTM/GPS
// ============================================================


const convert = () => {
    convertToUTM.value = !convertToUTM.value;
    loadGeoJSON();
    addPointsToMap();
    toast[convertToUTM.value ? 'success' : 'info'](
        convertToUTM.value ? 'Coordonnées UTM activées' : 'Coordonnées GPS activées'
    );
};

// ============================================================
// 13. LISTE
// ============================================================
function toggleList() {
    showList.value = !showList.value;
}

// ============================================================
// 14. STATISTIQUES
// ============================================================
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
// 15. MODALE SHAPEFILES
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
// 16. CHARGEMENT DES SHAPEFILES
// ============================================================
async function chargerShapefiles() {
    console.log('═══════════════════════════════════════');
    console.log('🔵 [chargerShapefiles] DÉBUT');
    console.log('   shapefiles.value.length =', shapefiles.value.length);
    console.log('   Détail :', JSON.stringify(
        shapefiles.value.map(s => ({
            id: s.id, nom: s.nom, nom_table: s.nom_table, visible: s.visible
        })), null, 2
    ));

    if (!map.value) {
        console.error('❌ map.value est NULL');
        return;
    }

    if (shapefilesLayerGroup.value) {
        map.value.removeLayer(shapefilesLayerGroup.value);
    }
    shapefilesLayerGroup.value = L.layerGroup().addTo(map.value);
    console.log('✅ Groupe de couches créé');

    if (!shapefiles.value.length) {
        console.warn('⚠️ Aucun shapefile à charger');
        return;
    }

    for (const shp of shapefiles.value) {
        console.log(`\n🔵 ─── Shapefile #${shp.id} "${shp.nom}" ───`);
        console.log(`   visible = ${shp.visible}`);

        if (!shp.visible) {
            console.log(`   ⏭️  Ignoré`);
            continue;
        }

        const url = `/api/shapefiles/${shp.id}/geojson`;
        console.log(`   🌐 GET ${url}`);

        try {
            const response = await axios.get(url);
            console.log(`   ✅ Status : ${response.status}`);
            console.log(`   📦 Content-Type : ${response.headers['content-type']}`);
            console.log(`   📦 Type de data : ${typeof response.data}`);

            const data = response.data;

            if (!data || !data.features) {
                console.error('   ❌ Structure GeoJSON invalide :', data);
                continue;
            }

            console.log(`   📊 ${data.features.length} features reçues`);

            if (data.features.length > 0) {
                const first = data.features[0];
                console.log(`   📍 Type geom : ${first.geometry?.type}`);
                console.log(`   📍 Premières coords :`,
                    JSON.stringify(first.geometry?.coordinates?.[0]?.[0] || first.geometry?.coordinates?.[0]).substring(0, 200)
                );
            }

            const layer = L.geoJSON(data, {
                style: {
                    color: shp.couleur || '#3388ff',
                    weight: 2,
                    fillOpacity: 0.25,
                },
                onEachFeature: (feature, lyr) => {
                    const props = feature.properties || {};
                    const rows = Object.entries(props)
                        .filter(([k]) => !k.startsWith('geom'))
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

            const bounds = layer.getBounds();
            console.log(`   📐 Bounds valides : ${bounds.isValid()}`);
            if (bounds.isValid()) {
                console.log(`      NW:`, bounds.getNorthWest());
                console.log(`      SE:`, bounds.getSouthEast());
                safeFitBounds(bounds, { padding: [30, 30] });
                console.log(`   ✅ Zoom effectué`);
            } else {
                console.warn(`   ⚠️  Bounds invalides`);
            }

        } catch (e) {
            console.error(`   ❌ ERREUR #${shp.id} :`);
            console.error(`      Message : ${e.message}`);
            console.error(`      Status : ${e.response?.status}`);
            console.error(`      Data :`, e.response?.data);
            toast.error(`Impossible de charger "${shp.nom}"`);
        }
    }

    console.log('🔵 [chargerShapefiles] FIN');
    console.log('═══════════════════════════════════════');
}

// ============================================================
// 17. UPLOAD
// ============================================================
async function uploadShapefile() {
    uploading.value = true;
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => { if (v) fd.append(k, v); });

    try {
        await axios.post('/shapefiles', fd, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('✅ Shapefile importé avec succès');

        const { data } = await axios.get('/shapefiles');
        shapefiles.value = data;
        await chargerShapefiles();

        activeTab.value = 'list';
        form.value = { nom: '', shp: null, shx: null, dbf: null, prj: null, srid: '4326' };
        document.querySelectorAll('input[type="file"]').forEach(i => i.value = '');
    } catch (e) {
        const msg = e.response?.data?.errors?.shp
            || e.response?.data?.message
            || 'Erreur lors de l\'import';
        toast.error(msg);
    } finally {
        uploading.value = false;
    }
}

// ============================================================
// 18. VISIBILITÉ / SUPPRESSION / ZOOM
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
        safeFitBounds(targetLayer.getBounds(), { padding: [40, 40] });
        closeShapefileModal();
    } else {
        toast.warning('Shapefile masqué ou vide');
    }
}

// ============================================================
// 19. INITIALISATION
// ============================================================
onMounted(() => {
    const kedougou = [12.5599, -12.1742];
    map.value = L.map('map', {
        zoomAnimation: false,
        fadeAnimation: false,
        markerZoomAnimation: false
    }).setView(kedougou, 12);

    const googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google', maxZoom: 24
    });
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    });
    const googleSatellite = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google', maxZoom: 24
    });
    const googleStreets = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        attribution: '&copy; Google', maxZoom: 22
    });
    const openTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenTopoMap contributors', maxZoom: 17
    });

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

    map.value.whenReady(async () => {
        try {
            // 1. GeoJSON principal
            const response = await fetch('/storage/dinguessou_plateau_complet.geojson');
            geojsonData.value = await response.json();
            loadGeoJSON();

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

            // 2. Points densifiés
            pointsDensifies.value = props.point_densifiers;
            addPointsToMap();

            // 3. Sites historiques
            addHistoricalSites();

            // 4. Shapefiles
            console.log('📦 props.shapefiles reçus :', props.shapefiles);
            shapefiles.value = props.shapefiles || [];
            console.log('📦 shapefiles.value après assignation :', shapefiles.value);
            await chargerShapefiles();

        } catch (error) {
            console.error('❌ Erreur chargement :', error);
            toast.error('Erreur chargement des données');
        }
    });
});
</script>

<template>
    <Head title="Plan cadastral - Kédougou" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="flex items-center gap-3 text-2xl font-bold text-gray-800">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 rounded-xl text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </span>
                    Plan cadastral – Kédougou
                </h2>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Badge points -->
                    <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-800 bg-blue-100 rounded-full shadow-sm">
                        <span class="flex items-center justify-center w-6 h-6 text-xs text-white bg-blue-600 rounded-full">📌</span>
                        {{ stats.total }} points densifiés
                    </span>

                    <!-- Bouton Gérer les plans -->
                    <button @click="openShapefileModal('list')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition bg-emerald-600 rounded-lg shadow-sm hover:bg-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Gérer les plans
                        <span v-if="shapefiles.length" class="ml-1 px-2 py-0.5 text-xs bg-white/25 rounded-full">
                            {{ shapefiles.length }}
                        </span>
                    </button>

                    <!-- Toggle liste -->
                    <button @click="toggleList"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ showList ? 'Afficher' : 'Masquer' }} la liste
                    </button>

                    <!-- Géolocalisation -->
                    <button @click="getUserLocation" :disabled="locationLoading"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 disabled:opacity-60">
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
            <div class="relative overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xl">
                <!-- Carte -->
                <div id="map" class="w-full h-[700px]"></div>

                <!-- Recherche localisation -->
                <div class="absolute top-4 left-1/2 z-[1000] w-11/12 max-w-md -translate-x-1/2">
                    <div class="relative">
                        <input v-model="searchQuery" @input="searchLocation" 
                            type="text" 
                            placeholder="🔍 Rechercher une localité (ex: Kédougou, Saraya…)" 
                            class="w-full px-5 py-3 pr-12 text-sm bg-white/95 rounded-xl border-0 shadow-lg backdrop-blur-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <svg class="absolute right-4 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <div v-if="searchResults.length > 0" 
                            class="absolute top-full left-0 right-0 mt-2 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl border border-gray-200 max-h-60 overflow-y-auto divide-y divide-gray-100">
                            <div v-for="result in searchResults" :key="result.place_id" 
                                @click="selectLocation(result)" 
                                class="flex items-start gap-3 px-4 py-3 transition cursor-pointer hover:bg-blue-50">
                                <span class="mt-0.5 text-blue-500">📍</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-800">{{ result.display_name.split(',').slice(0,3).join(',') }}</div>
                                    <div class="text-xs text-gray-500 capitalize">{{ result.type || 'Localité' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-auto px-3 py-1 mx-auto mt-2 text-xs text-center text-gray-500 bg-white/80 rounded-full backdrop-blur-sm">
                        ⚡ Navigation uniquement – n’affecte pas les points
                    </div>
                </div>

                <!-- Switch GPS/UTM -->
                <div class="absolute top-24 left-4 z-[1000]">
                    <div class="flex flex-col items-center gap-1 p-2 bg-white/90 rounded-2xl shadow-lg border border-gray-200 backdrop-blur-sm">
                        <span class="text-[10px] font-medium tracking-wider text-gray-500 uppercase">Projection</span>
                        <button @click="convert" 
                            class="relative w-14 h-8 rounded-full transition-all duration-300 shadow-inner overflow-hidden"
                            :class="convertToUTM ? 'bg-green-500' : 'bg-gray-300'">
                            <div class="absolute inset-0 bg-white/20 backdrop-blur-sm"></div>
                            <div class="absolute top-1 w-6 h-6 rounded-full bg-white shadow-md transition-all duration-300 flex items-center justify-center text-[10px] font-bold"
                                :class="convertToUTM ? 'left-7' : 'left-1'">
                                {{ convertToUTM ? 'UTM' : 'GPS' }}
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Échelle -->
                <div class="absolute bottom-6 left-1/2 z-[1000] -translate-x-1/2 bg-white/90 backdrop-blur-sm px-5 py-2 rounded-full shadow-xl border border-gray-200/80 text-sm font-medium flex items-center gap-2 select-none">
                    <span class="text-gray-500">Échelle :</span>
                    <span class="font-mono font-bold text-gray-800">1 / {{ scaleDenominator.toLocaleString() }}</span>
                </div>

                <!-- Liste des points densifiés -->
                <div v-if="!showList" 
                    class="absolute bottom-6 left-6 z-[1000] w-80 max-h-[60%] bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 overflow-hidden flex flex-col transition-all duration-300">
                    <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-blue-50 to-blue-100/50 border-gray-200">
                        <h3 class="flex items-center gap-2 font-semibold text-blue-800">
                            <span class="flex items-center justify-center w-6 h-6 text-xs text-white bg-blue-600 rounded-full">📍</span>
                            Points densifiés
                        </h3>
                        <span class="px-3 py-1 text-xs text-white bg-blue-600 rounded-full shadow-sm">{{ filteredPoints.length }}</span>
                    </div>
                    <div class="flex-1 p-3 space-y-1 overflow-y-auto">
                        <input v-model="searchQueryList" 
                            type="text" 
                            placeholder="🔍 Filtrer les points…" 
                            class="w-full px-3 py-2 mb-2 text-sm bg-white/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" />
                        <div v-if="filteredPoints.length === 0" class="py-8 text-sm text-center text-gray-400">
                            <span class="block text-3xl mb-2">🔍</span>
                            Aucun point trouvé
                        </div>
                        <div v-for="point in filteredPoints" :key="point.id" 
                            :data-point-id="point.id" 
                            class="point-list-item flex items-start gap-3 p-3 transition-all border border-transparent rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-200"
                            :class="{ 'active bg-blue-100 border-blue-400 shadow-sm': selectedPointId === point.id }" 
                            @click="localiserPoint(point)">
                            <span class="mt-0.5 text-blue-500">📍</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-800 truncate">{{ point.txt_nomPoint || 'Point' }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ point.txt_descriptionPoint || 'Aucune description' }}</div>
                                <div class="flex items-center gap-1 mt-0.5 text-xs text-gray-400 truncate">
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

                <!-- Panneau d'infos -->
                <div v-if="showInfo && selectedInfo" 
                    class="absolute bottom-6 right-6 z-[1000] w-80 max-h-[60%] bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 overflow-y-auto p-5 transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="flex items-center gap-2 text-lg font-bold text-blue-700">
                            <span>{{ selectedInfo.type === 'point' ? '📍 Point densifié' : '🏠 Parcelle' }}</span>
                        </h3>
                        <button @click="showInfo = false" class="text-gray-400 transition hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="my-3 border-t border-gray-200"></div>
                    
                    <!-- Infos point -->
                    <template v-if="selectedInfo.type === 'point'">
                        <div class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            <span class="font-medium text-gray-500">Nom</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.txt_nomPoint || '-' }}</span>
                            <span class="font-medium text-gray-500">Description</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.txt_descriptionPoint || '-' }}</span>
                            <span class="font-medium text-gray-500">Situation</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.txt_situationPoint || '-' }}</span>
                            <span class="font-medium text-gray-500">Département</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.departement?.slt_departement || '-' }}</span>
                            <span class="font-medium text-gray-500">Arrondissement</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.arrondissement?.slt_arrondissement || '-' }}</span>
                            <span class="font-medium text-gray-500">Commune</span>
                            <span class="text-gray-800 truncate">{{ selectedInfo.point.commune?.slt_commune || '-' }}</span>
                            <span class="font-medium text-gray-500">Est (X)</span>
                            <span class="text-gray-800">{{ selectedInfo.point.txt_estPoint || '-' }}</span>
                            <span class="font-medium text-gray-500">Nord (Y)</span>
                            <span class="text-gray-800">{{ selectedInfo.point.txt_nordPoint || '-' }}</span>
                            <span class="font-medium text-gray-500">Hauteur (Z)</span>
                            <span class="text-gray-800">{{ selectedInfo.point.txt_hauteurPoint || '-' }}</span>
                        </div>
                        <div v-if="selectedInfo.point.txt_documentsAfferants" class="mt-3">
                            <a :href="`/storage/${selectedInfo.point.txt_documentsAfferants}`" target="_blank" 
                                class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                                📄 Voir le PDF
                            </a>
                        </div>
                        <div class="mt-4">
                            <button @click="localiserPoint(selectedInfo.point)" 
                                    class="flex items-center justify-center w-full gap-2 py-2.5 text-sm font-medium text-white transition bg-blue-600 rounded-xl shadow-sm hover:bg-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Localiser
                            </button>
                        </div>
                    </template>

                    <!-- Infos parcelle -->
                    <template v-else>
                        <div class="space-y-1 text-sm">
                            <p><span class="font-medium text-gray-500">Parcelle :</span> {{ selectedInfo.parcelle }}</p>
                            <p><span class="font-medium text-gray-500">Section :</span> {{ selectedInfo.section }}</p>
                        </div>
                        <div v-if="selectedInfo.loading" class="flex items-center gap-2 mt-4 text-blue-500">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Chargement…
                        </div>
                        <div v-else-if="selectedInfo.data" class="mt-3 space-y-1 text-sm border-t border-gray-200 pt-3">
                            <p><span class="font-medium text-gray-500">N° Dossier :</span> {{ selectedInfo.data.txt_num_dossier || '-' }}</p>
                            <p><span class="font-medium text-gray-500">NICAD :</span> {{ selectedInfo.data.txt_nicad || '-' }}</p>
                            <p><span class="font-medium text-gray-500">Surface :</span> {{ selectedInfo.data.nbr_surface || '-' }}</p>
                            <p><span class="font-medium text-gray-500">N° Lot :</span> {{ selectedInfo.data.txt_num_lotissement || '-' }}</p>
                            <p><span class="font-medium text-gray-500">Lotissement :</span> {{ selectedInfo.data.txt_lotissement || '-' }}</p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- MODALE : Gestion des Shapefiles -->
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

                            <!-- En-tête modale -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-emerald-100/50">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-10 h-10 text-white bg-emerald-600 rounded-xl shadow-md">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">Gestion des plans</h3>
                                        <p class="text-xs text-gray-500">Importer et gérer vos shapefiles</p>
                                    </div>
                                </div>
                                <button @click="closeShapefileModal"
                                    class="text-gray-400 transition rounded-lg hover:text-gray-600 hover:bg-gray-100 p-1.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Onglets -->
                            <div class="flex border-b border-gray-200 bg-gray-50">
                                <button @click="activeTab = 'list'"
                                    class="flex-1 px-4 py-3 text-sm font-medium transition relative"
                                    :class="activeTab === 'list' ? 'text-emerald-700 bg-white' : 'text-gray-500 hover:text-gray-700'">
                                    <span class="inline-flex items-center gap-2">
                                        📋 Plans importés
                                        <span class="px-2 py-0.5 text-xs rounded-full"
                                            :class="activeTab === 'list' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600'">
                                            {{ shapefiles.length }}
                                        </span>
                                    </span>
                                    <span v-if="activeTab === 'list'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600"></span>
                                </button>
                                <button @click="activeTab = 'upload'"
                                    class="flex-1 px-4 py-3 text-sm font-medium transition relative"
                                    :class="activeTab === 'upload' ? 'text-emerald-700 bg-white' : 'text-gray-500 hover:text-gray-700'">
                                    <span class="inline-flex items-center gap-2">⬆️ Importer un shapefile</span>
                                    <span v-if="activeTab === 'upload'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-600"></span>
                                </button>
                            </div>

                            <!-- Contenu -->
                            <div class="flex-1 overflow-y-auto p-6">

                                <!-- ONGLET LISTE -->
                                <div v-if="activeTab === 'list'" class="space-y-3">
                                    <div v-if="!shapefiles.length"
                                        class="flex flex-col items-center justify-center py-12 text-gray-400">
                                        <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                        </svg>
                                        <p class="text-sm font-medium">Aucun plan importé</p>
                                        <p class="mt-1 text-xs">Commencez par importer un shapefile</p>
                                        <button @click="activeTab = 'upload'"
                                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                                            ⬆️ Importer maintenant
                                        </button>
                                    </div>

                                    <div v-for="shp in shapefiles" :key="shp.id"
                                        class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-xl hover:border-emerald-300 hover:shadow-sm transition group">

                                        <span class="flex-shrink-0 w-4 h-4 rounded-full ring-2 ring-white shadow"
                                            :style="{ background: shp.couleur || '#3388ff' }"></span>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-semibold text-gray-800 truncate">{{ shp.nom }}</p>
                                                <span v-if="!shp.visible"
                                                    class="px-2 py-0.5 text-[10px] font-medium text-gray-500 bg-gray-100 rounded-full">
                                                    Masqué
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                {{ shp.nb_entites }} entité(s) · SRID {{ shp.srid }}
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition">
                                            <button @click="toggleShapefileVisibility(shp)"
                                                :title="shp.visible ? 'Masquer' : 'Afficher'"
                                                class="p-2 rounded-lg transition"
                                                :class="shp.visible ? 'text-emerald-600 hover:bg-emerald-50' : 'text-gray-400 hover:bg-gray-100'">
                                                <svg v-if="shp.visible" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            </button>

                                            <button @click="zoomToShapefile(shp)" title="Centrer"
                                                class="p-2 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </button>

                                            <button @click="deleteShapefile(shp)" title="Supprimer"
                                                class="p-2 text-red-600 rounded-lg hover:bg-red-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- ONGLET UPLOAD -->
                                <form v-else @submit.prevent="uploadShapefile" class="space-y-5">

                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-700">
                                            Nom du plan <span class="text-red-500">*</span>
                                        </label>
                                        <input v-model="form.nom" required
                                            type="text"
                                            placeholder="Ex : Plan cadastral Dinguessou"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none" />
                                    </div>

                                    <div>
                                        <label class="block mb-1.5 text-sm font-medium text-gray-700">
                                            Système de projection
                                        </label>
                                        <select v-model="form.srid"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none bg-white">
                                            <option value="4326">🌍 WGS84 — EPSG:4326 (degrés)</option>
                                            <option value="32628">📐 UTM 28N — EPSG:32628 (mètres)</option>
                                            <option value="2154">📐 Lambert 93 — EPSG:2154 (mètres)</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Vérifiez le fichier <code class="px-1 bg-gray-100 rounded">.prj</code> ou demandez au fournisseur
                                        </p>
                                    </div>

                                    <div class="p-4 border border-gray-200 rounded-xl bg-gray-50/50 space-y-3">
                                        <p class="text-sm font-medium text-gray-700">Fichiers du shapefile</p>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <label class="cursor-pointer">
                                                <span class="block text-xs font-medium text-gray-600 mb-1">
                                                    .shp <span class="text-red-500">*</span>
                                                </span>
                                                <div class="flex items-center gap-2 px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:border-emerald-400 transition"
                                                    :class="form.shp ? 'border-emerald-400 bg-emerald-50' : ''">
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span class="truncate text-gray-700" :class="form.shp ? 'text-emerald-700 font-medium' : ''">
                                                        {{ form.shp?.name || 'Choisir…' }}
                                                    </span>
                                                </div>
                                                <input type="file" accept=".shp" required class="hidden"
                                                    @change="e => form.shp = e.target.files[0]" />
                                            </label>

                                            <label class="cursor-pointer">
                                                <span class="block text-xs font-medium text-gray-600 mb-1">
                                                    .shx <span class="text-red-500">*</span>
                                                </span>
                                                <div class="flex items-center gap-2 px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:border-emerald-400 transition"
                                                    :class="form.shx ? 'border-emerald-400 bg-emerald-50' : ''">
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <span class="truncate text-gray-700" :class="form.shx ? 'text-emerald-700 font-medium' : ''">
                                                        {{ form.shx?.name || 'Choisir…' }}
                                                    </span>
                                                </div>
                                                <input type="file" accept=".shx" required class="hidden"
                                                    @change="e => form.shx = e.target.files[0]" />
                                            </label>

                                            <label class="cursor-pointer">
                                                <span class="block text-xs font-medium text-gray-600 mb-1">
                                                    .dbf <span class="text-red-500">*</span>
                                                </span>
                                                <div class="flex items-center gap-2 px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:border-emerald-400 transition"
                                                    :class="form.dbf ? 'border-emerald-400 bg-emerald-50' : ''">
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3z" />
                                                    </svg>
                                                    <span class="truncate text-gray-700" :class="form.dbf ? 'text-emerald-700 font-medium' : ''">
                                                        {{ form.dbf?.name || 'Choisir…' }}
                                                    </span>
                                                </div>
                                                <input type="file" accept=".dbf" required class="hidden"
                                                    @change="e => form.dbf = e.target.files[0]" />
                                            </label>

                                            <label class="cursor-pointer">
                                                <span class="block text-xs font-medium text-gray-600 mb-1">
                                                    .prj <span class="text-gray-400">(optionnel)</span>
                                                </span>
                                                <div class="flex items-center gap-2 px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg hover:border-emerald-400 transition"
                                                    :class="form.prj ? 'border-emerald-400 bg-emerald-50' : ''">
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="truncate text-gray-700" :class="form.prj ? 'text-emerald-700 font-medium' : ''">
                                                        {{ form.prj?.name || 'Choisir…' }}
                                                    </span>
                                                </div>
                                                <input type="file" accept=".prj" class="hidden"
                                                    @change="e => form.prj = e.target.files[0]" />
                                            </label>
                                        </div>

                                        <p class="text-xs text-gray-500 flex items-start gap-1.5">
                                            <span>💡</span>
                                            <span>Les 4 fichiers doivent avoir le même nom de base. Ex : <code class="px-1 bg-gray-100 rounded">plan.shp</code>, <code class="px-1 bg-gray-100 rounded">plan.shx</code>, <code class="px-1 bg-gray-100 rounded">plan.dbf</code>, <code class="px-1 bg-gray-100 rounded">plan.prj</code></span>
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                                        <button type="button" @click="closeShapefileModal"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                            Annuler
                                        </button>
                                        <button type="submit" :disabled="uploading"
                                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white transition bg-emerald-600 rounded-lg shadow-sm hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed">
                                            <svg v-if="uploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            {{ uploading ? 'Import en cours…' : 'Importer le shapefile' }}
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

/* ==========================================================
   STYLES GLOBAUX
   ========================================================== */

.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15) !important;
}
.leaflet-popup-content {
    margin: 12px !important;
    font-size: 13px;
    line-height: 1.5;
}
.leaflet-popup-tip {
    background: white !important;
}

.user-marker {
    animation: pulse-blue 1.5s ease-in-out infinite;
}
@keyframes pulse-blue {
    0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(37, 99, 235, 0.6); }
    50% { transform: scale(1.1); box-shadow: 0 0 30px rgba(37, 99, 235, 0.8); }
}

.point-list-item.active {
    background-color: #eff6ff !important;
    border-color: #3b82f6 !important;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
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
    .relative {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    #map {
        height: 500px !important;
    }
}
</style>