<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePage } from "@inertiajs/vue3";

const user = usePage().props.auth.user;
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header>
      <h2
        class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
      >
        Tableau de bord
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
          class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8"
        >
          <div class="text-center">
            <h1
              class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4"
            >
              Bienvenue, {{ user.name }} ! 👋
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mb-8">
              Nous sommes ravis de vous revoir sur votre espace personnel.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div
              class="p-6 bg-gradient-to-br from-purple-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-lg shadow-sm"
            >
              <h3
                class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2"
              >
                Commencer rapidement
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Explorez votre tableau de bord et découvrez toutes les
                fonctionnalités disponibles.
              </p>
            </div>

            <div
              class="p-6 bg-gradient-to-br from-blue-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-lg shadow-sm"
            >
              <h3
                class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2"
              >
                Besoin d'aide ?
              </h3>
              <p class="text-gray-600 dark:text-gray-400">
                Notre équipe est là pour vous accompagner dans toutes vos
                démarches.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
