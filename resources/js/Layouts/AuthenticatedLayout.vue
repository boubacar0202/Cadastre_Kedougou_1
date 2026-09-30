<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3'; 
import NavLink from '@/Components/NavLink.vue';
import { toast } from "@/Components/AppToast"; 
import axios from 'axios';
import { route } from 'ziggy-js'

// Récupérer l'utilisateur depuis les props Inertia
const page = usePage();
const user = page.props.auth?.user;
const isMenuOpen = ref(false);
const isDesktop = ref(false);
const showingNavigationDropdown = ref(false);
const unreadTotal = ref(0);
const previousUnreadTotal = ref(0);
const isSettingsOpen = ref(false)

// 🔔 POLLING POUR LES MESSAGES NON LUS
let intervalUnread;

// 🔔 FONCTION SON DE NOTIFICATION 
const playNotificationSound = () => {
    try {
        // Créer un contexte audio
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Créer un oscillateur
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Paramètres du son - double bip
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        // 🔥 Volume MAXIMUM (1 = 100%)
        gainNode.gain.setValueAtTime(1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
        
        // Jouer le premier bip
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
        
        // Deuxième bip après un court délai
        setTimeout(() => {
            try {
                const audioContext2 = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator2 = audioContext2.createOscillator();
                const gainNode2 = audioContext2.createGain();
                
                oscillator2.connect(gainNode2);
                gainNode2.connect(audioContext2.destination);
                
                oscillator2.frequency.value = 1000;
                oscillator2.type = 'sine';
                // 🔥 Volume MAXIMUM pour le second bip
                gainNode2.gain.setValueAtTime(1, audioContext2.currentTime);
                gainNode2.gain.exponentialRampToValueAtTime(0.01, audioContext2.currentTime + 0.15);
                
                oscillator2.start(audioContext2.currentTime);
                oscillator2.stop(audioContext2.currentTime + 0.15);
            } catch (e) {
                console.warn('Erreur second bip:', e);
            }
        }, 200);
        
    } catch (error) {
        console.warn('Erreur de lecture du son:', error);
        // Fallback avec Audio
        try {
            const audio = new Audio();
            audio.src = 'sounds/son_notification.mp3'; 
            // 🔥 Volume MAXIMUM
            audio.volume = 1;
            audio.play().catch(e => console.warn('Fallback audio échoué:', e));
        } catch (e) {
            console.warn('Fallback complet échoué:', e);
        }
    }
};

// Fonction pour récupérer le total des messages non lus
const fetchUnreadTotal = async () => {
    try {
        const res = await axios.get("/messages/unread-total");
        const newTotal = res.data.total || 0;
        
        // 🔔 Si le nombre de messages non lus augmente, jouer le son
        if (newTotal > previousUnreadTotal.value && newTotal > 0) {
            playNotificationSound();
            // Notification visuelle (toast)
            if (newTotal - previousUnreadTotal.value === 1) {
                toast.info(`📩 Nouveau message reçu !`);
            } else {
                toast.info(`📩 ${newTotal - previousUnreadTotal.value} nouveaux messages !`);
            }
        }
        
        previousUnreadTotal.value = unreadTotal.value;
        unreadTotal.value = newTotal;
        
    } catch (e) {
        console.error("Erreur fetch unread total:", e);
    }
};

// Ouvrir automatiquement si on est dans parametre.*
if (route().current('parametre.*')) {
    isSettingsOpen.value = true;
}

// Toast pour les flash messages
onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success)
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error)
    }
    
    // Charger le total des messages non lus
    fetchUnreadTotal();
    
    // Mettre à jour toutes les 3 secondes (plus réactif)
    intervalUnread = setInterval(fetchUnreadTotal, 3000);
});

// Nettoyer l'intervalle
onUnmounted(() => {
    if (intervalUnread) {
        clearInterval(intervalUnread);
    }
});

// Surveille la largeur d'écran
function checkScreenSize() {
    isDesktop.value = window.innerWidth >= 768
    if (isDesktop.value) {
        const savedMenuState = localStorage.getItem('menuOpen');
        if (savedMenuState === 'true') {
            isMenuOpen.value = true;
        }
    }
}

// Toggle menu avec sauvegarde
function toggleMenu() {
    isMenuOpen.value = !isMenuOpen.value;
    localStorage.setItem('menuOpen', isMenuOpen.value.toString());
}

// Garde l’état du menu après rafraîchissement
onMounted(() => {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize)
    
    const savedMenuState = localStorage.getItem('menuOpen');
    if (savedMenuState !== null) {
        isMenuOpen.value = savedMenuState === 'true';
    } else {
        isMenuOpen.value = window.innerWidth >= 768;
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize)
})

