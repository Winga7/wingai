<script setup>
import { Head, Link } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
});

function handleImageError() {
  document.getElementById("screenshot-container")?.classList.add("!hidden");
  document.getElementById("docs-card")?.classList.add("!row-span-1");
  document.getElementById("docs-card-content")?.classList.add("!flex-row");
  document.getElementById("background")?.classList.add("!hidden");
}
</script>

<template>
  <Head title="Bienvenue" />

  <div
    class="min-h-screen bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800"
  >
    <!-- Navigation -->
    <nav class="relative px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center flex-shrink-0">
          <ApplicationLogo class="h-8 w-auto sm:h-10" />
        </div>
        <div v-if="canLogin" class="flex items-center gap-2 sm:gap-4">
          <Link
            v-if="$page.props.auth.user"
            :href="route('dashboard')"
            class="text-sm sm:text-base text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition"
          >
            Dashboard
          </Link>
          <template v-else>
            <Link
              :href="route('login')"
              class="text-sm sm:text-base text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition"
            >
              Connexion
            </Link>
            <Link
              v-if="canRegister"
              :href="route('register')"
              class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition"
            >
              Inscription gratuite
            </Link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative isolate px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl py-12 sm:py-24 lg:py-32">
        <div class="text-center px-4">
          <h1
            class="text-3xl sm:text-4xl lg:text-6xl font-bold tracking-tight text-gray-900 dark:text-white"
          >
            Discutez avec une IA intelligente et personnalisée
          </h1>
          <p
            class="mt-4 sm:mt-6 text-base sm:text-lg leading-8 text-gray-600 dark:text-gray-300 max-w-3xl mx-auto"
          >
            Découvrez une nouvelle façon d'interagir avec l'intelligence
            artificielle. Notre assistant IA s'adapte à vos besoins et vous aide
            dans vos tâches quotidiennes.
          </p>
          <div
            class="mt-6 sm:mt-10 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-x-6"
          >
            <Link
              v-if="canRegister"
              :href="route('register')"
              class="w-full sm:w-auto rounded-md bg-indigo-600 px-4 sm:px-6 py-2.5 sm:py-3 text-sm sm:text-lg font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Commencer gratuitement
            </Link>
            <Link
              :href="route('login')"
              class="w-full sm:w-auto text-sm sm:text-lg font-semibold leading-6 text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400"
            >
              Se connecter <span aria-hidden="true">→</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="py-12 sm:py-24">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
          <h2
            class="text-base font-semibold leading-7 text-indigo-600 dark:text-indigo-400"
          >
            Plus intelligent que jamais
          </h2>
          <p
            class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl"
          >
            Tout ce dont vous avez besoin pour échanger avec l'IA
          </p>
        </div>
        <div class="mx-auto mt-12 sm:mt-16 lg:mt-20">
          <dl
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8"
          >
            <!-- Feature 1 -->
            <div
              class="flex flex-col rounded-lg bg-white/5 p-6 backdrop-blur-sm hover:bg-white/10 transition duration-300"
            >
              <dt
                class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"
                  />
                </svg>
                Conversations naturelles
              </dt>
              <dd
                class="mt-4 flex flex-auto flex-col text-sm sm:text-base leading-7 text-gray-600 dark:text-gray-300"
              >
                <p class="flex-auto">
                  Échangez naturellement avec notre IA comme si vous discutiez
                  avec un véritable assistant.
                </p>
              </dd>
            </div>

            <!-- Feature 2 -->
            <div
              class="flex flex-col rounded-lg bg-white/5 p-6 backdrop-blur-sm hover:bg-white/10 transition duration-300"
            >
              <dt
                class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"
                  />
                </svg>
                Personnalisation avancée
              </dt>
              <dd
                class="mt-4 flex flex-auto flex-col text-sm sm:text-base leading-7 text-gray-600 dark:text-gray-300"
              >
                <p class="flex-auto">
                  Adaptez l'IA à vos besoins spécifiques et créez des commandes
                  personnalisées.
                </p>
              </dd>
            </div>

            <!-- Feature 3 -->
            <div
              class="flex flex-col rounded-lg bg-white/5 p-6 backdrop-blur-sm hover:bg-white/10 transition duration-300"
            >
              <dt
                class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900 dark:text-white"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                  class="w-6 h-6 text-indigo-600 dark:text-indigo-400 flex-shrink-0"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"
                  />
                </svg>
                Interface intuitive
              </dt>
              <dd
                class="mt-4 flex flex-auto flex-col text-sm sm:text-base leading-7 text-gray-600 dark:text-gray-300"
              >
                <p class="flex-auto">
                  Une interface moderne et facile à utiliser pour une expérience
                  utilisateur optimale.
                </p>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-50 dark:bg-gray-900 mt-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8 lg:px-8">
        <div class="text-center">
          <p
            class="text-xs sm:text-sm leading-5 text-gray-500 dark:text-gray-400"
          >
            &copy; {{ new Date().getFullYear() }} Made with ❤️ by Winga. Tous
            droits réservés.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>
