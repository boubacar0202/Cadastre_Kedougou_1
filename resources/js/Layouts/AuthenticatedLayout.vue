<script setup>
import { ref, onMounted, onUnmounted, watch  } from 'vue';
import { usePage, Link } from '@inertiajs/vue3'; 
import NavLink from '@/Components/NavLink.vue';
import { useToast } from 'maz-ui'
// import Chat from '@/Components/Chat.vue'
  
// Récupérer l'utilisateur depuis les props Inertia
const page = usePage();
const user = page.props.auth?.user;
const isMenuOpen = ref(false);
const isDesktop = ref(false);
const showingNavigationDropdown = ref(false);
const unreadTotal = ref(0);

// Message alerte 
const toast = useToast() 
onMounted(() => {
  if (page.props.flash.success) {
    toast.success(page.props.flash.success)
  }
  if (page.props.flash.error) {
    toast.error(page.props.flash.error)
  }
}) 

// Surveille la largeur d'écran
function checkScreenSize() {
  isDesktop.value = window.innerWidth >= 768
  // Sur desktop, on rouvre le menu si c'était l'état précédent
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
  // Sauvegarde la préférence utilisateur
  localStorage.setItem('menuOpen', isMenuOpen.value.toString());
}

// Garde l’état du menu après rafraîchissement
onMounted(() => {
  checkScreenSize()
  window.addEventListener('resize', checkScreenSize) 
  
  // Récupère l'état sauvegardé ou utilise l'état par défaut
  const savedMenuState = localStorage.getItem('menuOpen');
  if (savedMenuState !== null) {
    isMenuOpen.value = savedMenuState === 'true';
  } else {
    // Par défaut : ouvert sur desktop, fermé sur mobile
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

// Charger le total des messages non lus au montage
onMounted(async () => {
  try {
    const res = await axios.get("/messages/unread-total");
    unreadTotal.value = res.data.total;
  } catch (e) {
    console.error("Erreur fetch unread:", e);
  }
});
  
</script>

<template>
    <div class="flex min-h-screen bg-primary-layout">
        <!-- Sidebar -->
        <nav class="w-64 h-screen bg-white border-r border-primary-only fixed flex flex-col p-4 overflow-y-auto">
            <!-- Logo -->
            <div class="flex items-center justify-center mb-6">
                <Link :href="route('dashboard')">
                    <!-- <logo class="h-10 w-auto fill-current text-gray-800" /> -->
                    <img src="/logo.jpg" alt="Logo" class="h-100 w-auto">
                </Link>
            </div>

            <!-- Navigation Links -->
            <div class="flex flex-col space-y-4">
                <!-- Bouton d'ouverture/fermeture -->
                <button @click="toggleMenu" class="p-2 bg-primary text-white rounded shadow-md">
                    ☰ Menu
                </button>
                <transition name="slide">
                    
                        <nav v-if="isMenuOpen || !isDesktop" class="flex flex-col space-y-4" >
                            <!-- Navigation Links -->
                            <div class="flex flex-col space-y-4">
                              
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('dashboard')}">
                                    <svg class="hover:white w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#6d3500">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    Accueil
                                </NavLink>
                                <NavLink :href="route('secretariat.create')" :active="route().current('secretariat.create')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('secretariat.create')}">
                                    <svg class="hover:white w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#6d3500">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 12v3m0 0l-2-2m2 2l2-2" />
                                    </svg>
                                    Enregistrer Dossier
                                </NavLink>
                                <NavLink :href="route('geometre.create')" :active="route().current('geometre.create')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('geometre.create')}">
                                    <svg class="w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        <circle cx="11" cy="11" r="3" stroke-width="2" />
                                    </svg>
                                    Enquête Cadastrale
                                </NavLink>
                                <NavLink :href="route('matriceCadastrale.create')" :active="route().current('matriceCadastrale.create')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('matriceCadastrale.create')}">
                                    <svg class="w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 6v12m4-12v12m4-12v12" />
                                    </svg>
                                    Matrice Cadastrale
                                </NavLink>
                                <NavLink :href="route('donnee.create')" :active="route().current('donnee.create')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('donnee.create')}">
                                    <svg class="w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                    Base de Données
                                </NavLink> 
                                <NavLink :href="route('mutation.create')" :active="route().current('mutation.create')" 
                                    class="hover:bg-primary-dark hover:text-white hover:text-1xl hover:font-bold p-2 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('mutation.create')}">
                                    <svg class="w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 8l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Mutations
                                </NavLink>
                                <NavLink :href="route('message.create')" :active="route().current('message.create')" 
                                    class="relative hover:bg-primary-dark hover:text-white hover:font-bold hover:text-1xl p-2 
                                    rounded text-lg text-primary-txt border border-l-8 flex items-center m" 
                                    :class="{'border-primary-menu': route().current('message.create')}">
                                    <svg class="w-6 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-3 12H7v-2h10v2zm0-3H7V9h10v2zm0-3H7V6h10v2z"/>
                                    </svg>
                                    Discussion
                                    <span v-if="unreadTotal > 0"
                                            class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-green-500 text-white rounded-full">
                                        {{ unreadTotal }}
                                    </span>
                                </NavLink> 
                            </div>
                        </nav> 
                </transition>
            </div>
            <footer class="bg-white border-t border-primary-only text-center text-sm text-primary-txt mt-auto py-4">
                <b>CENTRE DES SERVICES FISCAUX</b> CADASTRE KÉDOUGOU
            </footer>
 
        </nav>

        <!-- Page Content -->
        <div class="flex-1 md:ml-64 overflow-x-auto">
            <header class="bg-white shadow flex justify-between items-center p-4 relative">
                <!-- Titre ou Header (si présent) -->
                <div>
                    <slot name="header" />
                </div>

                <!-- User Dropdown (En haut à droite) -->
      
                    <div class="relative">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="flex items-center space-x-2 px-4 py-2 bg-primary rounded-md hover:bg-primary-dark">
                            <span class="text-white font-semibold">{{ user?.name }}</span>
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <transition name="slideUser">
                            <div v-if="showingNavigationDropdown" class="absolute right-0 mt-2 w-48 bg-white border border-primary-dark rounded shadow-md z-10">
                                <div class="px-4 py-2 text-gray-800">{{ user?.name }}</div>
                                <hr>
                                <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-primary hover:bg-primary-dark">
                                    Déconnexion
                                </Link>
                            </div>
                        </transition>
                    </div> 
            </header>

            <!-- Contenu de la page -->
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
  
    /* slideUser */
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
</style>