// Mémoriser l'état dans localStorage pour persistance
watch(isMenuOpen, (newValue) => {
    localStorage.setItem('menuOpen', newValue.toString())
})
</script>

<template>
    <Head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </Head>

    <div class="flex min-h-screen bg-primary-layout">
        <!-- Sidebar -->
        <nav class="w-64 h-screen bg-white border-r border-primary-only fixed flex flex-col p-4 overflow-y-auto">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-6">
                <Link :href="route('dashboard')">
                    <img src="/logo.jpg" alt="Logo" class="h-100 w-auto">
                </Link>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-col space-y-1">
                <!-- Bouton d'ouverture/fermeture -->
                <button 
                    @click="toggleMenu"
                    class="p-2 text-white rounded-xl shadow-md mb-5
                        bg-gradient-to-r from-primary to-primary/80
                        hover:to-primary hover:shadow-lg hover:-translate-y-0.5
                        hover:from-primary hover:to-primary-dark
                        hover:bg-gradient-to-r
                        hover:shadow-lg hover:scale-[1.02]
                        transition-all duration-300 ease-in-out
                        flex items-center gap-2 text-lg justify-center"
                >
                    ☰ Menu
                </button>
                
                <transition name="slide">
                    <div v-if="isMenuOpen || !isDesktop" class="flex flex-col space-y-1">
                        <!-- Navigation Links -->
                        <div class="flex flex-col space-y-1"> 
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('dashboard')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="#ff7a18"/>
                                </svg>
                                Accueil
                            </NavLink>
                            
                            <NavLink :href="route('secretariat.create')" :active="route().current('secretariat.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('secretariat.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h7l2 2h7v10a2 2 0 0 1-2 2H4V6z" fill="#ff7a18"/>
                                    <path d="M12 11v6m-3-3h6" stroke="white" stroke-width="2"/>
                                </svg>
                                Enregistrer Dossier
                            </NavLink>
                            
                            <NavLink :href="route('geometre.create')" :active="route().current('geometre.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('geometre.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z" fill="#ff7a18"/>
                                    <circle cx="12" cy="10" r="2" fill="white"/>
                                </svg>
                                Enquête Cadastrale
                            </NavLink>
                            
                            <NavLink :href="route('matriceCadastrale.create')" :active="route().current('matriceCadastrale.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('matriceCadastrale.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="4" width="7" height="7" fill="#6a11cb"/>
                                    <rect x="14" y="4" width="7" height="7" fill="#ff7a18"/>
                                    <rect x="3" y="13" width="7" height="7" fill="#ff7a18"/>
                                    <rect x="14" y="13" width="7" height="7" fill="#6a11cb"/>
                                </svg>
                                Matrice Cadastrale
                            </NavLink>
                            
                            <NavLink :href="route('donnee.create')" :active="route().current('donnee.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('donnee.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <ellipse cx="12" cy="5" rx="8" ry="3" fill="#ff7a18"/>
                                    <path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5" fill="#4facfe"/>
                                    <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" fill="#ff7a18"/>
                                </svg>
                                Base de Données
                            </NavLink>
                            
                            <NavLink :href="route('mutation.create')" :active="route().current('mutation.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('mutation.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M7 7h11l-3-3m3 3l-3 3" stroke="#ff512f" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M17 17H6l3 3m-3-3l3-3" stroke="#dd2476" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Mutations
                            </NavLink>
                            
                            <!-- 🔔 Discussion avec compteur de messages non lus -->
                            <NavLink :href="route('message.create')" :active="route().current('message.create')" 
                                class="relative hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('message.create')}">
                                <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z" fill="#ff7a18"/>
                                    <circle cx="17" cy="12" r="2" fill="white"/>
                                </svg>
                                Discussion
                                <!-- 🔔 Badge avec le nombre total de messages non lus -->
                                <span v-if="unreadTotal > 0"
                                    class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold bg-red-500 text-white rounded-full shadow-lg animate-pulse">
                                    {{ unreadTotal }}
                                </span>
                            </NavLink>
                            
                            <NavLink :href="route('plan.create')" :active="route().current('plan.create')" 
                                class="relative hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('plan.create')}">
                                <svg class="w-6 h-6 mr-4 globe" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="earth" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#ff7a18"/>
                                            <stop offset="100%" stop-color="#ff7a18"/>
                                        </linearGradient>
                                    </defs>
                                    <circle cx="12" cy="12" r="10" fill="url(#earth)" />
                                    <path d="M12 2c3 3 3 17 0 20" stroke="white" stroke-width="1.2" opacity="0.8"/>
                                    <path d="M12 2c-3 3-3 17 0 20" stroke="white" stroke-width="1.2" opacity="0.8"/>
                                    <path d="M2 12h20" stroke="white" stroke-width="1.2" opacity="0.8"/>
                                    <path d="M4 8h16" stroke="white" stroke-width="0.8" opacity="0.6"/>
                                    <path d="M4 16h16" stroke="white" stroke-width="0.8" opacity="0.6"/>
                                </svg>
                                Plan
                            </NavLink>
                            
                            <!-- Paramètres -->
                            <div
                                class="relative h-10 hover:bg-primary-dark hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:font-bold hover:text-1xl 
                                p-2 rounded-xl text-lg text-primary-txt border-l-8 border flex items-center justify-between"
                                :class="{'border-primary-menu': route().current().startsWith('parametre.') || isSettingsOpen}"
                            >
                                <NavLink 
                                    :href="route('parametre.admin')" 
                                    :active="route().current().startsWith('parametre.')"
                                    class="hover:text-white text-md text-primary-txt h-10"
                                >
                                    <svg class="w-6 h-6 mr-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" fill="#ff7a18"/>
                                        <path d="M19.4 15a7.8 7.8 0 0 0 .1-1l2-1.5-2-3.5-2.3.6a7.5 7.5 0 0 0-1.7-1L15 6h-6l-.5 2.6a7.5 7.5 0 0 0-1.7 1L4.5 9l-2 3.5 2 1.5a7.8 7.8 0 0 0 .1 1L2.6 17l2 3.5 2.3-.6c.5.4 1.1.7 1.7 1L9 23h6l.5-2.6c.6-.3 1.2-.6 1.7-1l2.3.6 2-3.5-2.1-2z" fill="#ff7a18" opacity="0.9"/>
                                    </svg>
                                    Paramètres
                                </NavLink>
                                <button @click.stop="isSettingsOpen = !isSettingsOpen">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div v-show="isSettingsOpen" class="ml-5 mt-1 space-y-0.5">
                                <NavLink
                                    :href="route('parametre.admin')" :active="route().current('parametre.admin')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center justify-between"
                                >
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z" fill="#ff7a18"/>
                                        <path d="M4 20a8 8 0 0 1 16 0" fill="#ff7a18"/>
                                    </svg>
                                    Administrateur
                                </NavLink>

                                <NavLink :href="route('parametre.lotissement.create')" :active="route().current('parametre.lotissement.create')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <!-- Terrain principal (lotissement) -->
                                        <rect x="2" y="3" width="18" height="16" rx="2" fill="#ff7a18" />
                                        
                                        <!-- Ligne séparatrice horizontale (parcelles) -->
                                        <line x1="2" y1="10.5" x2="20" y2="10.5" stroke="white" stroke-width="1.5" />
                                        
                                        <!-- Ligne séparatrice verticale (parcelles) -->
                                        <line x1="11" y1="3" x2="11" y2="19" stroke="white" stroke-width="1.5" />
                                        
                                        <!-- Petites maisons sur les parcelles (optionnel) -->
                                        <path d="M5 8 L5 6 L7 6 L7 8" stroke="white" stroke-width="1.2" fill="none" />
                                        <path d="M14 8 L14 6 L16 6 L16 8" stroke="white" stroke-width="1.2" fill="none" />
                                        
                                        <!-- Symbole + (ajouter) en bas à droite -->
                                        <circle cx="21" cy="20" r="4.5" fill="#ff7a18" stroke="white" stroke-width="1.5" />
                                        <line x1="18.5" y1="20" x2="23.5" y2="20" stroke="white" stroke-width="2" />
                                        <line x1="21" y1="17.5" x2="21" y2="22.5" stroke="white" stroke-width="2" />
                                    </svg>
                                    Ajouter/Lotissements
                                </NavLink>
                                 
                                <NavLink 
                                    :href="route('parametre.lotissement.index')" :active="(route().current() || '') === 'parametre.lotissement.index'"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none">
                                        <rect x="4" y="5" width="16" height="3" fill="#6a11cb"/>
                                        <rect x="4" y="10.5" width="16" height="3" fill="#ff7a18"/>
                                        <rect x="4" y="16" width="16" height="3" fill="#ff7a18"/>
                                    </svg>
                                    Liste lotissements
                                </NavLink>
                                
                                <NavLink 
                                    :href="route('parametre.mutation')" :active="route().current('parametre.mutation')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >    
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 4h10v16H4z" fill="#ff7a18"/>
                                        <path d="M14 7h6v13h-6z" fill="#00c6ff"/>
                                        <path d="M7 8h4M7 12h4M7 16h4" stroke="white" stroke-width="1.5"/>
                                    </svg>
                                    Registre Mutations
                                </NavLink>

                                <NavLink 
                                    :href="route('parametre.chart')" :active="route().current('parametre.chart')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 20V4" stroke="#ff7a18" stroke-width="2"/>
                                        <path d="M4 20h16" stroke="#ff7a18" stroke-width="2"/>
                                        <path d="M7 15l3-4 3 2 4-6" stroke="#00c6ff" stroke-width="2" fill="none"/>
                                    </svg>
                                    Evalutation
                                </NavLink>
                                
                                <NavLink :href="route('parametre.pointDensifier.createPoint')" :active="route().current('parametre.pointDensifier.createPoint')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-4 mr-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <!-- Épingle / Marqueur principal -->
                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 6 12 7 13.25C13 21 19 14.25 19 9c0-3.87-3.13-7-7-7z" 
                                            fill="#ff7a18" />
                                        <circle cx="12" cy="9" r="3" fill="white" />
                                        
                                        <!-- Points secondaires (densification) -->
                                        <circle cx="4" cy="16" r="2.5" fill="#ff7a18" opacity="0.7" />
                                        <circle cx="4" cy="16" r="1.2" fill="white" />
                                        
                                        <circle cx="20" cy="16" r="2.5" fill="#ff7a18" opacity="0.7" />
                                        <circle cx="20" cy="16" r="1.2" fill="white" />
                                        
                                        <circle cx="7" cy="20" r="2" fill="#ff7a18" opacity="0.6" />
                                        <circle cx="17" cy="20" r="2" fill="#ff7a18" opacity="0.6" />
                                        
                                        <!-- Symbole + (ajouter) en bas à droite -->
                                        <circle cx="22" cy="22" r="3.5" fill="#ff7a18" stroke="white" stroke-width="1.5" />
                                        <line x1="19.5" y1="22" x2="24.5" y2="22" stroke="white" stroke-width="2" />
                                        <line x1="22" y1="19.5" x2="22" y2="24.5" stroke="white" stroke-width="2" />
                                    </svg>
                                    Ajouter/Points
                                </NavLink>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <footer class="bg-white border-t border-primary-only text-center text-sm text-primary-txt mt-auto py-4">
                <b>CENTRE DES SERVICES FISCAUX</b> CADASTRE KÉDOUGOU
            </footer>
        </nav>

        <!-- Page Content -->
        <div class="flex-1 md:ml-64 overflow-x-auto">
            <header class="bg-white shadow flex justify-between items-center p-4 relative">
                <div>
                    <slot name="header" />
                </div>

                <!-- User Dropdown -->
                <div class="relative">
                    <button 
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="flex items-center gap-3 px-4 py-2 bg-primary text-white rounded-xl shadow hover:bg-primary-dark transition"
                    >
                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-primary font-bold">
                            {{ user?.name?.charAt(0).toUpperCase() }}
                        </div>
                        <span class="font-medium">{{ user?.name }}</span>
                        <svg 
                            class="w-4 h-4 transition-transform duration-300"
                            :class="{ 'rotate-180': showingNavigationDropdown }"
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-1"
                    >
                        <div 
                            v-if="showingNavigationDropdown"
                            class="absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50"
                        >
                            <div class="px-4 py-3 bg-gray-50 border-b">
                                <p class="text-sm text-gray-500">Connecté en tant que</p>
                                <p class="font-semibold text-gray-800 truncate">{{ user?.name }}</p>
                            </div>

                            <div class="py-2">
                                <Link 
                                    :href="route('logout')" 
                                    method="post" 
                                    as="button"
                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V7" />
                                    </svg>
                                    Déconnexion
                                </Link>
                            </div>
                        </div>
                    </transition>
                </div>
            </header>

            <main class="p-6 overflow-x-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: max-height 1s ease-in-out, opacity 1s ease-in-out;
    overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
}

.slide-enter-to,
.slide-leave-from {
    max-height: 500px;
    opacity: 1;
}

.slideUser-enter-active,
.slideUser-leave-active {
    transition: max-height 1s ease-in-out, opacity 1s ease-in-out;
    overflow: hidden;
}

.slideUser-enter-from,
.slideUser-leave-to {
    max-height: 0;
    opacity: 0;
}

.slideUser-enter-to,
.slideUser-leave-from {
    max-height: 500px;
    opacity: 1;
}

.globe {
    animation: rotate 4s linear infinite;
    transform-origin: center;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.animate-pulse {
    animation: pulse 1.5s ease-in-out infinite;
}
</style>