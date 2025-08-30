<script setup>
import { ref, onMounted, onUnmounted, watch  } from 'vue';
import { usePage, Link } from '@inertiajs/vue3'; 
import NavLink from '@/Components/NavLink.vue';
import { useToast } from 'maz-ui'
// import Chat from '@/Components/Chat.vue'
  
// Récupérer l'utilisateur depuis les props Inertia
const page = usePage();
const user = page.props.auth?.user;
const isMenuOpen = ref(false)
const isDesktop = ref(false)
const showingNavigationDropdown = ref(false)

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
}

// Toggle menu (mobile uniquement)
function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}

// Garde l’état du menu entre pages, mais pas après F5
onMounted(() => {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize) 
        if (window.__menuOpenTemp === true) {
            isMenuOpen.value = true
    } 
    window.__menuOpenTemp = false
}) 

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize)
})

// Mémoriser l'état temporairement pour navigation Inertia
watch(isMenuOpen, (val) => {
  window.__menuOpenTemp = val
})


// Liens du menu
const menuItems = [
  { label: 'Dashboard', href: route('dashboard'), active: 'dashboard' },
  { label: 'Secretariat', href: route('secretariat.create'), active: 'secretariat.create' },
  { label: 'Enquête Cadastrale', href: route('geometre.create'), active: 'geometre.create' },
  { label: 'Matrice Cadastrale', href: route('matriceCadastrale.create'), active: 'matriceCadastrale.create' },
  { label: 'Base de Données', href: route('donnee.create'), active: 'donnee.create' }
]


</script>

<template>
    <div class="flex min-h-screen bg-primary-layout">
        <!-- Sidebar -->
        <nav class="w-64 h-screen bg-white border-r border-primary-only fixed flex flex-col p-4">
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
                                    class="hover:bg-primary-menu hover:text-primary hover:text-lg hover:font-bold p-3 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('dashboard')}">
                                    <svg class="w-5 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10l9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10z" />
                                    </svg>
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('secretariat.create')" :active="route().current('secretariat.create')" 
                                    class="hover:bg-primary-menu hover:text-primary hover:text-lg hover:font-bold p-3 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('secretariat.create')}">
                                    <svg class="w-5 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 7V3m8 4V3m-9 14h10m-5-5a3 3 0 0 1-6 0m12 5V10a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8" />
                                    </svg>
                                    Secretariat
                                </NavLink>
                                <NavLink :href="route('geometre.create')" :active="route().current('geometre.create')" 
                                    class="hover:bg-primary-menu hover:text-primary hover:text-lg hover:font-bold p-3 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('geometre.create')}">
                                    <svg class="w-5 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 3v18m9-9H3" />
                                    </svg>
                                    Enquête Cadastrale
                                </NavLink>
                                <NavLink :href="route('matriceCadastrale.create')" :active="route().current('matriceCadastrale.create')" 
                                    class="hover:bg-primary-menu hover:text-primary hover:text-lg hover:font-bold p-3 
                                    rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('matriceCadastrale.create')}">
                                    <svg class="w-5 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 6h16M4 12h16m-7 6h7" />
                                    </svg>
                                    Matrice Cadastrale
                                </NavLink>
                                <NavLink :href="route('donnee.create')" :active="route().current('donnee.create')" 
                                    class="hover:bg-primary-menu hover:text-primary hover:text-lg hover:font-bold p-3 rounded text-lg text-primary-txt border-l-8 border flex items-center" :class="{'border-primary-menu': route().current('donnee.create')}">
                                    <svg class="w-5 h-6 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#5f2e01">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4z" />
                                    </svg>
                                    Base de Données
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
        <div class="ml-64 flex-1">
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
            <main class="p-6">
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
