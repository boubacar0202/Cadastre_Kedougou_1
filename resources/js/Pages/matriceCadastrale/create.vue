<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Matrice"/>

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Matrice Cadastrale
            </h2>
           
        </template>
     
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-8 lg:px-12">
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <div class="container">
                        <div class="card">
                            <div class="card-header">
                                <div class="p-4 border-b bg-gray-100">
                                    <h1 class="text-lg font-semibold">Matrice Cadastrale</h1>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-strictped table-bordered">
                                    <thead >
                                        <tr>
                                            <th scope="col" class="px-6 py-3">
                                                N°
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Region
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Departement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Commune
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Village/Quartier
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Rue
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Section
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Lotissement
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Numéro Lot
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Numéro_Parcelle
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                NICAD
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Numéro_Prote
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Superfice Legale
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Superficie Batie
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Type_Batie
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Niveau
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Superfice_NBatie
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Type_Designation
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Cat_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Source_Donnee
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Titre Mere
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Titre Parcelle
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Type_DocFoncier
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Nature_Juridique
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Prenom_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Nom_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                CIN_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Passport_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                DateDelivrance_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Date_Naissance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Lieu_Naissance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                NINEA_PRO
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Telephone1_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Telephone2_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Email_Pro
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Prenom_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Nom_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                CIN_Ocupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Passeport_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Date_Delivrance
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                DateNaiss_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                LieuNaiss_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                NINEA_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Tel_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Email_Occupant
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Valeur_Locative
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Observations
                                            </th>
                                            <th scope="col" class="px-6 py-3">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                N°
                                            </th>
                                            <td class="px-6 py-4">
                                                Region
                                            </td>
                                            <td class="px-6 py-4">
                                                Departement
                                            </td>
                                            <td class="px-6 py-4">
                                                Commune
                                            </td>
                                            <td class="px-6 py-4">
                                                Village/Quartier
                                            </td>
                                            <td class="px-6 py-4">
                                                Rue
                                            </td>
                                            <td class="px-6 py-4">
                                                Section
                                            </td>
                                            <td class="px-6 py-4">
                                                Lotissement
                                            </td>
                                            <td class="px-6 py-4">
                                                Numéro Lot
                                            </td>
                                            <td class="px-6 py-4">
                                                Numéro_Parcelle
                                            </td>
                                            <td class="px-6 py-4">
                                                NICAD
                                            </td>
                                            <td class="px-6 py-4">
                                                Numéro_Prote
                                            </td>
                                            <td class="px-6 py-4">
                                                Superfice Legale
                                            </td>
                                            <td class="px-6 py-4">
                                                Superficie Batie
                                            </td>
                                            <td class="px-6 py-4">
                                                Type_Batie
                                            </td>
                                            <td class="px-6 py-4">
                                                Niveau
                                            </td>
                                            <td class="px-6 py-4">
                                                Superfice_NBatie
                                            </td>
                                            <td class="px-6 py-4">
                                                Type_Designation
                                            </td>
                                            <td class="px-6 py-4">
                                                Cat_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Source_Donnee
                                            </td>
                                            <td class="px-6 py-4">
                                                Titre Mere
                                            </td>
                                            <td class="px-6 py-4">
                                                Titre Parcelle
                                            </td>
                                            <td class="px-6 py-4">
                                                Type_DocFoncier
                                            </td>
                                            <td class="px-6 py-4">
                                                Nature_Juridique
                                            </td>
                                            <td class="px-6 py-4">
                                                Prenom_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Nom_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                CIN_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Passport_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                DateDelivrance_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Date_Naissance
                                            </td>
                                            <td class="px-6 py-4">
                                                Lieu_Naissance
                                            </td>
                                            <td class="px-6 py-4">
                                                NINEA_PRO
                                            </td>
                                            <td class="px-6 py-4">
                                                Telephone1_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Telephone2_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Email_Pro
                                            </td>
                                            <td class="px-6 py-4">
                                                Prenom_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Nom_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                CIN_Ocupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Passeport_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Date_Delivrance
                                            </td>
                                            <td class="px-6 py-4">
                                                DateNaiss_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                LieuNaiss_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                NINEA_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Tel_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Email_Occupant
                                            </td>
                                            <td class="px-6 py-4">
                                                Valeur_Locative
                                            </td>
                                            <td class="px-6 py-4">
                                                Observations
                                            </td>
                                            <td class="flex items px-6 py-6">
                                                <div class="mt-2">
                                                    <MazBtn pastel size="sm">Modifier</MazBtn>
                                                </div>
                                                <div class="container">
                                                    <p>.</p>
                                                </div>
                                                <div class="mt-2">
                                                    <MazBtn color="danger" pastel size="sm">Supprimer</MazBtn>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                
                                            </th>
                                            <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                               <!--{{$item->region}}    -->
                                               début
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                              
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                          
                                            </td>
                                            <td class="px-6 py-4">
                                            
                                            </td>
                                            <td class="px-6 py-4">
                                              
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                              
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                              
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                            
                                            </td>
                                            <td class="px-6 py-4">
                                               
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                
                                            </td>
                                            <td class="px-6 py-4">
                                                Fin_debut
                                            </td>
                                            <td class="flex items px-6 py-6">
                                                <div class="mt-2">
                                                    <MazBtn pastel size="sm">Modifier</MazBtn>
                                                </div>
                                                <div class="container">
                                                    <p>.</p>
                                                </div>
                                                <div class="mt-2">
                                                    <MazBtn color="danger" pastel size="sm">Supprimer</MazBtn>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
