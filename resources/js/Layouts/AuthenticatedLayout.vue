<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3'; 
import NavLink from '@/Components/NavLink.vue';
import { toast } from "@/Components/AppToast"; 
import axios from 'axios';
import { route } from 'ziggy-js'

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.role === 'admin');
const isMenuOpen = ref((() => {
    if (typeof window === 'undefined' || window.innerWidth < 768) return false;
    const saved = localStorage.getItem('menuOpen');
    return saved === null ? true : saved === 'true';
})());
const isDesktop = ref(typeof window !== 'undefined' && window.innerWidth >= 768);
const isSmallScreen = computed(() => !isDesktop.value);
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

if (isAdmin.value && route().current('parametre.*')) {
    isSettingsOpen.value = true;
}

onMounted(() => {
    if (page.props.flash?.success) {
        toast.success(page.props.flash.success)
    }
    if (page.props.flash?.error) {
        toast.error(page.props.flash.error)
    }

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
    const desktop = window.innerWidth >= 768
    const wasDesktop = isDesktop.value
    isDesktop.value = desktop

    if (desktop && !wasDesktop) {
        const savedMenuState = localStorage.getItem('menuOpen')
        isMenuOpen.value = savedMenuState === null ? true : savedMenuState === 'true'
    } else if (!desktop && wasDesktop) {
        isMenuOpen.value = false
    }
}

// Toggle menu avec sauvegarde
function toggleMenu() {
    isMenuOpen.value = !isMenuOpen.value;
    localStorage.setItem('menuOpen', isMenuOpen.value.toString());
}

function onNavClick(event) {
    if (!isSmallScreen.value) return
    if (event.target.closest('a')) {
        isMenuOpen.value = false
    }
}

// Garde l’état du menu après rafraîchissement
onMounted(() => {
    isDesktop.value = window.innerWidth >= 768
    if (isDesktop.value) {
        const savedMenuState = localStorage.getItem('menuOpen');
        isMenuOpen.value = savedMenuState === null ? true : savedMenuState === 'true';
    } else {
        isMenuOpen.value = false
    }
    window.addEventListener('resize', checkScreenSize)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize)
})

</script>

