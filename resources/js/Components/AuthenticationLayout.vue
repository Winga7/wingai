<script setup>
import { Head } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

defineProps({
  title: String,
});
</script>

<template>
  <Head :title="title" />

  <div
    class="min-h-screen bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800"
  >
    <div class="flex flex-col min-h-screen">
      <!-- Logo -->
      <div class="flex justify-center pt-6 sm:pt-0">
        <ApplicationLogo class="h-8 w-auto sm:h-10" />
      </div>

      <!-- Image d'accueil -->
      <div class="flex justify-center mt-6">
        <img
          src="/logo/Image_Accueil.jpg"
          alt="Image d'accueil"
          class="w-full max-w-md rounded-lg shadow-xl"
        />
      </div>

      <!-- Contenu -->
      <div
        class="flex flex-col sm:justify-center items-center flex-grow pt-6 sm:pt-0"
      >
        <div
          class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-md overflow-hidden sm:rounded-lg"
        >
          <slot></slot>
        </div>
      </div>
    </div>
  </div>
</template>