<template>
    <div class="app-shell flex min-h-screen w-full bg-primary-layout" :class="{ 'is-menu-open': isMenuOpen }">
        <Head>
            <meta name="csrf-token" :content="page.props.csrf_token">
        </Head>
        <div v-if="isMenuOpen && isSmallScreen" class="app-overlay" @click="isMenuOpen = false"></div>
        <nav class="app-sidebar" :class="{ 'is-open': isMenuOpen }" @click="onNavClick">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-6">
                <Link :href="route('dashboard')">
                    <img src="/logo.jpg" alt="Logo" class="h-20 w-auto rounded-xl object-contain">
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
                            flex items-center gap-2 text-lg justify-center
                            overflow-x-hidden "
                    >
                        ☰ Menu
                    </button> 
                
                <transition name="slide">
                    <div v-if="isMenuOpen || !isDesktop" class="flex flex-col space-y-1  rounded-xl shadow-md">
                        <!-- Navigation Links -->
                        <div class="flex flex-col space-y-1"> 
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('dashboard')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" fill="#ff7a18"/>
                                </svg>
                                Accueil
                            </NavLink>
                            
                            <NavLink :href="route('secretariat.create')" :active="route().current('secretariat.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('secretariat.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h7l2 2h7v10a2 2 0 0 1-2 2H4V6z" fill="#ff7a18"/>
                                    <path d="M12 11v6m-3-3h6" stroke="white" stroke-width="2"/>
                                </svg>
                                Enregistrer Dossier
                            </NavLink>
 
                            <NavLink :href="route('arrivee.create')" :active="route().current('arrivee.create') || route().current('arrivee.edit')"
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('arrivee.create') || route().current('arrivee.edit')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z" fill="#ff7a18"/>
                                    <path d="M22 6l-10 7L2 6" stroke="white" stroke-width="1.6"/>
                                </svg>
                                Enregistrement Arrivées
                            </NavLink>
  
                            <NavLink :href="route('depart.create')" :active="route().current('depart.create') || route().current('depart.edit')"
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('depart.create') || route().current('depart.edit')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 4h16a2 2 0 012 2v9l-5 5H4a2 2 0 01-2-2V6a2 2 0 012-2z" fill="#ff7a18"/>
                                    <path d="M17 15v5l5-5h-5z" fill="#613104"/>
                                    <path d="M7 12h7m0 0-2.2-2.2M14 12l-2.2 2.2" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Enregistrement Départs
                            </NavLink> 
                            
                            <NavLink :href="route('geometre.create')" :active="route().current('geometre.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('geometre.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 21s7-4.5 7-11a7 7 0 10-14 0c0 6.5 7 11 7 11z" fill="#ff7a18"/>
                                    <circle cx="12" cy="10" r="2" fill="white"/>
                                </svg>
                                Enquête Cadastrale
                            </NavLink>
                            
                            <NavLink :href="route('matriceCadastrale.create')" :active="route().current('matriceCadastrale.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('matriceCadastrale.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="4" width="7" height="7" fill="#6a11cb"/>
                                    <rect x="14" y="4" width="7" height="7" fill="#ff7a18"/>
                                    <rect x="3" y="13" width="7" height="7" fill="#ff7a18"/>
                                    <rect x="14" y="13" width="7" height="7" fill="#6a11cb"/>
                                </svg>
                                Matrice Cadastrale
                            </NavLink>
                         
                            <NavLink :href="route('mutation.create')" :active="route().current('mutation.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('mutation.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M7 7h11l-3-3m3 3l-3 3" stroke="#ff512f" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M17 17H6l3 3m-3-3l3-3" stroke="#dd2476" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                Mutations
                            </NavLink>
 
                            <NavLink
                                    :href="route('lotissement.index')" :active="(route().current() || '') === 'lotissement.index'"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="5" width="16" height="3" fill="#6a11cb"/>
                                    <rect x="4" y="10.5" width="16" height="3" fill="#ff7a18"/>
                                    <rect x="4" y="16" width="16" height="3" fill="#ff7a18"/>
                                </svg>
                                Liste lotissements
                            </NavLink>
 
                            <NavLink 
                                    :href="route('mutation.index')" :active="route().current('mutation.index')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >    
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 4h10v16H4z" fill="#ff7a18"/>
                                    <path d="M14 7h6v13h-6z" fill="#00c6ff"/>
                                    <path d="M7 8h4M7 12h4M7 16h4" stroke="white" stroke-width="1.5"/>
                                </svg>
                                Registre Mutations
                            </NavLink>
                             
                            <NavLink :href="route('instance.create')" :active="route().current('instance.create')"
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('instance.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="4" width="18" height="16" rx="2" fill="#ff7a18"/>
                                    <path d="M7 9h10M7 13h10M7 17h6" stroke="white" stroke-width="1.6"/>
                                </svg>
                                Courriers Instances
                            </NavLink>

                            <NavLink :href="route('instancearrivee.create')" :active="route().current('instancearrivee.create')"
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('instancearrivee.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h16v12H4z" fill="#ff7a18"/>
                                    <path d="M4 6l8 6 8-6" stroke="white" stroke-width="1.6"/>
                                </svg>
                                Base Courriers Arrivées
                            </NavLink>

                            <NavLink :href="route('instancedepart.create')" :active="route().current('instancedepart.create')"
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('instancedepart.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h16v12H4z" fill="#ff7a18"/>
                                    <path d="M12 16V8M9 11l3-3 3 3" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Base Courriers Départs
                            </NavLink>
                            
                            <NavLink :href="route('donnee.create')" :active="route().current('donnee.create')" 
                                class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('donnee.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                    <ellipse cx="12" cy="5" rx="8" ry="3" fill="#ff7a18"/>
                                    <path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5" fill="#4facfe"/>
                                    <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" fill="#ff7a18"/>
                                </svg>
                                Base de Données
                            </NavLink>
                            
                            <NavLink :href="route('plan.create')" :active="route().current('plan.create')" 
                                class="relative hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2 
                                rounded-xl text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('plan.create')}">
                                <svg class="w-6 h-6 mr-4 shrink-0 globe" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                            
                            <!-- Paramètres : visible uniquement pour les administrateurs -->
                            <template v-if="isAdmin">
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
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <path d="M19.4 15a7.8 7.8 0 0 0 .1-1l2-1.5-2-3.5-2.3.6a7.5 7.5 0 0 0-1.7-1L15 6h-6l-.5 2.6a7.5 7.5 0 0 0-1.7 1L4.5 9l-2 3.5 2 1.5a7.8 7.8 0 0 0 .1 1L2.6 17l2 3.5 2.3-.6c.5.4 1.1.7 1.7 1L9 23h6l.5-2.6c.6-.3 1.2-.6 1.7-1l2.3.6 2-3.5-2.1-2z" fill="#ff7a18"/>
                                        <circle cx="12" cy="12" r="3.2" fill="white"/>
                                    </svg>
                                    Paramètres
                                </NavLink>
                                <button @click.stop="isSettingsOpen = !isSettingsOpen" aria-label="Ouvrir les paramètres">
                                    <svg class="w-6 h-6 transition-transform duration-200" :class="{ 'rotate-90': isSettingsOpen }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <div v-show="isSettingsOpen" class="ml-5 mt-1 space-y-0.5">
                                <NavLink
                                    :href="route('parametre.admin')" :active="route().current('parametre.admin')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center justify-between"
                                >
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z" fill="#ff7a18"/>
                                        <path d="M4 20a8 8 0 0 1 16 0" fill="#ff7a18"/>
                                        <path d="M16.8 13.2 19.4 14.1v2.1c0 1.4-1 2.4-2.6 3-1.6-.6-2.6-1.6-2.6-3v-2.1l2.6-.9z" fill="#613104"/>
                                        <path d="M15.9 16.1 16.8 17 18.4 15.4" stroke="white" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Administrateur
                                </NavLink>

                                <NavLink
                                    :href="route('parametre.user')" :active="route().current('parametre.user')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center justify-between"
                                >
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <circle cx="16.2" cy="9" r="2.5" fill="#613104"/>
                                        <path d="M12.4 19.6c.2-2.4 1.5-4 3.8-4s3.6 1.6 3.8 4z" fill="#613104"/>
                                        <circle cx="9" cy="8.4" r="3.1" fill="#ff7a18"/>
                                        <path d="M3.4 20c.3-3.2 2.5-5.4 5.6-5.4s5.3 2.2 5.6 5.4z" fill="#ff7a18"/>
                                    </svg>
                                    Utilisateurs
                                </NavLink>

                                <NavLink :href="route('parametre.lotissement.create')" :active="route().current('parametre.lotissement.create')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="2.5" width="15" height="13.5" rx="2" fill="#ff7a18" />
                                        <line x1="2" y1="9.2" x2="17" y2="9.2" stroke="white" stroke-width="1.4" />
                                        <line x1="9.5" y1="2.5" x2="9.5" y2="16" stroke="white" stroke-width="1.4" />
                                        <path d="M5 6.6V5.2h1.5v1.4" stroke="white" stroke-width="1.1" fill="none" />
                                        <path d="M12.2 6.6V5.2h1.5v1.4" stroke="white" stroke-width="1.1" fill="none" />
                                        <circle cx="18.6" cy="18.6" r="3.6" fill="#ff7a18" stroke="white" stroke-width="1.3" />
                                        <line x1="16.8" y1="18.6" x2="20.4" y2="18.6" stroke="white" stroke-width="1.6" />
                                        <line x1="18.6" y1="16.8" x2="18.6" y2="20.4" stroke="white" stroke-width="1.6" />
                                    </svg>
                                    Ajouter/Lotissements
                                </NavLink>
 
                                <NavLink :href="route('parametre.pointDensifier.createPoint')" :active="route().current('parametre.pointDensifier.createPoint')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.5 2.2C7.9 2.2 5.8 4.3 5.8 6.9c0 3.6 3.8 8.2 4.7 9.1.9-.9 4.7-5.5 4.7-9.1 0-2.6-2.1-4.7-4.7-4.7z" fill="#ff7a18" />
                                        <circle cx="10.5" cy="6.9" r="1.8" fill="white" />
                                        <circle cx="4.2" cy="14.2" r="1.6" fill="#ff7a18" opacity="0.7" />
                                        <circle cx="4.2" cy="14.2" r="0.7" fill="white" />
                                        <circle cx="15.4" cy="13.6" r="1.6" fill="#ff7a18" opacity="0.7" />
                                        <circle cx="15.4" cy="13.6" r="0.7" fill="white" />
                                        <circle cx="6.6" cy="17.6" r="1.3" fill="#ff7a18" opacity="0.6" />
                                        <circle cx="12.6" cy="17.8" r="1.3" fill="#ff7a18" opacity="0.6" />
                                        <circle cx="18.7" cy="18.7" r="3.5" fill="#ff7a18" stroke="white" stroke-width="1.3" />
                                        <line x1="16.9" y1="18.7" x2="20.5" y2="18.7" stroke="white" stroke-width="1.5" />
                                        <line x1="18.7" y1="16.9" x2="18.7" y2="20.5" stroke="white" stroke-width="1.5" />
                                    </svg>
                                    Ajouter/Points
                                </NavLink>
 
                                <NavLink :href="route('parametre.service.create')"
                                    :active="route().current('parametre.service.create') || route().current('parametre.service.edit')"
                                    class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                    hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                    :class="{'border-primary-menu': route().current('parametre.service.create') || route().current('parametre.service.edit')}">
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <path d="M6 3h8l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" fill="#ff7a18"/>
                                        <path d="M14 3v5h5M8 13h8M8 17h6" stroke="white" stroke-width="1.4"/>
                                    </svg>
                                    Recueil de texte
                                </NavLink>

                                <NavLink :href="route('parametre.trimestre.create')"
                                    :active="route().current('parametre.trimestre.create')"
                                    class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                    hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                    :class="{'border-primary-menu': route().current('parametre.trimestre.create')}">
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="5" width="18" height="16" rx="2" fill="#ff7a18"/>
                                        <path d="M3 10h18M8 3v4M16 3v4" stroke="white" stroke-width="1.6"/>
                                    </svg>
                                    Trimestres
                                </NavLink>

                                <NavLink :href="route('parametre.bareme.index')"
                                    :active="route().current('parametre.bareme.index')"
                                    class="hover:from-primary hover:to-primary-dark hover:bg-gradient-to-r
                                    hover:shadow-lg hover:scale-[1.02] hover:text-white hover:text-1xl hover:font-bold p-2
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                    :class="{'border-primary-menu': route().current('parametre.bareme.index')}">
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 4v14" stroke="#ff7a18" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M8 20h8" stroke="#ff7a18" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4.5 8h15" stroke="#ff7a18" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4.5 8 2 14h5L4.5 8z" fill="#ff7a18"/>
                                        <path d="M19.5 8 17 14h5l-2.5-6z" fill="#ff7a18"/>
                                    </svg>
                                    Barèmes
                                </NavLink> 


                                <NavLink 
                                    :href="route('parametre.chart')" :active="route().current('parametre.chart')"
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded-xl text-lg text-primary-txt border-l-8 border flex items-center"
                                >
                                    <svg class="w-6 h-6 mr-4 shrink-0" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 20V4" stroke="#ff7a18" stroke-width="2"/>
                                        <path d="M4 20h16" stroke="#ff7a18" stroke-width="2"/>
                                        <path d="M7 15l3-4 3 2 4-6" stroke="#00c6ff" stroke-width="2" fill="none"/>
                                    </svg>
                                    Evalutation
                                </NavLink> 

                            </div>
                            </template>
                        </div>
                    </div>
                </transition>
            </div>

            <footer class="bg-primary-form/60 border-t border-primary-only/80 text-center text-xs tracking-wide text-primary-txt mt-auto py-4 rounded-xl">
                <b>CENTRE DES SERVICES FISCAUX</b><br>CADASTRE KÉDOUGOU
            </footer>
        </nav>

        <!-- Page Content -->
        <div class="flex min-h-screen w-full flex-col md:pl-64">
            <header class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-3 border-b border-primary-only/50 bg-white/90 p-4 shadow-sm backdrop-blur-md">
                <button
                    v-if="isSmallScreen"
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-lg text-white shadow"
                    aria-label="Ouvrir le menu"
                    @click="toggleMenu"
                >
                    ☰
                </button>
                <div class="min-w-0 flex-1">
                    <slot name="header" />
                </div>

                <!-- User Dropdown -->
                <div class="relative shrink-0">
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
                                <p class="text-sm text-primary-txt">Connecté en tant que</p>
                                <p class="font-semibold text-primary-txt truncate">{{ user?.name }}</p>
                            </div>

                            <div class="py-2">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-primary-txt hover:bg-primary-form transition"
                                >
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="8" r="3.2" stroke-width="1.8"/>
                                        <path d="M5 19.5c.8-3.2 3.2-5 7-5s6.2 1.8 7 5" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                    Profil
                                </Link>
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

            <main class="w-full flex-1 bg-primary-layout p-4 md:p-6">
                <slot />
            </main>
        </div>

        <div v-if="!route().current('message.create')" class="fixed bottom-6 right-6 z-[2000]">
            <Link
                :href="route('message.create')"
                class="relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-lg transition duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white/70"
                aria-label="Discussion"
            >
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none">
                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8z" fill="currentColor"/>
                </svg>
                <span
                    v-if="unreadTotal > 0"
                    class="absolute -top-1 -right-1 inline-flex min-w-[20px] h-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white shadow-lg"
                >
                    {{ unreadTotal }}
                </span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.app-overlay {
    position: fixed;
    inset: 0;
    z-index: 40;
    background: rgba(97, 49, 4, 0.45);
}

.app-sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 50;
    display: flex;
    width: 16rem;
    height: 100vh;
    flex-direction: column;
    overflow-y: auto;
    padding: 1rem;
    transition: transform 0.28s ease;
}

@media (max-width: 767px) {
    .app-sidebar {
        transform: translateX(-110%);
    }

    .app-sidebar.is-open {
        transform: translateX(0);
    }
}

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